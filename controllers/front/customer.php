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
            /*
            foreach ($customers as $index => $customer) {
                //Rename id_customer to id and prepend to response
                $customers[$index] = array_merge(['id' => $customer['id_customer']], $customers[$index]);
                unset($customers[$index]['id_customer']);
            }
            */
            $get_sub_status = false;
            if (Configuration::get('CLERK_DATASYNC_SYNC_SUBSCRIBERS', $this->language_id, null, $this->shop_id) == '1') {
                $get_sub_status = true;
            }
			$dbquery = new DbQuery();
			$dbquery->select('c.`id_customer` AS `id`, s.`name` AS `shop_name`, gl.`name` AS `gender`, c.`lastname`, c.`firstname`, c.`email`, c.`newsletter` AS `subscribed`, c.`optin`');
			$dbquery->from('customer', 'c');
			$dbquery->leftJoin('shop', 's', 's.id_shop = c.id_shop');
			$dbquery->leftJoin('gender', 'g', 'g.id_gender = c.id_gender');
			$dbquery->leftJoin('gender_lang', 'gl', 'g.id_gender = gl.id_gender AND gl.id_lang = '.$this->getLanguageId());
			$customers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($dbquery->build());
            foreach ($customers as $index => $customer) {
                unset($customers[$index]['shop_name']);
                if($get_sub_status){
                    $customers[$index]['subscribed'] = ($customers[$index]['subscribed'] == 1) ? true : false;
                    $customers[$index]['optin'] = ($customers[$index]['optin'] == 1) ? true : false;
                }
            }

            $this->logger->log('Fetched Customers', ['response' => $customers]);

            return $customers;

        } catch (Exception $e) {

            $this->logger->error('ERROR getJsonResponse', ['error' => $e->getMessage()]);

        }
    }
}
