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
            'platform' => sprintf('PrestaShop %s', _PS_VERSION_),
            'version' => $clerk->version,
        );

        return $response;
    }
}