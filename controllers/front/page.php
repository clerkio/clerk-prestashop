<?php
/**
 *  @author Clerk.io
 *  @copyright Copyright (c) 2017 Clerk.io
 *
 *  @license MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
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

require "ClerkAbstractFrontController.php";

class ClerkPageModuleFrontController extends ClerkAbstractFrontController
{
    /**
     * @var int
     */
    private $language_id;

    /**
     * @var int
     */
    private $shop_id;

    /**
     * @var
     */
    protected $logger;

    /**
     * @var
     */
    protected $url_base;


    /**
     * ClerkProductModuleFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        require_once (_PS_MODULE_DIR_. $this->module->name . '/controllers/admin/ClerkLogger.php');

        $context = Context::getContext();

        $this->shop_id = (Tools::getValue('clerk_shop_select')) ? (int)Tools::getValue('clerk_shop_select') : $context->shop->id;
        $this->language_id = (Tools::getValue('clerk_language_select')) ? (int)Tools::getValue('clerk_language_select') : $context->language->id;

        $this->logger = new ClerkLogger();

        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')

            $this->url_base = "https://".$_SERVER['HTTP_HOST'];

        else {

            $this->url_base = "https://".$_SERVER['HTTP_HOST'];

        }

        //Needed for PHP 5.3 support
        $context = $this->context;


        if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {



        }
        else {


        }

    }

    public function ValidatePage($Page) {

        foreach ($Page as $key => $content) {

            if (empty($content)) {

                return false;

            }

        }

        return true;

    }

    /**
     * Get response
     *
     * @return array
     */
    public function getJsonResponse()
    {
        try {
            header('User-Agent: ClerkExtensionBot Prestashop/v' ._PS_VERSION_. ' Clerk/v'.Module::getInstanceByName('clerk')->version. ' PHP/v'.phpversion());
            if (Configuration::get('CLERK_DATASYNC_INCLUDE_PAGES', $this->language_id, null, $this->shop_id) != '0') {

                $pages = CMS::getCMSPages($this->getLanguageId(), 1, true, $this->shop_id);

                $response = array();

                foreach ($pages as $page) {

                    $page_fields = explode(',', Configuration::get('CLERK_DATASYNC_PAGE_FIELDS', $this->language_id, null, $this->shop_id));

                    $item = [
                        'id' => $page['id_cms'],
                        'type' => 'cms page',
                        'url' => $this->context->link->getCMSLink($page['id_cms']),
                        'title' => $page['meta_title'],
                        'text' => $page['content']
                    ];

                    if (!$this->ValidatePage($item)) {

                        continue;

                    }

                    foreach ($page_fields as $page_field) {

                        $page_field = str_replace(' ','',$page_field);

                        if ($page_field) {

                            $item[$page_field] = $page[$page_field];

                        }

                    }

                    $response[] = $item;

                }

                $this->logger->log('Fetched Pages', ['response' => $response]);

                return $response;
            }
            else {

                return [];

            }

        } catch (Exception $e) {

            $this->logger->error('ERROR Pages getJsonResponse', ['error' => $e->getMessage()]);

        }

    }

    /**
     * Get default fields for products
     *
     * @return array
     */
    protected function getDefaultFields()
    {
        try {

            $default = array(
                'id',
                'name',
                'description',
                'price',
                'list_price',
                'image',
                'url',
                'categories',
                'brand',
                'sku',
                'on_sale',
                'qty',
                'in_stock'
            );

            //Get custom fields from configuration
            $fieldsConfig = Configuration::get('CLERK_DATASYNC_FIELDS', $this->getLanguageId(), null, $this->getShopId());

            $fields = explode(',', $fieldsConfig);

            return array_merge($default, $fields);

        } catch (Exception $e) {

            $this->logger->error('ERROR getDefaultFields', ['error' => $e->getMessage()]);

        }
    }

    private function getStockForProduct($product)
    {
        try {

            $id_product_attribute = isset($product['id_product_attribute']) ? $product['id_product_attribute'] : null;

            if (isset($this->stock[$product['id_product']][$id_product_attribute])) {
                return $this->stock[$product['id_product']][$id_product_attribute];
            }

            $availableQuantity = StockAvailable::getQuantityAvailableByProduct($product['id_product'], $id_product_attribute);

            $this->stock[$product['id_product']][$id_product_attribute] = $availableQuantity;

            return $this->stock[$product['id_product']][$id_product_attribute];

        } catch (Exception $e) {

            $this->logger->error('ERROR getStockForProduct', ['error' => $e->getMessage()]);

        }
    }
}
