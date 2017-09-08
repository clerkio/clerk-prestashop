<?php
class ClerkAddedModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        if ($id_product = (int)Tools::getValue('id_product')) {
            $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);
        }

        if (!Validate::isLoadedObject($product)) {
            Tools::redirect('index.php');
        }

        $image = Image::getCover($id_product);

        $suffix = $this->getSuffix();

        $templatesConfig = Configuration::get('CLERK_POWERSTEP_TEMPLATES' . $suffix);
        $templates = array_filter(explode(',', $templatesConfig));

        $this->context->smarty->assign(array(
            'templates' => $templates,
            'product' => $product,
            'category' => reset($product->getCategories()),
            'image' => $image,
            'order_process' => Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order',
            'continue' => $this->context->link->getProductLink($id_product, $product->link_rewrite)
        ));

        $this->setTemplate('powerstep.tpl');
    }

    /**
     * Add css to powerstep
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS(_MODULE_DIR_.$this->module->name.'/views/css/powerstep.css');
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