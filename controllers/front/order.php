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

        $orders = $this->getOrdersWithInformations($this->offset, $this->limit);

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

    /**
     * Get orders with start and limit
     * @param  int $start [description]
     * @param  int $limit [description]
     * @return mixed        [description]
     */
    protected function getOrdersWithInformations($start = null, $limit = null)
    {
        $context = Context::getContext();

        $sql = 'SELECT *, (
                    SELECT osl.`name`
                    FROM `'._DB_PREFIX_.'order_state_lang` osl
                    WHERE osl.`id_order_state` = o.`current_state`
                    AND osl.`id_lang` = '.(int)$context->language->id.'
                    LIMIT 1
                ) AS `state_name`, o.`date_add` AS `date_add`, o.`date_upd` AS `date_upd`
                FROM `'._DB_PREFIX_.'orders` o
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = o.`id_customer`)
                WHERE 1
                    '.Shop::addSqlRestriction(false, 'o').'
                ORDER BY o.`date_add` DESC
                '.((int)$limit ? 'LIMIT '.(int)$start.','.(int)$limit : '');
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }
}
