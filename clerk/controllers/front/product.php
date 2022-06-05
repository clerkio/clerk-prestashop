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

        $this->addFieldHandler('date_add', function ($product) {
            return strtotime($product['date_add']);
        });

        $this->addFieldHandler('list_price', function ($product) {
            //Get price without reduction
            return Product::getPriceStatic($product['id_product'], true, null, 6, null, false, false);
        });

        $this->addFieldHandler('qty', function ($product) {
            //return var_dump($product);
            return $this->getStockForProduct($product);
        });

        $this->addFieldHandler('stock', function ($product) {
            return $this->getStockForProduct($product);
        });

        $this->addFieldHandler('description', function ($product) {
            $description = ($product['description_short'] != '') ? trim(strip_tags($product['description_short'])) : trim(strip_tags($product['description']));
            return $description;
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
            $language_id = $this->getLanguageId();
            $shop_id = $this->getShopId();
            $offset = $this->offset;
            $limit = $this->limit;
            
            //$products = $product->getProducts($this->getLanguageId(), $this->offset, $this->limit, $this->order_by, $this->order, false, true);
            
            $context = Context::getContext();

            /* Get Products SQL in order to get the overselling parameter, in addition to the normal values. */

            $active = ' AND active = 1 AND available_for_order = 1';

            if (Configuration::get('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', $this->language_id, null, $this->shop_id) != '1') {
                $active = '';
            }

            $sql = "SELECT p.id_product, p.reference, m.name as 'manufacturer_name', pl.link_rewrite, p.date_add, pl.description, pl.description_short, pl.name
            FROM "._DB_PREFIX_."product p
            LEFT JOIN "._DB_PREFIX_."product_lang pl ON (p.id_product = pl.id_product)
            LEFT JOIN "._DB_PREFIX_."category_product cp ON (p.id_product = cp.id_product)
            LEFT JOIN "._DB_PREFIX_."category_lang cl ON (cp.id_category = cl.id_category)
            LEFT JOIN "._DB_PREFIX_."manufacturer m ON (p.id_manufacturer = m.id_manufacturer)
            WHERE pl.id_lang = ".$language_id." AND cl.id_lang = ".$language_id.$active."
            GROUP BY p.id_product
            ORDER BY p.id_product asc
            LIMIT ".$offset.",".$limit;

            $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

            /* Get Products SQL in order to get the overselling parameter, in addition to the normal values. */

            $response = array();
            $fields = array_flip($this->fieldMap);
            $fieldsConfig = Configuration::get('CLERK_DATASYNC_FIELDS', $this->getLanguageId(), null, $this->getShopId());
            $customFields = explode(',', $fieldsConfig);
            $attriarr = [];

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

                        $setGroupfield = str_replace(' ','',$combination['group_name']);

                        if(!isset($attributes[$setGroupfield])) {

                            $attributes[$setGroupfield][] = $combination['attribute_name'];

                        } else {

                            if (!in_array($combination['attribute_name'], $attributes[$setGroupfield])) {

                                $attributes[$setGroupfield][] = $combination['attribute_name'];

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
                        if ($field == 'date_add') {
                            $item['created_at'] = $this->fieldHandlers[$field]($product);
                        }
                        else {
                            $item[$field] = $this->fieldHandlers[$field]($product);
                        }
                    }
                }

                if(Pack::isPack($product['id_product'])){
                    foreach($customFields as $_field){

                        if (empty($attriarr)) {
                            $attriarr = Attribute::getAttributes($this->language_id, true);
                        };

                        $childatributes = [];
                        $children = Pack::getItems($product['id_product'], $this->language_id);

                        foreach ($children as $child) {
                            if (isset($child->id_pack_product_attribute)) {
                                $combination = new Combination($child->id_pack_product_attribute);
                                $combarr = $combination->getAttributesName($this->language_id);

                                foreach ($combarr as $comb) {
                                    foreach ($attriarr as $attri) {
                                        if ($attri['id_attribute'] === $comb['id_attribute'] ){
                                            if(str_replace(' ','',$attri['public_name']) == str_replace(' ','',$_field)){
                                                $childatributes[] = $attri['name'];
                                            }

                                        }
                                    }
                                }
                            }

                            if ($attributes && array_key_exists($_field, $attributes)){
                                $childatributes[$_field] = $attributes[$_field];
                            }

                           if (isset($child->$_field)) {
                                $childatributes[] = $child->$_field;
                            }

                        }

                        if(!empty($childatributes)){
                            $item['child_'.$_field.'s'] = $childatributes;
                        }

                    }

                }

                if (Configuration::get('CLERK_INCLUDE_VARIANT_REFERENCES', $this->language_id, null, $this->shop_id) == '1') {
                    if (!empty($variants)) {
                        $item['variants'] = $variants;
                    }
                }

                // Adding Product Features
                if (Configuration::get('CLERK_DATASYNC_PRODUCT_FEATURES', $this->language_id, null, $this->shop_id) != '1') {

                    $frontfeatures = Product::getFrontFeaturesStatic($this->language_id, $product['id_product']);
                    
                    foreach($frontfeatures as $ftr){

                        $item[$ftr['name']] = $ftr['value'];
                        
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
                'stock',
                'date_add'
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
