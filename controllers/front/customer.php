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
            $customers = Customer::getCustomers(true);

            foreach ($customers as $index => $customer) {
                //Rename id_customer to id and prepend to response
                $customers[$index] = array_merge(['id' => $customer['id_customer']], $customers[$index]);
                unset($customers[$index]['id_customer']);
            }

            $this->logger->log('Fetched Customers', ['response' => $customers]);

            return $customers;

        } catch (Exception $e) {

            $this->logger->error('ERROR getJsonResponse', ['error' => $e->getMessage()]);

        }
    }
}
