<?php
class OrderController extends OrderControllerCore
{
    /**
     * Redirect if ipa parameter is set, indicating that the user was redirected to cart from product add
     */
    public function postProcess()
    {
        parent::postProcess();

        if (Tools::getValue('ipa') && Configuration::get('CLERK_POWERSTEP_ENABLED')) {
            $url = $this->context->link->getModuleLink('clerk', 'added', ['id_product' => Tools::getValue('ipa')]);
            Tools::redirect($url);
        }
    }
}