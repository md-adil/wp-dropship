<?php
namespace Bigly\Dropship\Controllers;

use WC_Order;
use WP_Error;
use Bigly\Dropship\Library\Client;

class OrderController extends Controller
{
    protected $request;

    public function __construct()
    {
        @parent::__construct();
        $this->request = new Client($this->config);
    }
    
    protected function orderExists(WC_Order $order)
    {
        return $this->getMappingId($order->get_id());
    }

    protected function create(WC_Order $order)
    {
        $products = $this->getOrderItems($order);
        if (!$products) {
            return;
        }
        
        if($guestId = $this->orderExists($order)) {
            return $this->update($order, ['status' =>'placed'], $guestId);
        }
        $res = $this->request->withAuth()->post('api/orders', [
            'header' => [
                'content-type' => 'application/json'
            ],
            'body' => [
                'name' => $order->get_order_key(),
                'customer_id' => $order->get_customer_id('billing'),
                'amount' => $order->get_total(),
                'customer_note' => $order->get_customer_note(),
                'shipping' => $order->get_address('shipping'),
                'billing' => $order->get_address('billing'),
                'products' => $products,
                'payment_method' => $order->get_payment_method(),
                'status' => $order->get_status()
            ]
        ]);

        if ($res instanceof WP_Error) {
            echo 'Service Error';
            return;
        }

        $data = json_decode($res['body']);

        if(!$data) {
            echo 'Error get response';
            return;
        }

        $orderId = $data->id;
        $this->insertMapping($order, $orderId);
    }

    protected function getOrderItems(WC_Order $order)
    {
        $items = $order->get_items();
        $posts = [];
        foreach ($items as $item) {
            $posts[$item['product_id']] = $item;
        }

        $table = $this->config->get('tables.sync');
        $results = $this->db->get_results("SELECT guest_id as product, host_id as post FROM {$table} WHERE type='product' AND host_id IN (" . implode(',', array_keys($posts)) . ')', OBJECT);

        $products = [];
        foreach ($results as $row) {
            $product = [
                'id' => $row->product,
                'name' => $posts[$row->post]['name'],
                'quantity' => $posts[$row->post]['quantity'],
                'amount' => $posts[$row->post]['total']
            ];

            if($variationId = $posts[$row->post]->get_variation_id()) {
                $product['attribute_id'] = $this->getGuestAttributeId($variationId);
            }

            $products[] = $product;
        }
        return $products;
    }

    protected function getGuestAttributeId($postId) {
        $table = $this->db->posts;
        return $this->db->get_var("SELECT post_content FROM {$table} WHERE ID={$postId}");
    }

    protected function update($postId, $data, $orderId = null)
    {
        $orderId = $orderId ?: $this->getMappingId($postId);
        if (!$orderId) {
            return;
        }
        return $this->request->withAuth()->put('api/orders/' . $orderId, [
            'header' => [
                'content-type' => 'application/json'
            ],
            'body' => $data
        ]);
    }

    protected function getMappingId($id)
    {
        $table = $this->config->get('tables.sync');
        return $this->db->get_var("SELECT guest_id as order_id FROM {$table} WHERE type='order' AND host_id={$id}");
    }

    protected function insertMapping(WC_Order $order, $orderId)
    {
        $table = $this->config->get('tables.sync');
        $this->db->insert($table, [
            'host_id' => $order->get_id(),
            'guest_id' => $orderId,
            'type' => 'order'
        ]);
    }

    public function completed($orderId)
    { 
        $this->update($orderId, ['status' => 'completed']);
    }

    public function failed($orderId)
    {
        $this->update($orderId, ['status' => 'failed']);
    }

    public function processing($id) {
        $this->update($id, ['status' => 'processing']);
    }

    public function onHold($orderId)
    {
        $this->update($orderId, ['status' => 'on-hold']);
    }

    public function refunded($orderId)
    {
        $this->update($orderId, ['status' => 'refunded']);
    }

    public function cancelled($orderId)
    {
       $this->update($orderId, ['status' => 'cancelled']);
    }

    public function placed($orderId)
    {

       $order = new WC_Order($orderId);
       $this->create($order);
    }
}
