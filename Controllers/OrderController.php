<?php
namespace Bigly\Dropship\Controllers;

use WC_Order;
use Bigly\Dropship\Library\Client;

class OrderController extends Controller
{
    protected $request;

    public function __construct()
    {
        $this->request = new Client($this->config);
    }
    public function placed($orderId)
    {
        $order = new WC_Order($orderId);
        $this->create($order);
    }

    protected function create(WC_Order $order)
    {
        $products = $this->getOrderItemsId($order);
        if (!$products) {
            return;
        }

        $res = $this->request->withAuth()->post('api/orders', [
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

        if ($res instanceof \WP_Error) {
            // HAndle message
        }

        $data = json_decode($res['body']);
        $orderId = $data->id;
        $this->insertMapping($order, $orderId);
    }

    protected function getOrderItemsId(WC_Order $order)
    {
        $items = $order->get_items();
        $posts = [];
        $products = [];
        foreach ($items as $item) {
            $posts[] = $item['product_id'];
        }
        $table = $this->config->get('tables.sync');
        $results = $this->db->get_results("SELECT guest_id as product_id FROM {$table} WHERE type='product' AND host_id IN " . implode(',', $posts), OBJECT);

        foreach ($results as $row) {
            $products[] = $row->product_id;
        }
        return $products;
    }

    public function update($postId, $data)
    {
        $orderId = $this->getMappingId($order->get_id());
        
        if (!$orderId) {
            return;
        }

        $res = $this->request->withAuth()->post('api/orders/' . $orderId, [
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

    protected function completed($orderId)
    {
        $this->update($orderId, ['status' => 'completed']);
    }

    protected function failed($orderId)
    {
        $this->update($orderId, ['status' => 'failed']);
    }

    public function onHold($orderId)
    {
        $order = new WC_Order($orderId);
        try {
            $this->create($order);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    protected function refunded($orderId)
    {
        $this->update($orderId, ['status' => 'refund']);
    }

    protected function cancelled($orderId)
    {
        $this->update($orderId, ['status' => 'cancelled']);
    }
}
