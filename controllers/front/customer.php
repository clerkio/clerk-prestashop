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

class ClerkCustomerModuleFrontController extends ClerkAbstractFrontController
{

    protected $logger;

    public function __construct()
    {
        parent::__construct();
        require_once (_PS_MODULE_DIR_. $this->module->name . '/controllers/admin/ClerkLogger.php');
        $this->logger = new ClerkLogger();

    }
    /**
     * Get response
     *
     * @return array
     */
    public function getJsonResponse()
    {
        try {
            header('User-Agent: ClerkExtensionBot Prestashop/v' . _PS_VERSION_ . ' Clerk/v' . Module::getInstanceByName('clerk')->version . ' PHP/v' . phpversion());

            if (Configuration::get('CLERK_DATASYNC_DISABLE_CUSTOMER_SYNC',  $this->getLanguageId(), null, $this->getShopId()) == '1') {
                return [];
            }

            $get_sub_status = false;
            if (Configuration::get('CLERK_DATASYNC_SYNC_SUBSCRIBERS',  $this->getLanguageId(), null, $this->getShopId()) == '1') {
                $get_sub_status = true;
            }

            $get_email = false;
            if (Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS',  $this->getLanguageId(), null, $this->getShopId()) == '1') {
                $get_email = true;
            }

            $end_date = date('Y-m-d',strtotime(Tools::getValue('end_date') ? Tools::getValue('end_date') : 'today + 1 day'));
            $start_date = date('Y-m-d',strtotime(Tools::getValue('start_date') ? Tools::getValue('start_date') : 'today - 200 years'));

            $language_iso = Language::getIsoById($this->getLanguageId()) ? strtoupper(Language::getIsoById($this->getLanguageId())) : null;

            $sql = "SELECT c.`id_customer` AS `id`, gl.`name` AS `gender`, c.`lastname`, c.`firstname`, c.`email`, c.`newsletter` AS `subscribed`, c.`optin`, cg.id_group AS `customer_group_id`
            FROM " . _DB_PREFIX_ . "customer c
            LEFT JOIN " . _DB_PREFIX_ . "shop s ON (s.id_shop = c.id_shop)
            LEFT JOIN " . _DB_PREFIX_ . "gender g ON (g.id_gender = c.id_gender)
            LEFT JOIN " . _DB_PREFIX_ . "gender_lang gl ON (g.id_gender = gl.id_gender AND gl.id_lang = " . $this->getLanguageId() . ")
            LEFT JOIN " . _DB_PREFIX_ . "customer_group cg ON (cg.id_customer = c.id_customer)
            WHERE c.`id_shop` = " . $this->getShopId() . " AND c.`id_lang` = " . $this->getLanguageId() . "
            AND c.`email` NOT LIKE '%marketplace.amazon.%'
            AND c.`date_upd` > '" . $start_date . "' AND c.`date_upd` < '" . $end_date . "'
            ORDER BY c.`id_customer` asc
            LIMIT " . $this->offset . "," . $this->limit;

            $customers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

            $customers_map = [];

            foreach ($customers as $index => $customer) {
                $customer_id = $customer['id'];
                $address_id = Address::getFirstCustomerAddressId($customer_id);
                $country_object = $this->getCustomerAddress($customer_id, $address_id);
                if (count($country_object) === 1) {
                    $country_object = $country_object[0];
                }
                if(is_array($country_object)){
                    $customers[$index]['country'] = $country_object['country'];
                    $customers[$index]['country_iso'] = $country_object['country_iso'];
                }

                $customers[$index]['language'] = $language_iso;

                if(!$get_email){
                    $customers[$index]['email'] = '';
                    $customers[$index]['gender'] = '';
                    $customers[$index]['name'] = '';
                    $customers[$index]['lastname'] = '';
                }

                if($get_sub_status){
                    $customers[$index]['subscribed'] = $customers[$index]['subscribed'] == 1;
                    $customers[$index]['optin'] = $customers[$index]['optin'] == 1;
                } else {
                    unset($customers[$index]['subscribed']);
                    unset($customers[$index]['optin']);
                }

                if(!array_key_exists($customer_id, $customers_map)){
                  $customers_map[$customer_id] = $customer;
                } else {
                  foreach ($customer as $key => $value) {
                    if(array_key_exists($key, $customers_map[$customer_id]) && $customers_map[$customer_id][$key] != $value){
                      $first_value = $customers_map[$customer_id][$key];
                      if(!is_array($first_value)){
                        $customers_map[$customer_id][$key] = [$first_value];
                      }
                      if(is_array($value)){
                        $customers_map[$customer_id][$key] = array_merge($customers_map[$customer_id][$key], $value);
                      } else {
                        $customers_map[$customer_id][$key][] = $value;
                      }
                    }
                  }
                }
            }

            $customers = array_values($customers_map);

            if($get_sub_status && $get_email){
                if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
                    // Default newsletter table for ^1.7.0 is ps_emailsubscription
                    $query = new DbQuery();
                    $query->select('CONCAT(\'N\', e.`id`) AS `id`, e.`email`, e.`active` AS `subscribed`');
                    $query->from('emailsubscription', 'e');
                    $query->leftJoin('shop', 's', 's.id_shop = e.id_shop');
                    $query->leftJoin('lang', 'l', 'l.id_lang = e.id_lang');
                    $query->where('e.id_shop = ' . $this->getShopId() . ' AND e.id_lang = ' . $this->getLanguageId());
                    $non_customers = Db::getInstance()->executeS($query->build());
                }elseif(version_compare(_PS_VERSION_, '1.6.0', '>=')) {
                    // Default newsletter table for ^1.6.0 is ps_newsletter
                    $query = new DbQuery();
                    $query->select('CONCAT(\'N\', n.`id`) AS `id`, n.`email`, n.`active` AS `subscribed`');
                    $query->from('newsletter', 'n');
                    $query->leftJoin('shop', 's', 's.id_shop = n.id_shop');
                    $query->where('n.id_shop = ' . $this->getShopId());
                    $non_customers = Db::getInstance()->executeS($query->build());
                }
                if(!empty($non_customers)){
                    foreach ($non_customers as $index => $subscriber){
                        $non_customers[$index]['subscribed'] = $subscriber['subscribed'] == 1;
                    }
                    $customers = array_merge($customers, $non_customers);
                }
            }

            $this->logger->log('Fetched Customers', ['response' => $customers]);

            return $customers;

        } catch (Exception $e) {

            $this->logger->error('ERROR getJsonResponse', ['error' => $e->getMessage()]);

        }
    }

    /**
     * @param $id_customer
     * @param $id_address
     * @return array|void
     */
    public function getCustomerAddress($id_customer, $id_address = null)
    {

        $id_lang = $this->getLanguageId();
        $share_order = (bool) Context::getContext()->shop->getGroup()->share_order;

        $query = 'SELECT DISTINCT
                    a.`id_address` AS `id`, a.`alias`, a.`firstname`, a.`lastname`, a.`company`, a.`address1`,
                    a.`address2`, a.`postcode`, a.`city`, a.`id_state`, s.name AS state, s.`iso_code` AS state_iso,
                    a.`id_country`, cl.`name` AS country, co.`iso_code` AS country_iso, a.`other`, a.`phone`,
                    a.`phone_mobile`, a.`vat_number`, a.`dni`
                    FROM `' . _DB_PREFIX_ . 'address` a
                    LEFT JOIN `' . _DB_PREFIX_ . 'country` co ON (a.`id_country` = co.`id_country`)
                    LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (co.`id_country` = cl.`id_country`)
                    LEFT JOIN `' . _DB_PREFIX_ . 'state` s ON (s.`id_state` = a.`id_state`)
                    ' . ($share_order ? '' : Shop::addSqlAssociation('country', 'co')) . '
                    WHERE
                        `id_lang` = ' . (int) $id_lang . '
                        AND `id_customer` = ' . (int) $id_customer . '
                        AND a.`deleted` = 0
                        AND a.`active` = 1';

        if (null !== $id_address) {
            $query .= ' AND a.`id_address` = ' . (int) $id_address;
        }

        try {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        } catch (PrestaShopDatabaseException $e) {
            $this->logger->log('PrestaShopDatabaseException', ['error' => $e->getMessage()]);
        }
    }
}

