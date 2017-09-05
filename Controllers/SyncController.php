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
			$term = wp_insert_term($category->name, 'product_cat', [
				'description' => $category->description,
				'parent' => $this->getCategoryParent($category)
			]);
			return $this->insertCategoryMapping($category, $term);
		}
	}

	private function getCategoryParent($category) {
		if(!$category->parent_id) return 0;
		global $wpdb;
		$tableName = Config::get('table.category');
		$parentId = $wpdb->get_var("SELECT term_id FROM {$tableName} WHERE category_id={$category->parent_id}");
		return $parentId ?: 0;
	}

	private function insertCategoryMapping($category, $term) {
		if(!is_array($term)) return;
		if(!isset($term['term_id'])) return;
		global $wpdb;
		$tableName = Config::get('tables.category');
		$wpdb->insert($tableName, [
			'term_id' => 50,
			'category_id' => 31
		]);
	}

	public function products() {
		if(count($products)) {
			$this->hasMore = true;
		}
	}
}