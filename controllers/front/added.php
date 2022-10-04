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

class ClerkAddedModuleFrontController extends ModuleFrontController
{
    /**
     * @var ClerkLogger
     */
    protected $logger;

    /**
     * ClerkAddedModuleFrontController constructor.
     */
    public function __construct()
    {
        require_once (_PS_MODULE_DIR_. 'clerk/controllers/admin/ClerkLogger.php');
        $this->logger = new ClerkLogger();
        parent::__construct();
    }

    /**
     * @return $this|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initContent()
    {

        parent::initContent();

        if ($id_product = (int)Tools::getValue('id_product')) {
            $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);
        }

        if (!Validate::isLoadedObject($product)) {
            Tools::redirect('index.php');
        }

        $exclude_duplicates_powerstep = (bool)Configuration::get('CLERK_POWERSTEP_EXCLUDE_DUPLICATES', $this->context->language->id, null, $this->context->shop->id);

        $image = Image::getCover($id_product);

        $templatesConfig = Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->context->language->id, null, $this->context->shop->id);
        $templates = array_filter(explode(',', $templatesConfig));

        $categories = $product->getCategories();
        $category = reset($categories);

        $this->context->smarty->assign(array(
            'templates' => $templates,
            'product' => $product,
            'category' => $category,
            'image' => $image,
            'order_process' => Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order',
            'continue' => $this->context->link->getProductLink($id_product, $product->link_rewrite),
            'unix' => time(),
            'template_count' => count($templates),
            'popup' => 0,
            'ExcludeDuplicates' => $exclude_duplicates_powerstep
        ));

        if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
            $this->setTemplate('module:clerk/views/templates/front/powerstep17.tpl');
        } else {
            $this->setTemplate('powerstep.tpl');
        }

        return $this;
    }

    /**
     * Add css to powerstep
     */
    public function setMedia()
    {

        try {

            parent::setMedia();
            $this->addCSS(_MODULE_DIR_ . $this->module->name . '/views/css/powerstep.css');
            $this->logger->log('Added Powerstep.css to the frontend', ['response' => '']);

        } catch (Exception $e) {

            $this->logger->error('ERROR setMedia', ['error' => $e->getMessage()]);

        }

    }
}
