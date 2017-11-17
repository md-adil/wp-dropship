<?php
namespace Bigly\Dropship\Controllers;
use WC_Order;
use Bigly\Dropship\Library\Client;

class OrderController extends Controller
{
    protected $request;

    protected function placed($orderId)
    {
        $this->request = new Client($this->config);
        $order = new WC_Order($orderId);
        $this->create($order);

    }

    protected function create(WC_Order $order)
    {
        $products = $this->getOrderItems($order);
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

        $data = json_decode($res['body']);
        $orderId = $data->id;
        $this->insertMapping($order, $orderId);
    }

    protected function getOrderItemsId(WC_Order $order)
    {
        $items = $order->get_items();
        $posts = [];
        $product = [];
        foreach ($items as $item) {
            $posts[] = $item['product_id'];
        }
        $table = $this->config->get('tables.product');
        $results = $this->db->get_results("SELECT product_id FROM {$table} WHERE post_id IN " . implode(',', $posts), OBJECT);

        foreach ($results as $row) {
            $product[] = $row->product_id;
        }
        return $products;
    }

    public function update($postId, $data)
    {
        $orderId = $this->getMappingId($order->get_id());
        
        if (!$orderId) {
            return $this->create(new WC_Order($postId));
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
        $table = $this->config->get('tables.order');
        return $this->db->get_var("SELECT order_id FROM {$table} WHERE post_id={$id}");
    }

    protected function insertMapping(WC_Order $order, $orderId)
    {
        $table = $this->config->get('tables.order');
        $this->db->insert($table, [
            'post_id' => $order->get_id(),
            'order_id' => $orderId
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

    protected function onHold($orderId)
    {
        $this->update($orderId, ['status' => 'on-hold']);
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
