<?php
class ClerkSearchModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $query = Tools::getValue('search_query', '');

        $this->context->smarty->assign(array(
            'search_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_SEARCH_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),
            'search_query' => $query,
        ));

        if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
            $this->setTemplate('module:clerk/views/templates/front/search17.tpl');
        } else {
            $this->setTemplate('search.tpl');
        }
    }
}