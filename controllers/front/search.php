<?php
class ClerkSearchModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $query = Tools::getValue('search_query');
        $this->context->smarty->assign(array(
            'search_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_SEARCH_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),
            'search_query' => $query,
        ));

        $this->setTemplate('search.tpl');
    }
}