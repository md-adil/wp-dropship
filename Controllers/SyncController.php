<?php
namespace Bigly\Dropship\Controllers;

use Bigly\Dropship\Config;
/**
* 
*/
class SyncController extends Controller
{
	
	public function sync() {
		$syncPath = Config::get('remote.sync');
		$res = blds_remote_get($syncPath);
		$responseCode = wp_remote_retrieve_response_code($res);
		if($responseCode !== 200) {
			return [
				'status' => 'fail',
				'message' => 'Something went wrong with credentials'
			];
		}
		$res = json_decode($res['body']);
		if(!$res) {
			return [
				'status' => 'fail',
				'message' => 'Unable to fetch records, something might went wrong.'
			];
		}

		if($res->categories) {
			return $this->categories($res->categories);
		}
		if($res->products) {
			$this->products($res->products);
		}

		return [
			'status' => 'ok',
			'hasMore' => $this->hasMore
		];
		
	}

	protected function categories($categories) {
		if(count($categories)) {
			$this->hasMore = true;
		}

		foreach($categories as $category) {
			switch ($category->action) {
				case 'create':
					$this->createCategory($category);
					break;
				case 'update':
					$this->updateCategory($category);
					break;
				case 'delete':
					$this->deleteCategory($category);
					break;
				
				default:
					# code...
					break;
			}
		}
	}

	protected function createCategory($category) {
		$parentId;
		if($category->parent_id) {
			$parentId = $this->getTermId($category->parent_id);
		}
		$term = wp_insert_term($category->name, 'product_cat', [
			'description' => $category->description,
			'parent' => $parentId ?: 0
		]);
		return $this->insertCategoryMapping($category, $term);
	}

	protected function updateCategory($category) {
		$termId = $this->getTermId($category->id);
		if(!$termId) {
			$this->createCategory($category);
		}
		$parentId = null;
		if($category->parent_id) {
			$parentId = $this->getTermId($category->parent_id);
		}

		$data = [
			'name' => $category->name,
			'description' => $category->description,
			'parent' => $parentId
		];

		wp_update_term($termId, 'product_cat', $data);
	}

	protected function deleteCategory($category) {
		$termId = $this->getTermId($category->id);
		wp_delete_term($termId, 'product_cat', [
			'force_default' => true
		]);
	}

	protected function getTermId($id) {
		global $wpdb;
		$tableName = Config::get('table.category');
		return $wpdb->get_var("SELECT term_id FROM {$tableName} WHERE category_id={$id}");
	}

	private function getCategoryParent($category) {
		if(!$category->parent_id) return 0;
		global $wpdb;
		$tableName = Config::get('table.category');
		$parentId = $wpdb->get_var("SELECT term_id FROM {$tableName} WHERE category_id={$category->parent_id}");
		return $parentId ?: 0;
	}

	private function insertCategoryMapping($category, $term) {
		if($term instanceof WP_Error) return;
		if(!isset($term['term_id'])) return;
		global $wpdb;
		$tableName = Config::get('tables.category');
		$wpdb->insert($tableName, [
			'term_id' => $term['term_id'],
			'category_id' => $category->id
		]);
	}

	public function products($products) {
		if(count($products)) {
			$this->hasMore = true;
		}

		foreach($products as $product) {
			switch ($product->action) {
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
	}

	protected function createProduct($product) {
		$categories = array_map(function($cat) {
			return $cat->id;
		}, $product->categories);

		$post = wp_insert_post([
			'post_content' => $product->description,
			'post_title' => $product->name,
			'post_excerpt' => $product->excerpt,
			'post_status' => 'publish',
			'post_type' => 'product',
			'post_category' => $$categories
		], true);
		$this->insertProductMapping($product, $post);
	}

	protected function updateProduct($product) {
		$postId = $this->getPostId($product->id);
		if(!$postId) {
			$this->createProduct($product);
		}
		$categories = null;
		if($product->categories) {
			$categories = array_map(function($cat) {
				return $cat->id;
			}, $product->categories);
		}
		$data = [
			'ID' => $postId,
			'post_content' => $product->description,
			'post_title' => $product->name,
			'post_excerpt' => $product->excerpt,
			'post_category' => $$categories
		];

		$data = array_filter($data, function($val) {
			return $val;
		});

		$post = wp_insert_post($data, true);
	}

	protected function deleteProduct($product) {
		$postId = $this->getPostId($product->id);
		if(!$postId) return;
		wp_delete_post($postId, true);
	}

	public function getPostId($id) {
		$tableName = Config::get('tables.product');
		global $wpdb;
		return $wpdb->get_var("SELECT post_id FROM {$tableName} WHERE product_id={$id}");
	}

	private function insertProductMapping($product, $post) {
		if($post instanceof WP_Error) {
			return;
		}
		global $wpdb;
		$tableName = Config::get('tables.category');
		$wpdb->insert($tableName, [
			'product_id' => $product->id,
			'post_id' => $post
		]);
	}
}
