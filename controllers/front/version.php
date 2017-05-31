<?php
require "ClerkAbstractFrontController.php";

class ClerkVersionModuleFrontController extends ClerkAbstractFrontController
{
    /**
     * Get response
     *
     * @return array
     */
    public function getJsonResponse()
    {
        $clerk = Module::getInstanceByName('clerk');

        $response = array(
            'platform' => 'PrestaShop',
            'version' => $clerk->version,
        );

        return $response;
    }
}