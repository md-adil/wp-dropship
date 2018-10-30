<?php
namespace Bigly\Dropship;
use Exception;
use WP_Error;
use ErrorException;

error_reporting(E_ALL);

function exceptions_error_handler($severity, $message, $filename, $lineno) {
    if($filename != __FILE__) {
        return;
    }
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $filename, $lineno);
    }
}

set_error_handler('Bigly\Dropship\exceptions_error_handler');

require_once(__DIR__ . '/../../../wp-config.php');

class AuthenticationException extends Exception { }
class InvalidRequestException extends Exception { }
/**
*
*/
class SyncController
{
	protected $configs;
	protected $syncTable;
	protected $db;

    protected $attributeMeta = [
        'sku' => '_sku',
        'price' => ['_price', '_regular_price'],
        'quantity' => '_stock',
        'description' => '_variation_description',
        'weight' => '_weight',
        'length' => '_length',
        'width' => '_width',
        'height' => '_height'
    ];

    public function __construct($data)
    {
        if(!$data) {
            throw new InvalidRequestException("Payload is missing");
        }
    	global $wpdb;
    	$this->data = $data;
    	$this->configs = require(__DIR__ . '/configs/config.php');
    	$this->syncTable = $this->configs['tables']['sync'];
    	$this->db = $wpdb;
    }
    
    public function dd($attrs)
    {
        print_r($attrs);
        die();
    }

    public function sync()
    {

        if(!isset($this->data->token)) {
            throw new InvalidRequestException('Token is not defined');
        }

        if(!isset($this->data->type)) {
            throw new InvalidRequestException('Type is not defined');
        }

        if(!isset($this->data->action)) {
            throw new InvalidRequestException("Action is not defined");
        }

        if(!$this->validateRequest($this->data->token)) {
            throw new AuthenticationException("Invalid request token");
        }

        // set_time_limit(60 * 5);
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

    protected function createCategories($categories)
    {
        $ids = []; $mappings = [];

        foreach($categories as $category) {
            $parentId = 0;
            if (isset($category->parent_id) && $category->parent_id && isset($mappings[$category->parent_id])) {
                $parentId = $mappings[$category->parent_id];
            }

            $term = wp_insert_term($category->name, 'product_cat', [
                'description' => $this->ifset($category->description),
                'parent' => $parentId
            ]);
            
            if($term instanceof WP_Error) {
                if(isset($term->error_data['term_exists'])) {
                    $termId = $term->error_data['term_exists'];
                } else {
                    continue;
                }
            } else {
                $termId = $term['term_id'];
            }
            $ids[] = (int)$termId;
            $mappings[$category->id] = (int)$termId;
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
        $data = [
            'post_content' => $this->ifset($product->description),
            'post_title' => $this->ifset($product->name),
            'post_excerpt' => $this->ifset($product->excerpt),
            'post_type' => 'product',
        ];

        if(isset($product->status)) {
            if($product->status) {
                $data['post_status'] = 'publish';
            } else {
                $data['post_status'] = 'trash';
            }
        }

        return array_filter($data);
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
        if($oldId = $this->isPostExists($product)) {
            return $this->updateProduct($product, $oldId);
        }

        $data = $this->preparePost($product);
        $post = wp_insert_post($data, true);
        if($post instanceof WP_Error) {
            throw new Exception($post->get_error_message());
        }

        $this->insertProductMapping($product, $post);

        if(isset($product->categories)) {
            wp_set_object_terms($post, $this->createCategories($product->categories), 'product_cat');
        }

        if(isset($product->media)) {
            $this->insertAttachments($product, $post);
        }

        if(isset($product->attributes)) {
            $this->insertAttributes($post, $product);
        }

        $this->insertPostMeta($post, $product);
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

    protected function updateProduct($product, $postId = null)
    {
        if(!$postId) {
            $postId = $this->getPostId($product->id);
        }

        if (!$postId) {
            return;
        }

        $data = $this->preparePost($product);
        $data['ID'] = $postId;
        $post = wp_update_post($data, true);
        if(isset($product->categories)) {
            wp_set_object_terms($postId, $this->createCategories($product->categories), 'product_cat');
        }

        if(isset($product->media)) {
            $this->insertAttachments($product, $postId);
        }

        if (isset($product->attributes)) {
            $this->insertAttributes($postId, $product);
        }

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
        $this->db->query("DELETE FROM {$this->db->postmeta} WHERE post_id={$postId}");
    }

    public function getPostId($id)
    {
        return $this->getVar('host_id', [ 'guest_id' => $id, 'type' => 'product' ]);
    }

    private function insertProductMapping($product, $post)
    {
       $this->insertMapping($product->id, $post, 'product');
    }

    protected function insertMapping($guestId, $hostId, $type)
    {
        $tableName = $this->syncTable;

        $exists = $this->getVar('COUNT(*)', [
            'guest_id' => $guestId,
            'type' => $type
        ]);

        if($exists) {
            return $this->db->update($tableName, [
                'host_id' => $hostId,
            ], [
                'guest_id' => $guestId,
                'type' => $type
            ]);

        } else {
            return $this->db->insert($tableName, [
                'guest_id' => $guestId,
                'host_id' => $hostId,
                'type' => $type
            ]);
        }
    }

    protected function deleteMapping($guestId, $type)
    {
        $tableName = $this->syncTable;
        $this->db->delete($tableName, [
            'guest_id' => $guestId,
            'type' => $type
        ]);
    }

    private function getAttachmentId($guid, $postId)
    {
        return $this->db->get_var($this->db->prepare("SELECT ID FROM {$this->db->posts} WHERE guid=%s AND post_parent=%d", $guid, $postId));
    }

    private function insertAttachments($product, $postId)
    {
        $defaultImage = null;
        $attachments = [];
        foreach ($product->media as $media) {
            if(!isset($media->large)) {
                continue;
            }
            if(!$attachment = $this->getAttachmentId($media->large, $postId)) {
                $attachment = wp_insert_attachment([
                    'guid' => $media->large,
                    'post_mime_type' => $this->ifset($media->mime, 'image/jpeg'),
                    'post_excerpt' => $this->ifset($media->caption, ''),
                    'post_content' => 'biglydropship'
                ], false, $postId, true);
                if ($attachment instanceof WP_Error) {
                    continue;
                }
            }
            if ($this->ifset($media->default, false)) {
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

    protected function insertAttributes($postId, $product)
    {
        $this->insertSimpleAttributes(
            $postId,
            $this->createSimpleAttributes($product->attributes)
        );
        
        $variations = $this->createVariationAttributes($product->attributes);
        // $this->dd($variations);
        if(count($variations)) {
            wp_set_object_terms($postId, 'variable', 'product_type');
            $this->insertVariationAttributes($postId, $variations);
            $this->removeAttributeCaches($postId);
        } else {
            wp_remove_object_terms($postId, 'variable', 'product_type');
            $this->removeVariationAttributes($postId);
        }
    }

    protected function removeAttributeCaches($postId) {
        $keys = [
            '_transient_timeout_wc_product_children_',
            '_transient_wc_product_children_',
            '_transient_timeout_wc_var_prices_',
            '_transient_wc_var_prices_'
        ];

        foreach($keys as $k) {
            delete_option($k . $postId);
        }
    }

    protected function insertSimpleAttributes($postId, $attributes) {
        foreach ($attributes as $key => $attr) {
            $attributes[$key]['value'] = implode('|', array_unique($attr['value']));
        }
        update_post_meta($postId, '_product_attributes', $attributes);
    }

    private function createSimpleAttributes($attributes, $attrs = [], $pos = 0)
    {
        foreach ($attributes as $attribute) {

            if(!isset($attribute->name) || !isset($attribute->value)) {
                continue;
            }
            $isVariation = 0;
            if(isset($attribute->is_variation) && $attribute->is_variation) {
                $isVariation = 1;
            }

            $_name = strtolower($attribute->name);
            if(isset($attrs[$_name])) {
                $attrs[$_name]['value'][] = $attribute->value;
                if($isVariation) {
                    $attrs[$_name]['is_variation'] = 1;
                }
            } else {
                $attrs[$_name] = [
                   'name' => $attribute->name, 
                   'value'=> [$attribute->value],
                   'position' => $pos++,
                   'is_visible' => 1,
                   'is_variation' => $isVariation,
                   'is_taxonomy' => 0
                ];
            }

            if(isset($attribute->children)) {
                $attrs = $this->createSimpleAttributes($attribute->children, $attrs, $pos);
            }
        }
        return $attrs;
    }

    private function createVariationAttributes($attributes, $parents = [], $variations = [])
    {
        foreach($attributes as $attribute) {
            if(!isset($attribute->is_variation) || !$attribute->is_variation) {
                continue;
            }
            if(!isset($attribute->name) || !isset($attribute->value)) {
                continue;
            }
            
            if(isset($attribute->children)) {
                array_unshift($parents, $attribute);
                $variations = $this->createVariationAttributes($attribute->children, $parents, $variations);
                $parents = [];
            } else {
                $attribute->values = [];
                $attribute->values[] = [
                    'name' => $attribute->name,
                    'value' => $attribute->value
                ];
                foreach($parents as $parent) {
                    $attribute->values[] = [
                        'name' => $parent->name,
                        'value' => $parent->value
                    ];
                    if(!isset($attribute->price) && isset($parent->price)) {
                        $attribute->price = $parent->price;
                    }
                    if(!isset($attribute->quantity) && isset($parent->quantity)) {
                        $attribute->quantity = $parent->quantity;
                    }
                }
                $variations[] = $attribute;
            }
        }
        return $variations;
    }

    private function insertVariationAttributes($post_id, $variations)  
    {
        $usedVariations = [];
        foreach ($variations as $variation) {
            $index = $variation->id;
            if(!isset($variation->price)) {
                $variation->price = get_post_meta($post_id, '_price', true);
            }
            
            if(!isset($variation->quantity)) {
                $variation->quantity = get_post_meta($post_id, '_stock', true);
            }

            $variation_post_id = $this->db->get_var("SELECT ID FROM {$this->db->posts}
                WHERE post_parent={$post_id} AND post_content='{$index}'");
            if($variation_post_id) {
                wp_update_post([
                    'ID' => $variation_post_id,
                    'post_title'  => 'Variation #' . $index .' of ' .count($variations). ' for product#'. $post_id,
                    'post_status' => 'publish'
                ]);
            } else {
                $variation_post_id = wp_insert_post([
                    'post_title'  => 'Variation #' . $index .' of ' .count($variations). ' for product#'. $post_id,
                    'post_name'   => 'product-' . $post_id . '-variation-' . $index,
                    'post_status' => 'publish',
                    'post_content' => $index,
                    'post_parent' => $post_id,
                    'post_type'   => 'product_variation',
                    'guid'        => home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $index
                ]);
            }
            $usedVariations[] = $variation_post_id;
            foreach ($variation->values as $attr) {   
                $attribute = strtolower($attr['name']);
                $value = $attr['value'];
                update_post_meta($variation_post_id, 'attribute_' . $attribute, $value);
            }

            foreach($this->attributeMeta as $key => $meta) {
                if(isset($variation->{$key})) {
                    $metaVal = $variation->{$key};
                    if(is_array($meta)) {
                        foreach($meta as $m) {
                            update_post_meta($variation_post_id, $m, $metaVal);
                        }
                    } else {
                        update_post_meta($variation_post_id, $meta, $metaVal);
                    }
                    if($key === 'quantity') {
                        $stockStatus = $metaVal > 0 ? 'instock' : 'outofstock';
                        update_post_meta($variation_post_id, '_stock_status', $stockStatus);
                        update_post_meta($variation_post_id, '_manage_stock', 'yes');
                    }
                }
            }
        }
        $this->removeOldAttributeVariation($post_id, $usedVariations);
    }

    private function removeVariationAttributes($postId)
    {
        $postmeta = $this->db->postmeta;
        $posts = $this->db->posts;
        $query = $this->db->prepare(
            "{$posts} WHERE post_parent=%d AND post_type=%s",
            $postId, 'product_variation');

        $this->db->query("DELETE FROM {$postmeta}
            WHERE post_id IN (SELECT ID FROM {$query})");

        $this->db->query("DELETE FROM {$query}");
    }

    private function removeOldAttributeVariation($postId, $variations)
    {
        // Remove remaining attributes
        $posts = $this->db->posts;
        $postmeta = $this->db->postmeta;
        $variations = implode(',', $variations);
        if(!$variations) {
            return;
        }
        $query = $this->db->prepare(
            "{$posts} WHERE post_type=%s AND post_parent=%d AND ID NOT IN ({$variations})",
            'product_variation', $postId);

        $this->db->query(
            "DELETE FROM {$postmeta} WHERE post_id IN (
            SELECT ID FROM {$query})");
        $this->db->query("DELETE FROM {$query}");
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
} catch( InvalidRequestException $e ) {
    http_response_code(422);
    echo json_encode([
        'status' => 'fail',
        'message' => $e->getMessage()
    ]);
} catch( AuthenticationException $e ) {
    http_response_code(401);
    echo json_encode([
        'status' => 'fail',
        'message' => $e->getMessage()
    ]);
} catch ( Exception $e ) {
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => $e->getMessage(),
        'payload' => $e->getTrace()
    ]);
}
exit();
