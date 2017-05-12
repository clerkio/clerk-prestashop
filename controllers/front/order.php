<?php
require "ClerkAbstractFrontController.php";

class ClerkOrderModuleFrontController extends ClerkAbstractFrontController
{
    protected $fieldMap = [
        'id_order' => 'id',
        'id_customer' => 'customer',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->addFieldHandler('time', function ($order) {
            return strtotime($order['date_add']);
        });

        $this->addFieldHandler('products', function($order) {
            //Get products for order
            /** @var OrderCore $orderObj */
            $orderObj = new Order($order['id_order']);
            $products = $orderObj->getProducts();

            $response = array();

            foreach ($products as $product) {
                $response[] = array(
                    'id' => $product['product_id'],
                    'quantity' => $product['product_quantity'],
                    'price' => $product['product_price_wt'],
                );
            }

            return $response;
        });
    }

    /**
     * Get response
     *
     * @return array
     */
    public function getJsonResponse()
    {
        $response = array();

        $orders = Order::getOrdersWithInformations($this->offset, $this->limit);

        $fields = array_flip($this->fieldMap);

        foreach ($orders as $order) {
            $item = array();
            foreach ($this->fields as $field) {
                if (array_key_exists($field, array_flip($this->fieldMap))) {
                    $item[$field] = $order[$fields[$field]];
                } elseif (isset($order[$field])) {
                    $item[$field] = $order[$field];
                }

                //Check if there's a fieldHandler assigned for this field
                if (isset($this->fieldHandlers[$field])) {
                    $item[$field] = $this->fieldHandlers[$field]($order);
                }
            }

            $response[] = $item;
        }

        return $response;
    }

    protected function getDefaultFields()
    {
        return [
            'id',
            'products',
            'time',
            'email',
            'customer',
        ];
    }
}