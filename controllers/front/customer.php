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

        $context = Context::getContext();

        $this->logger = new ClerkLogger();

        //Needed for PHP 5.3 support
        $context = $this->context;

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
                return array();
            }

            $get_sub_status = false;
            if (Configuration::get('CLERK_DATASYNC_SYNC_SUBSCRIBERS',  $this->getLanguageId(), null, $this->getShopId()) == '1') {
                $get_sub_status = true;
            }

            $get_email = false;
            if (Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS',  $this->getLanguageId(), null, $this->getShopId()) == '1') {
                $get_email = true;
            }

            $language_iso = Language::getIsoById($this->getLanguageId()) ? strtoupper(Language::getIsoById($this->getLanguageId())) : null;

            $sql = "SELECT c.`id_customer` AS `id`, gl.`name` AS `gender`, c.`lastname`, c.`firstname`, c.`email`, c.`newsletter` AS `subscribed`, c.`optin`
            FROM " . _DB_PREFIX_ . "customer c
            LEFT JOIN " . _DB_PREFIX_ . "shop s ON (s.id_shop = c.id_shop)
            LEFT JOIN " . _DB_PREFIX_ . "gender g ON (g.id_gender = c.id_gender)
            LEFT JOIN " . _DB_PREFIX_ . "gender_lang gl ON (g.id_gender = gl.id_gender AND gl.id_lang = " . $this->getLanguageId() . ")
            WHERE c.`id_shop` = " . $this->getShopId() . " AND c.`id_lang` = " . $this->getLanguageId() . "
            ORDER BY c.`id_customer` asc
            LIMIT " . $this->offset . "," . $this->limit;

            $customers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

            foreach ($customers as $index => $customer) {
                $customer_id = $customer['id'];
                $address_id = Address::getFirstCustomerAddressId($customer_id);
                $country_object = $this->getCustomerAddress($address_id, $customer_id);
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
                    $customers[$index]['subscribed'] = ($customers[$index]['subscribed'] == 1) ? true : false;
                    $customers[$index]['optin'] = ($customers[$index]['optin'] == 1) ? true : false;
                } else {
                    unset($customers[$index]['subscribed']);
                    unset($customers[$index]['optin']);
                }
            }

            if($get_sub_status && $get_email){
                if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
                    // Default newletter table for ^1.7.0 is ps_emailsubscription
                    $dbquery = new DbQuery();
                    $dbquery->select('CONCAT(\'N\', e.`id`) AS `id`, e.`email`, e.`active` AS `subscribed`');
                    $dbquery->from('emailsubscription', 'e');
                    $dbquery->leftJoin('shop', 's', 's.id_shop = e.id_shop');
                    $dbquery->leftJoin('lang', 'l', 'l.id_lang = e.id_lang');
                    $dbquery->where('e.id_shop = ' . $this->getShopId() . ' AND e.id_lang = ' . $this->getLanguageId());
                    $non_customers = Db::getInstance()->executeS($dbquery->build());
                } else {
                    // Default newletter table for ^1.6.0 is ps_newsletter
                    $dbquery = new DbQuery();
                    $dbquery->select('CONCAT(\'N\', n.`id`) AS `id`, n.`email`, n.`active` AS `subscribed`');
                    $dbquery->from('newsletter', 'n');
                    $dbquery->leftJoin('shop', 's', 's.id_shop = n.id_shop');
                    $dbquery->where('n.id_shop = ' . $this->getShopId());
                    $non_customers = Db::getInstance()->executeS($dbquery->build());
                }
                foreach ($non_customers as $index => $subscriber){
                    $non_customers[$index]['subscribed'] = ($non_customers[$index]['subscribed'] == 1) ? true : false;
                }
                $customers = array_merge($customers, $non_customers);
            }

            $this->logger->log('Fetched Customers', ['response' => $customers]);

            return $customers;

        } catch (Exception $e) {

            $this->logger->error('ERROR getJsonResponse', ['error' => $e->getMessage()]);

        }
    }

    public function getCustomerAddress($idAddress = null, $idCustomer)
    {

        $idLang = $this->getLanguageId();
        $shareOrder = (bool) Context::getContext()->shop->getGroup()->share_order;

        $csql = 'SELECT DISTINCT
                    a.`id_address` AS `id`,
                    a.`alias`,
                    a.`firstname`,
                    a.`lastname`,
                    a.`company`,
                    a.`address1`,
                    a.`address2`,
                    a.`postcode`,
                    a.`city`,
                    a.`id_state`,
                    s.name AS state,
                    s.`iso_code` AS state_iso,
                    a.`id_country`,
                    cl.`name` AS country,
                    co.`iso_code` AS country_iso,
                    a.`other`,
                    a.`phone`,
                    a.`phone_mobile`,
                    a.`vat_number`,
                    a.`dni`
                    FROM `' . _DB_PREFIX_ . 'address` a
                    LEFT JOIN `' . _DB_PREFIX_ . 'country` co ON (a.`id_country` = co.`id_country`)
                    LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (co.`id_country` = cl.`id_country`)
                    LEFT JOIN `' . _DB_PREFIX_ . 'state` s ON (s.`id_state` = a.`id_state`)
                    ' . ($shareOrder ? '' : Shop::addSqlAssociation('country', 'co')) . '
                    WHERE
                        `id_lang` = ' . (int) $idLang . '
                        AND `id_customer` = ' . (int) $idCustomer . '
                        AND a.`deleted` = 0
                        AND a.`active` = 1';

        if (null !== $idAddress) {
            $csql .= ' AND a.`id_address` = ' . (int) $idAddress;
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($csql);

        return $result;
    }


}