<?php
namespace Bigly\Dropship\Controllers;

use Bigly\Dropship\Config;
use Bigly\Dropship\Library\Client;
use Exception;
use WP_Error;

/**
*
*/
class SyncController extends Controller
{
    protected $hasMore = false;
    protected $request;
    public function __construct()
    {
        parent::__construct();
        $this->request = new Client($this->config);
    }

    public function sync()
    {
        try {
            $syncPath = $this->config->get('remote.sync');
            $res = $this->request->withAuth()->get($syncPath);
            $responseCode = wp_remote_retrieve_response_code($res);
            if ($responseCode === 401) {
                return [
                    'status' => 'fail',
                    'message' => 'It seems credentials isnt valid. Do you want to update?',
                    'redirect' => 'admin.php?page=' . 'bigly-dropship/credentials'
                ];
            }

            if ($responseCode !== 200) {
                return [
                    'status' => 'fail',
                    'message' => 'Something went wrong'
                ];
            }

            $res = json_decode($res['body']);

            if (!$res) {
                return [
                    'status' => 'fail',
                    'message' => 'Unable to fetch records, something might went wrong.'
                ];
            }
            if (isset($res->categories)) {
                $this->categories($res->categories);
            }
            
            if ($res->products) {
                $this->products($res->products);
            }
        } catch (Exception $e) {
            return $e->getLine();
        }
        
        return [
            'status' => 'ok',
            'hasMore' => $this->hasMore,
            'data' => [
                'categories' => count($this->ifset($res->categories, [])),
                'products' => count($this->ifset($res->products, []))
            ]
        ];
    }

    protected function categories($categories)
    {
        if (count($categories)) {
            $this->hasMore = true;
        }

        foreach ($categories as $category) {
            switch ($category->action) {
                case 'create':
                    return $this->createCategory($category);
                    break;
                case 'update':
                    $this->updateCategory($category);
                    break;
                case 'delete':
                    $this->deleteCategory($category);
                    break;
                default:
                    break;
            }
        }
    }

    protected function createCategory($category)
    {
        $parentId = 0;
        if ($category->parent_id) {
            $parentId = $this->getTermId($category->parent_id);
        }

        $term = wp_insert_term($category->name, 'product_cat', [
            'description' => $category->description,
            'parent' => $parentId ?: 0
        ]);

        return $this->insertCategoryMapping($category, $term);
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
    }

    protected function getTermId($id)
    {
        $tableName = $this->config->get('table.sync');
        return $this->db->get_var("SELECT host_id FROM {$tableName} WHERE guest={$id} AND type='category'");
    }

    private function getCategoryParent($category)
    {
        if (!$category->parent_id) {
            return 0;
        }
        global $wpdb;
        $tableName = $this->config->get('table.sync');
        $parentId = $wpdb->get_var("SELECT host_id FROM {$tableName} WHERE guest_id={$category->parent_id} AND type='category'");
        return $parentId ?: 0;
    }

    private function insertCategoryMapping($category, $term)
    {
        if ($term instanceof WP_Error) {
            return;
        }
        if (!isset($term['term_id'])) {
            return;
        }
        global $wpdb;
        $tableName = $this->config->get('tables.sync');
        $wpdb->insert($tableName, [
            'host_id' => $term['term_id'],
            'guest_id' => $category->id,
            'type' => 'category'
        ]);
    }

    public function products($products)
    {
        if (count($products)) {
            $this->hasMore = true;
        }

        foreach ($products as $product) {
            switch ($product->action) {
                case 'create':
                    $this->createProduct($product);
                    break;
                case 'update':
                    $this->updateProduct($product);
                    break;
                case 'delete':
                    $this->deleteProduct($product);
                    // no break
                default:
                    # code...
                    break;
            }
        }
    }

    protected function preparePost($product)
    {
        $categories = [];
        if (isset($product->categories)) {
            $categories = array_map(function ($cat) {
                return $cat->id;
            }, $product->categories);
        }

        return [
            'post_content' => $this->ifset($product->description),
            'post_title' => $this->ifset($product->name),
            'post_excerpt' => $this->ifset($product->excerpt),
            'post_status' => 'publish',
            'post_type' => 'product',
            'post_category' => $categories
        ];
    }

    protected function createProduct($product)
    {
        $data = $this->preparePost($product);
        $data = array_filter($data);
        $post = wp_insert_post($data, true);
        $this->insertProductMapping($product, $post);
        $this->insertPostMeta($post, $product);
        $this->insertAttachments($product, $post);
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
            return $this->createProduct($product);
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
        $tableName = $this->config->get('tables.sync');
        wp_delete_post($postId, true);
        $wpdb->delete($tableName, ['guest_id' => $product->id, 'type'=>'product']);
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
        $tableName = $this->config->get('tables.sync');
        $this->db->insert($tableName, [
            'guest_id' => $product->id,
            'host_id' => $post,
            'type' => 'category'
        ]);
    }

    public function test()
    {
    }

    private function insertAttachments($product, $postId)
    {
        if (!isset($product->media) || !$product->media) {
            return;
        }

        foreach ($product->media as $media) {
            $attachment = wp_insert_attachment([
                'guid' => $media->large,
                'post_mime_type' => $media->mime,
                'caption' => $media->caption
            ], false, $postId, true);
            if ($attachment instanceof WP_Error) {
                continue;
            }

            if ($media->default) {
                set_post_thumbnail($postId, $attachment);
            }
        }
    }

    protected function getVar($var, $queries = array())
    {
        $tableName = $this->config->get('tables.sync');
        $conds = [];
        foreach ($queries as $key => $val) {
            $conds[] = "{$key}='{$val}'";
        }
        $conds = implode(' AND ', $conds);
        $this->db->get_var("SELECT {$var} FROM {$tableName} WHERE $conds");
    }

    protected function arrayToQuery()
    {
    }
}
