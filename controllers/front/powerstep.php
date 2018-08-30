<?php
class ClerkPowerstepModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $response = [];

        if ($id_product = (int)Tools::getValue('id_product')) {
            $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);
        }

        if (!Validate::isLoadedObject($product)) {
            $response = [
                'success' => false,
                'data' => 'Product not found'
            ];
        } else {
            $modal = $this->module->renderModal(
                $this->context->cart,
                Tools::getValue('id_product'),
                Tools::getValue('id_product_attribute')
            );


            $response = [
                'success' => true,
                'data' => $modal
            ];
//            $image = Image::getCover($id_product);
//
//            $templatesConfig = Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->context->language->id, null, $this->context->shop->id);
//            $templates = array_filter(explode(',', $templatesConfig));
//
//            $categories = $product->getCategories();
//            $category = reset($categories);
//
//            $this->context->smarty->assign(array(
//                'templates' => $templates,
//                'product' => $product,
//                'category' => $category,
//                'image' => $image,
//                'order_process' => Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order',
//                'continue' => $this->context->link->getProductLink($id_product, $product->link_rewrite)
//            ));
//
//            if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
//                $this->setTemplate('module:clerk/views/templates/front/powerstep17.tpl');
//            } else {
//                $this->setTemplate('powerstep.tpl');
//            }
//
//            return $this;
        }

        header('Content-Type: application/json');
        die(json_encode($response));
    }
}