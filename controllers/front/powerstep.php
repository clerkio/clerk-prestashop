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

class ClerkPowerstepModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $response = [];

        $popup = (int)Tools::getValue('popup');

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

            $image = Image::getCover($id_product);

            $templatesConfig = Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->context->language->id, null, $this->context->shop->id);
            $templates = array_filter(explode(',', $templatesConfig));

            foreach ($templates as $key => $template) {

                $templates[$key] = str_replace(' ','', $template);

            }

            $exclude_duplicates_powerstep = (bool)Configuration::get('CLERK_POWERSTEP_EXCLUDE_DUPLICATES', $this->context->language->id, null, $this->context->shop->id);

            $categories = $product->getCategories();
            $category = reset($categories);

            $this->context->smarty->assign(array(
                'templates' => $templates,
                'product' => $product,
                'category' => $category,
                'image' => $image,
                'order_process' => Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order',
                'continue' => $this->context->link->getProductLink($id_product, $product->link_rewrite),
                'popup' => (int)Tools::getValue('popup'),
                'unix' => time(),
                'ExcludeDuplicates' => $exclude_duplicates_powerstep 
            ));

            if ($popup == 1) {

                $this->setTemplate('module:clerk/views/templates/front/powerstepmodal.tpl');

            } else {

                if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
                    $this->setTemplate('module:clerk/views/templates/front/powerstep17.tpl');
                } else {
                    $this->setTemplate('powerstep.tpl');
                }

            }

            return $this;
        }

        header('Content-Type: application/json');
        die(json_encode($response));
    }
}
