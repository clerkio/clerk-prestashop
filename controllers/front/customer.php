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
            header('User-Agent: ClerkExtensionBot Prestashop/v' ._PS_VERSION_. ' Clerk/v'.Module::getInstanceByName('clerk')->version. ' PHP/v'.phpversion());
            //$customers = Customer::getCustomers($this->getLanguageId(), $this->offset, $this->limit, $this->order_by, $this->order, false, true);


            $sql = "SELECT c.`id_customer` AS `id`, s.`name` AS `shop_name`, gl.`name` AS `gender`, c.`lastname`, c.`firstname`, c.`email`, c.`newsletter` AS `subscribed`, c.`optin`
            FROM "._DB_PREFIX_."customer c
            LEFT JOIN "._DB_PREFIX_."shop s ON (s.id_shop = c.id_shop)
            LEFT JOIN "._DB_PREFIX_."gender g ON (g.id_gender = c.id_gender)
            LEFT JOIN "._DB_PREFIX_."gender_lang gl ON (g.id_gender = gl.id_gender AND gl.id_lang = ".$this->getLanguageId().")
            ORDER BY c.`id_customer` asc
            LIMIT ".$this->offset.",".$this->limit;
            
			$customers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            foreach ($customers as $index => $customer) {
                $customer_id = $customer['id'];
                $address_id = Address::getFirstCustomerAddressId($customer_id);
                $country_object = $this->getCustomerAddress($address_id, $customer_id);
                if (count($country_object) === 1) {
                    $country_object = $country_object[0];
                }
                $customers[$index]['country'] = $country_object['country'];
                $customers[$index]['country_iso'] = $country_object['country_iso'];
                unset($customers[$index]['shop_name']);
                $customers[$index]['subscribed'] = ($customers[$index]['subscribed'] == 1) ? true : false;
                $customers[$index]['optin'] = ($customers[$index]['optin'] == 1) ? true : false;
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
		$shareOrder = (bool)Context::getContext()->shop->getGroup()->share_order;

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
                        `id_lang` = ' . (int)$idLang . '
                        AND `id_customer` = ' . (int)$idCustomer . '
                        AND a.`deleted` = 0
                        AND a.`active` = 1';

		if (null !== $idAddress) {
			$csql .= ' AND a.`id_address` = ' . (int)$idAddress;
		}

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($csql);

		return $result;
	}

}
