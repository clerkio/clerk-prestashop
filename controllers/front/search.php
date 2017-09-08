<?php
class ClerkSearchModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $query = Tools::getValue('search_query');
        $this->context->smarty->assign(array(
            'search_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_SEARCH_TEMPLATE' . $this->getSuffix(), ''))),
            'search_query' => $query,
        ));

        $this->setTemplate('search.tpl');
    }

    /**
     * Get configuration suffix
     *
     * @return string
     */
    private function getSuffix()
    {
        return sprintf('_%s_%s', $this->context->shop->id, $this->context->language->id);
    }
}