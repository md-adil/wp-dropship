<?php
namespace Bigly\Dropship;
use WC_Order;
use Bigly\Dropship\Config;

class RegisterOrderHook {

	protected $db;

	function __construct() {
		add_action('woocommerce_new_order', [$this, 'placed'], 10, 1);
		add_action('woocommerce_order_status_completed', [$this, 'completed'], 10, 1);
		add_action('woocommerce_order_status_failed', [$this, 'failed'], 10, 1);
		add_action('woocommerce_order_status_on-hold', [$this, 'onHold'], 10, 1);
		add_action('woocommerce_order_status_refunded', [$this, 'refunded'], 10, 1);
		add_action('woocommerce_order_status_cancelled', [$this, 'cancelled'], 10, 1);
		global $wpdb;
		$this->db = $wpdb;
	}

	protected function placed($orderId) {
		$order = new WC_Order( $orderId );
		$this->create($order);
	}

	protected function create(WC_Order $order) {
		$products = $this->getOrderItems($order);
		if(!$products) return;

		$res = blds_remote_post('api/orders', [
			'header' => [
				'content-type' => 'application/json'
			],
			'body' => [
				'name' => $order->get_order_key(),
				'customer_id' => $order->get_customer_id('billing'),
				'customer_note' => $order->get_customer_note(),
				'shipping' => $order->get_address('shipping'),
				'billing' => $order->get_address('billing'),
				'products' => $products,
				'status' => $order->get_status()
			]
		]);

		$data = json_decode($res['body']);
		$orderId = $data->id;
		$this->insertMapping($order, $orderId);
	}

	protected function getOrderItemsId(WC_Order $order) {
		$items = $order->get_items();
		$posts = [];
		$product = [];
		foreach ( $items as $item ) {
			$posts[] = $item['product_id'];
		}
		$table = Config::get('tables.product');
		$results = $this->db->get_results("SELECT product_id FROM {$table} WHERE post_id IN " . implode(',', $posts), OBJECT);

		foreach($results as $row) {
			$product[] = $row->product_id;
		}
		return $products;
	}

	public function update($postId, $data) {
		$orderId = $this->getMappingId($order->get_id());
		
		if(!$orderId) {
			return $this->create(new WC_Order($postId));
		}

		$res = blds_remote_post('api/orders/' . $orderId, [
			'header' => [
				'content-type' => 'application/json'
			],
			'body' => $data
		]);
	}

	protected function getMappingId($id) {
		$table = Config::get('tables.order');
		return $this->db->get_var("SELECT order_id FROM {$table} WHERE post_id={$id}");
	}

	protected function insertMapping(WC_Order $order, $orderId) {
		$table = Config::get('tables.order');
		$this->db->insert($table, [
			'post_id' => $order->get_id(),
			'order_id' => $orderId
		]);
	}

	protected function completed($orderId) {
		$this->update($orderId, ['status' => 'completed']);
	}

	protected function failed($orderId) {
		$this->update($orderId, ['status' => 'failed']);

	}

	protected function onHold($orderId) {

		$this->update($orderId, ['status' => 'on-hold']);
	}

	protected function refunded($orderId) {
		$this->update($orderId, ['status' => 'refund']);
	}

	protected function cancelled($orderId) {
		$this->update($orderId, ['status' => 'cancelled']);
	}
}
