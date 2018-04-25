<?php
namespace Bigly\Dropship;
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
        if(!$this->validateRequest($this->data->token)) {
            http_response_code(500);
            return [
                'status' => 'fail',
                'message' => 'invalid token'
            ];
        }
        set_time_limit(60 * 5);
        try {
           switch ($this->data->type) {
           	case 'product':
           		$this->product($this->data->data, $this->data->action);
           		break;
           	default:
           		break;
           }
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'status' => 'fail',
                'message' => $e->getMessage()
            ];
        }
        return [
            'status' => 'ok'
        ];
    }

    protected function validateRequest($token) {
        $tokenKey = $this->configs['options']['webhook_token'];
        return $token === get_option($tokenKey);
    }

    protected function createCategory($category)
    {
        $parentId = 0;
        if (isset($category->parent_id) && $category->parent_id) {
            $parentId = $this->getTermId($category->parent_id);
        }
        $term = wp_insert_term($category->name, 'product_cat', [
            'description' => $category->description,
            'parent' => $parentId ?: 0
        ]);
        if(!$term instanceof WP_Error) {
            $this->insertCategoryMapping($category, $term);
        }
        return $term;
    }

    protected function updateCategory($category)
    {
        $termId = $this->getTermId($category->id);
        if (!$termId) {
            $this->createCategory($category);
        }
        $parentId = null;
        if ($category->parent_id) {
            $parentId = $this->getTermId($category->parent_id);
        }
        $data = [
            'name' => $category->name,
            'description' => $category->description,
            'parent' => $parentId
        ];
        wp_update_term($termId, 'product_cat', $data);
    }

    protected function deleteCategory($category)
    {
        $termId = $this->getTermId($category->id);
        wp_delete_term($termId, 'product_cat', [
            'force_default' => true
        ]);
        $this->deleteMapping($category->id, 'category');
    }

    protected function getTermId($id)
    {
        return $this->getVar('host_id', [ 'guest_id' => $id, 'type' => 'category' ]);
    }

    private function insertCategoryMapping($category, $term)
    {
        if (!isset($term['term_id'])) {
            return;
        }
        $this->insertMapping($category->id, $term['term_id'], 'category');
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
        $categories = [];
        
        if (isset($product->categories)) {
            $categories = array_map(function ($cat) {
                return $this->getTermId($cat->id);
            }, $product->categories);
        }

        return [
            'post_content' => $this->ifset($product->description),
            'post_title' => $this->ifset($product->name),
            'post_excerpt' => $this->ifset($product->excerpt),
            'post_status' => 'publish',
            'post_type' => 'product',
            'tax_input' => [
                'product_cat' => $categories
            ]
        ];
    }

    protected function createProduct($product)
    {
        // check if product exists then recreate.

        $data = $this->preparePost($product);
        $data = array_filter($data);
        $post = wp_insert_post($data, true);
        $this->insertProductMapping($product, $post);
        $this->insertAttributes($product, $post);
        $this->insertPostMeta($post, $product);
        $this->insertAttachments($product, $post);
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

    private function insertAttachments($product, $postId)
    {
        if (!isset($product->media) || !$product->media) {
            return;
        }
        $defaultImage = null;
        $attachments = [];
        foreach ($product->media as $media) {
            $attachment = wp_insert_attachment([
                'guid' => $media->large,
                'post_mime_type' => $media->mime ?: 'image/jpeg',
                'post_excerpt' => $media->caption ?: '',
                'post_content' => 'biglydropship'
            ], false, $postId, true);
           
            if ($attachment instanceof WP_Error) {
                $err = $attachment->get_error_messages();
                continue;
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
try {
    $json = ( new SyncController($data) )->sync();
    header("Content-Type: application/json");
    echo json_encode($json);
} catch (Exception $e) {}
exit();

