<?php
namespace Bigly\Dropship;
use Exception;
use WP_Error;
require('../../../wp-config.php');

/**
*
*/
class SyncController
{
	protected $configs;
	protected $syncTable;
	protected $db;
    public function __construct($data)
    {
    	global $wpdb;
    	$this->data = $data;
    	$this->configs = require('configs/config.php');
    	$this->syncTable = $this->configs['tables']['sync'];
    	$this->db = $wpdb;
    }

    public function sync()
    {
        // echo $name[0];
        if(!$this->validateRequest($this->data->token)) {
            http_response_code(401);
            return [
                'status' => 'fail',
                'message' => 'invalid token'
            ];
        }
        set_time_limit(60 * 5);
       switch ($this->data->type) {
       	case 'product':
       		$this->product($this->data->data, $this->data->action);
       		break;
       	default:
       		break;
       }
        return [
            'status' => 'ok'
        ];
    }

    protected function validateRequest($token) {
        $tokenKey = $this->configs['options']['webhook_token'];
        return $token === get_option($tokenKey);
    }

    public function getTermByName($term, $taxonomy)
    {
        $prefix = $this->db->prefix;
        return $this->db->get_var($this->db->prepare("SELECT terms.term_id FROM {$prefix}terms as terms JOIN
                {$prefix}term_taxonomy as taxonomy ON terms.term_id = taxonomy.term_id
            WHERE terms.name=%s AND taxonomy.taxonomy=%s", $term, $taxonomy));
    }

    protected function createCategories($categories)
    {
        $ids = []; $mappings = []; $parentId = 0;
        foreach($categories as $category) {
            if (isset($category->parent_id) && $category->parent_id && isset($mappings[$category->parent_id])) {
                $parentId = $mappings[$category->parent_id];
            }
            $term = $this->getTermByName($category->name, 'product_cat');
            if($term) {
                $ids[] = (int)$term;
            } else {
                $term = wp_insert_term($category->name, 'product_cat', [
                    'description' => $category->description,
                    'parent' => $parentId
                ]);
                if($term instanceof WP_Error) {
                    // throw new Exception((string)$term->get_error_message(), 422);
                    continue;
                }
                $term = $term['term_id'];
                $ids[] = (int)$term;
            }
            $mappings[$category->id] = $term;
        }
        return $ids;
    }

    public function product($product, $action)
    {
        switch ($action) {
            case 'create':
                $this->createProduct($product);
                break;
            case 'update':
                $this->updateProduct($product);
                break;
            case 'delete':
                $this->deleteProduct($product);
            default:
                # code...
                break;
        }
    }

    protected function preparePost($product)
    {
        $categories = null;
        
        return [
            'post_content' => $this->ifset($product->description),
            'post_title' => $this->ifset($product->name),
            'post_excerpt' => $this->ifset($product->excerpt),
            'post_status' => 'publish',
            'post_type' => 'product',
        ];
    }

    protected function isPostExists($product)
    {
        $postId = $this->getPostId($product->id);
        if(!$postId) {
            return false;
        }
        $post = get_post($postId);
        if($post) {
            return $postId;
        }
        $this->deleteMapping($product->id, 'product');
        return false;
    }

    protected function createProduct($product)
    {
        // check if product exists then recreate.
        $data = $this->preparePost($product);
        if($oldId = $this->isPostExists($product)) {
            $data['ID'] = $oldId;
        }

        $data = array_filter($data);
        // die(print_r($data,1));
        $post = wp_insert_post($data, true);
        if($post instanceof WP_Error) {
            throw new Exception($post->get_error_message(), 12);
        }

        if($this->ifset($product->categories)) {
            wp_set_object_terms($post, $this->createCategories($product->categories), 'product_cat');
        }

        $this->insertProductMapping($product, $post);
        $this->insertAttributes($product, $post);
        $this->insertPostMeta($post, $product);
        $this->insertAttachments($product, $post);
        // Need to work on
        $this->insertAttributes($product, $post);
        //update_post_meta($postId, '_product_image_gallery', implode(',', $attachments));
    }

    protected function preparePostMeta($product)
    {
        return array_filter([
            '_sku' => $this->ifset($product->sku),
            '_weight' => $this->ifset($product->weight),
            '_height' => $this->ifset($product->height),
            '_width' => $this->ifset($product->width),
            '_length' => $this->ifset($product->length),
            '_tax_status' => $this->ifset($product->taxable),
            '_sale_price' => $this->ifset($product->amount),
            '_regular_price' => $this->ifset($product->amount),
            '_price' => $this->ifset($product->amount),
            '_stock' => $this->ifset($product->quantity),
            '_stock_status' => $this->ifset($product->quantity) === 0 ?  'outofstock' : 'instock'
        ], function ($val) {
            return !is_null($val);
        });
    }

    protected function insertPostMeta($postId, $product)
    {
        if ($postId instanceof WP_Error) {
            return;
        }
        $meta = $this->preparePostMeta($product);
        $meta['_manage_stock'] = 'yes';
        foreach ($meta as $key => $val) {
            add_post_meta($postId, $key, $val, true);
        }
    }

    protected function updatePostMeta($postId, $product)
    {
        $meta = $this->preparePostMeta($product);
        foreach ($meta as $key => $val) {
            update_post_meta($postId, $key, $val);
        }
    }

    protected function updateProduct($product)
    {
        $postId = $this->getPostId($product->id);
        if (!$postId) {
            return;
        }
        $data = $this->preparePost($product);
        $data['ID'] = $postId;
        $data = array_filter($data);
        $post = wp_insert_post($data, true);

        if($this->ifset($product->categories)) {
            wp_set_object_terms($post, $this->createCategories($product->categories), 'product_cat');
        }
        // Checking if product has already medias then remove it first then insert again.
        return $this->updatePostMeta($postId, $product);
    }

    protected function deleteProduct($product)
    {
        $postId = $this->getPostId($product->id);
        if (!$postId) {
            return;
        }
        global $wpdb;
        wp_delete_post($postId, true);
        $this->deleteMapping($product->id, 'product');
    }

    public function getPostId($id)
    {
        return $this->getVar('host_id', [ 'guest_id' => $id, 'type' => 'product' ]);
    }

    private function insertProductMapping($product, $post)
    {
        if ($post instanceof WP_Error) {
            return;
        }
       $this->insertMapping($product->id, $post, 'product');
    }

    protected function insertMapping($guestId, $hostId, $type) {
        $tableName = $this->syncTable;
        $exists = $this->getVar('COUNT(*)', [
            'guest_id' => $guestId,
            'type' => $type
        ]);

        if($exists) {
            $this->db->update($tableName, [
                'host_id' => $hostId,
            ], [
                'guest_id' => $guestId,
                'type' => $type
            ]);

        } else {
            $this->db->insert($tableName, [
                'guest_id' => $guestId,
                'host_id' => $hostId,
                'type' => $type
            ]);
        }
    }

    protected function deleteMapping($guestId, $type) {
        $tableName = $this->syncTable;

        $this->db->delete($tableName, [
            'guest_id' => $guestId,
            'type' => $type
        ]);
    }

    private function getAttachmentId($guid, $postId) {
        return $this->db->get_var($this->db->prepare("SELECT ID FROM {$this->db->posts} WHERE guid=%s AND post_parent=%d", $guid, $postId));
    }

    private function insertAttachments($product, $postId)
    {
        if (!isset($product->media) || !$product->media) {
            return;
        }
        $defaultImage = null;
        $attachments = [];
        foreach ($product->media as $media) {
            if(!$attachment = $this->getAttachmentId($media->large, $postId)) {
                $attachment = wp_insert_attachment([
                    'guid' => $media->large,
                    'post_mime_type' => $media->mime ?: 'image/jpeg',
                    'post_excerpt' => $media->caption ?: '',
                    'post_content' => 'biglydropship'
                ], false, $postId, true);
                if ($attachment instanceof WP_Error) {
                    continue;
                }
            }

            if ($media->default) {
                $defaultImage = $attachment;
            } else {
                $attachments[] = $attachment;
            }
        }
        if(!$defaultImage) {
            $defaultImage = array_shift($attachments);
        }
        if($defaultImage) {
            set_post_thumbnail($postId, $defaultImage);
        }
        if($attachments) {
            update_post_meta($postId, '_product_image_gallery', implode(',', $attachments));
        }
    }

    protected function insertAttributes($product, $postId)
    {
        $thedata = [];
        if (!isset($product->attributes) || !$product->attributes) {
            return;
        }
        foreach ($product->attributes as $attribute) {
            wp_set_object_terms( $postId, $attribute->pivot->value, $attribute->name);
            $term_taxonomy_ids = wp_set_object_terms( $postId, $attribute->pivot->value, $attribute->name, true );
            $thedata[$attribute->name] = [
               'name' => $attribute->name, 
               'value'=> $attribute->pivot->value,
               'position' => '0',
               'is_visible' => '1',
               'is_variation' => '1',
               'is_taxonomy' => '0'
            ];
        }
        update_post_meta( $postId, '_product_attributes', $thedata);
    }

    protected function getVar($var, $queries = array())
    {
        $tableName = $this->syncTable;
        $conds = [];
        foreach ($queries as $key => $val) {
            $conds[] = "{$key}='{$val}'";
        }
        $conds = implode(' AND ', $conds);
        return $this->db->get_var("SELECT {$var} FROM {$tableName} WHERE $conds");
    }

    private function ifset(&$val, $def = null) {
        if(isset($val)) {
            return $val;
        }
        return $def;
    }
}

$data = json_decode(file_get_contents('php://input'));
header("Content-Type: application/json");
try {
    $json = ( new SyncController($data) )->sync();
    echo json_encode($json);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => $e->getMessage(),
        'payload' => $e
    ]);
}
exit();

