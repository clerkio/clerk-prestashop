<?php
require "ClerkAbstractFrontController.php";

class ClerkCustomerModuleFrontController extends ClerkAbstractFrontController
{
    /**
     * Get response
     *
     * @return array
     */
    public function getJsonResponse()
    {
        $customers = Customer::getCustomers(true);

        foreach ($customers as $index => $customer) {
            //Rename id_customer to id and prepend to response
            $customers[$index] = array_merge(['id' => $customer['id_customer']], $customers[$index]);
            unset($customers[$index]['id_customer']);
        }

        return $customers;
    }
}