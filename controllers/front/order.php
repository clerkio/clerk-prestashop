<?php
/**
 *  @author Clerk.io
 *  @copyright Copyright (c) 2017 Clerk.io
 *
 *  @license MIT License
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

require "ClerkAbstractFrontController.php";

class ClerkOrderModuleFrontController extends ClerkAbstractFrontController
{

    /**
     * @var
     */
    protected $logger;

    /**
     * @var array
     */
    protected $fieldMap = array(
        'id_order' => 'id',
        'id_customer' => 'customer',
    );

    /**
     * ClerkOrderModuleFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        require_once (_PS_MODULE_DIR_. $this->module->name . '/controllers/admin/ClerkLogger.php');
        $this->logger = new ClerkLogger();

        $this->addFieldHandler('time', function ($order) {
            try {
                if(is_array($order) && array_key_exists('date_add', $order)){
                    return strtotime($order['date_add']);
                } else {
                    return strtotime("now");
                }
            } catch (Exception $e) {

                $this->logger->error('ERROR Order addFieldHandlerTime', ['error' => $e->getMessage()]);
                return strtotime("now");

            }

        });

        $this->addFieldHandler('products', function ($order) {
            //Get products for order
            /** @var OrderCore $orderObj */
            try {
                if(is_array($order) && array_key_exists('id_order', $order)){

                    global $cookie;
                    $default_currency = Currency::getDefaultCurrency();
                    $current_currency = new CurrencyCore($cookie->id_currency);

                    if($current_currency->iso_code !== $default_currency->iso_code){
                        $rate = (float) $current_currency->conversion_rate;
                    } else {
                        $rate = 1;
                    }

                    $orderObj = new Order($order['id_order']);
                    $products = $orderObj->getProducts();
                    if(is_array($products) && count($products) > 0){
                        $discounts = $orderObj->total_discounts_tax_incl;
                        $discount_per_product = $discounts / count($products);

                        $response = [];

                        foreach ($products as $product) {
                            if(is_array($product)
                            && array_key_exists('product_id', $product)
                            && array_key_exists('product_quantity', $product)
                            && array_key_exists('product_price_wt', $product)){
                                $response[] = array(
                                    'id' => $product['product_id'],
                                    'quantity' => $product['product_quantity'],
                                    'price' => ($product['product_price_wt'] - $discount_per_product) / $rate,
                                );
                            } else {
                                continue;
                            }

                        }

                        return $response;
                    }
                }
            } catch (Exception $e) {

                $this->logger->error('ERROR Order addFieldHandlerProducts', ['error' => $e->getMessage()]);

            }

        });
    }

    /**
     * Get response
     *
     * @return array
     */
    public function getJsonResponse()
    {
        try {
            header('User-Agent: ClerkExtensionBot Prestashop/v' ._PS_VERSION_. ' Clerk/v'.Module::getInstanceByName('clerk')->version. ' PHP/v'.phpversion());
            $response = [];
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

            $end_date = date('Y-m-d',strtotime(Tools::getValue('end_date') ? Tools::getValue('end_date') : 'today + 1 day'));
            $start_date = date('Y-m-d',strtotime(Tools::getValue('start_date') ? Tools::getValue('start_date') : 'today - 200 years'));

            $orders = $this->getOrdersWithInformations($limit,$end_date,$start_date);

            $fields = array_flip($this->fieldMap);

            foreach ($orders as $order) {
                $item = [];
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

            $this->logger->log('Fetched Orders', ['response' => $response]);

            return $response;

        } catch (Exception $e) {

            $this->logger->error('ERROR Order getJsonResponse', ['error' => $e->getMessage()]);

        }
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

    protected function getOrdersWithInformations($limit = null, $end_date, $start_date, Context $context = null)
    {

        try {

            if (!$context) {
                $context = Context::getContext();
            }

            $sql = 'SELECT *, (
                    SELECT osl.`name`
                    FROM `' . _DB_PREFIX_ . 'order_state_lang` osl
                    WHERE osl.`id_order_state` = o.`current_state`
                    AND osl.`id_lang` = ' . (int)$this->getLanguageId() . '
                    LIMIT 1
                ) AS `state_name`, o.`date_add` AS `date_add`, o.`date_upd` AS `date_upd`,
                o.`id_shop` AS `id_shop`, o.`id_lang` AS `id_lang`
                FROM `' . _DB_PREFIX_ . 'orders` o
                LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = o.`id_customer`)
                WHERE 1
                    ' . Shop::addSqlRestriction(false, 'o') . '
                    AND o.`id_lang` = ' . (int)$this->getLanguageId() . '
                    AND o.`date_add` BETWEEN CAST("'.$start_date.'" AS DATE) AND CAST("'.$end_date.'" AS DATE)
                ORDER BY o.`date_add` DESC
                ' . ($limit != '' ? $limit : '');

            $this->logger->log('Fetched Orders with informations', ['response' => Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql)]);

            return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        } catch (Exception $e) {

            $this->logger->error('ERROR getOrdersWithInformations', ['error' => $e->getMessage()]);

        }
    }
}
