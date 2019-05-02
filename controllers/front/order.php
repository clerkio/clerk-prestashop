<?php
require "ClerkAbstractFrontController.php";

class ClerkOrderModuleFrontController extends ClerkAbstractFrontController
{
    protected $fieldMap = array(
        'id_order' => 'id',
        'id_customer' => 'customer',
    );

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

            $discounts = $orderObj->total_discounts_tax_incl;
            $discount_per_product = $discounts / count($products);

            $response = array();

            foreach ($products as $product) {
                $response[] = array(
                    'id' => $product['product_id'],
                    'quantity' => $product['product_quantity'],
                    'price' => $product['product_price_wt'] - $discount_per_product,
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
        $limit = '';

        if (Configuration::get('CLERK_DISABLE_ORDER_SYNC', $this->getLanguageId(), null, $this->getShopId())) {
            return $response;
        }

        if ($this->limit > 0) {
            $limit = sprintf('LIMIT %s', $this->limit);
        }

        if ($this->offset > 0) {
            $limit .= sprintf(' OFFSET %s', $this->offset);
        }

        $orders = $this->getOrdersWithInformations($limit);

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

            if (isset($item['email']) && !Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $this->getLanguageId(), null, $this->getShopId())) {
                unset($item['email']);
            }

            $response[] = $item;
        }

        return $response;
    }

    protected function getDefaultFields()
    {
        return array(
            'id',
            'products',
            'time',
            'email',
            'customer',
        );
    }

    protected function getOrdersWithInformations($limit = null, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $sql = 'SELECT *, (
					SELECT osl.`name`
					FROM `'._DB_PREFIX_.'order_state_lang` osl
					WHERE osl.`id_order_state` = o.`current_state`
					AND osl.`id_lang` = '.(int)$this->getLanguageId().'
					LIMIT 1
				) AS `state_name`, o.`date_add` AS `date_add`, o.`date_upd` AS `date_upd`
				FROM `'._DB_PREFIX_.'orders` o
				LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = o.`id_customer`)
				WHERE 1
					'.Shop::addSqlRestriction(false, 'o').'
				ORDER BY o.`date_add` DESC
				'.($limit != '' ? $limit : '');
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }
}