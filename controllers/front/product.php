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
        $this->logger = new ClerkLogger();

        $this->addFieldHandler('on_sale', function ($product) {
            return (Product::getPriceStatic($product['id_product'], true) < Product::getPriceStatic($product['id_product'], true, null, 6, null, false, false));
        });

        //Needed for PHP 5.3 support
        $context = $this->context;

        $this->addFieldHandler('url', function ($product) use ($context) {
            return $context->link->getProductLink($product['id_product']);
        });

        $this->addFieldHandler('image', function ($product) use ($context) {
            $image = Image::getCover($product['id_product']);
            return $context->link->getImageLink($product['link_rewrite'], $image['id_image'], ImageType::getFormattedName('home'));
        });

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

            $this->logger->log('Fetching Products Started', []);
            /** @var ProductCore $product */
            $product = new Product();
            $products = $product->getProducts($this->getLanguageId(), $this->offset, $this->limit, $this->order_by, $this->order, false, true);

            $response = array();
            $fields = array_flip($this->fieldMap);

            foreach ($products as $product) {
                $item = array();
                foreach ($this->fields as $field) {
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

                $response[] = $item;
            }

            $this->logger->log('Fetched Products Done', ['response' => $response]);

            return $response;

        } catch (Exception $e) {

            $this->logger->error('ERROR Products getJsonResponse', ['error' => $e]);

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

            $this->logger->log('Fetch default fields', ['response' => $fields]);

            return array_merge($default, $fields);

        } catch (Exception $e) {

            $this->logger->error('ERROR getDefaultFields', ['error' => $e]);

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

            $this->logger->log('Fetch Product Stock', ['response' => $this->stock[$product['id_product']][$id_product_attribute]]);

            return $this->stock[$product['id_product']][$id_product_attribute];

        } catch (Exception $e) {

            $this->logger->error('ERROR getStockForProduct', ['error' => $e]);

        }
    }
}
