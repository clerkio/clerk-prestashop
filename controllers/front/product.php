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

class ClerkProductModuleFrontController extends ClerkAbstractFrontController
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
     * @var array
     */
    protected $fieldMap = array(
        'id_product' => 'id',
        'manufacturer_name' => 'brand',
        'reference' => 'sku',
    );

    protected $stock;

    /**
     * ClerkProductModuleFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        require_once (_PS_MODULE_DIR_. $this->module->name . '/controllers/admin/ClerkLogger.php');

        $context = Context::getContext();

        $this->shop_id = (!empty(Tools::getValue('clerk_shop_select'))) ? (int)Tools::getValue('clerk_shop_select') : $context->shop->id;
        $this->language_id = (!empty(Tools::getValue('clerk_language_select'))) ? (int)Tools::getValue('clerk_language_select') : $context->language->id;

        $this->logger = new ClerkLogger();

        $this->addFieldHandler('on_sale', function ($product) {
            return (Product::getPriceStatic($product['id_product'], true) < Product::getPriceStatic($product['id_product'], true, null, 6, null, false, false));
        });

        //Needed for PHP 5.3 support
        $context = $this->context;

        $this->addFieldHandler('url', function ($product) use ($context) {
            return $context->link->getProductLink($product['id_product']);
        });

        if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {

            $this->addFieldHandler('image', function ($product) use ($context) {
                $image = Image::getCover($product['id_product']);
                return $context->link->getImageLink($product['link_rewrite'], $image['id_image'], ImageType::getFormattedName('home'));
            });

        }
        else {

            $this->addFieldHandler('image', function ($product) use ($context) {
                $image = Image::getCover($product['id_product']);
                return $context->link->getImageLink($product['link_rewrite'], $image['id_image'], 'home_default');
            });

        }

        $this->addFieldHandler('price', function ($product) {
            return Product::getPriceStatic($product['id_product'], true);
        });

        $this->addFieldHandler('list_price', function ($product) {
            //Get price without reduction
            return Product::getPriceStatic($product['id_product'], true, null, 6, null, false, false);
        });

        $this->addFieldHandler('qty', function ($product) {
            return $this->getStockForProduct($product);
        });

        $this->addFieldHandler('stock', function ($product) {
            return $this->getStockForProduct($product);
        });

        $this->addFieldHandler('in_stock', function ($product) {
            return $this->getStockForProduct($product) > 0;
        });

        $this->addFieldHandler('categories', function ($product) {
            $categories = array();
            $categoriesFull = Product::getProductCategoriesFull($product['id_product']);

            foreach ($categoriesFull as $category) {
                $categories[] = (int)$category['id_category'];
            }

            return $categories;
        });
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
            /** @var ProductCore $product */
            $product = new Product();
            $products = $product->getProducts($this->getLanguageId(), $this->offset, $this->limit, $this->order_by, $this->order, false, true);

            $context = Context::getContext();

            $response = array();
            $fields = array_flip($this->fieldMap);

            foreach ($products as $product) {

                $productRaw = new Product ($product['id_product'], $context->language->id);

                $combinations = $productRaw->getAttributeCombinations((int)$context->language->id, true);

                $attributes = [];
                $variants = [];

                if (count($combinations) > 0) {

                    foreach ($combinations as $combination) {

                        if(isset($combination['reference']) && $combination['reference'] != '' && !in_array($combination['reference'], $variants)) {

                            array_push($variants, $combination['reference']);
                        } elseif (isset($combination['id_product_attribute']) && !in_array($combination['id_product_attribute'], $variants))  {
                            array_push($variants, $combination['id_product_attribute']);
                        }

                        if(!isset($attributes[$combination['group_name']])) {

                            $attributes[$combination['group_name']][] = $combination['attribute_name'];

                        } else {

                            if (!in_array($combination['attribute_name'], $attributes[$combination['group_name']])) {

                                $attributes[$combination['group_name']][] = $combination['attribute_name'];

                            }
                        }

                    }

                }

                $item = array();
                foreach ($this->fields as $field) {
                    $field = str_replace(' ','',$field);
                    if ($attributes && array_key_exists($field, $attributes)){

                        $item[$field] = $attributes[$field];

                    }
                    if (array_key_exists($field, array_flip($this->fieldMap))) {
                        $item[$field] = $product[$fields[$field]];
                    } elseif (isset($product[$field])) {
                        $item[$field] = $product[$field];
                    }

                    //Check if there's a fieldHandler assigned for this field
                    if (isset($this->fieldHandlers[$field])) {
                        $item[$field] = $this->fieldHandlers[$field]($product);
                    }
                }

                if (Configuration::get('CLERK_INCLUDE_VARIANT_REFERENCES', $this->language_id, null, $this->shop_id) == '1') {
                    if (!empty($variants)) {
                        $item['variants'] = $variants;
                    }
                }

                if (Configuration::get('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', $this->language_id, null, $this->shop_id) != '1') {
                    if ($item['qty'] <= 0) {
                        continue;
                    }
                }

                $response[] = $item;
            }

            $this->logger->log('Fetched Products', ['response' => $response]);

            return $response;

        } catch (Exception $e) {

            $this->logger->error('ERROR Products getJsonResponse', ['error' => $e->getMessage()]);

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
                'in_stock',
                'stock'
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
