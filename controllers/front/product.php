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

        $this->shop_id = (!empty(Tools::getValue('clerk_shop_select'))) ? (int)Tools::getValue('clerk_shop_select') : $this->getShopId();
        $this->language_id = (!empty(Tools::getValue('clerk_language_select'))) ? (int)Tools::getValue('clerk_language_select') : $this->getLanguageId();

        $this->logger = new ClerkLogger();

        $this->addFieldHandler('on_sale', function ($product) {
            return (Product::getPriceStatic($product['id_product'], true) < Product::getPriceStatic($product['id_product'], true, null, 6, null, false, false));
        });

        //Needed for PHP 5.3 support
        $context = $this->context;

        $this->addFieldHandler('url', function ($product) use ($context) {
            return $context->link->getProductLink($product['id_product'], null, null, null, $this->language_id);
        });

        if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {

            $this->addFieldHandler('image', function ($product) use ($context) {

                $productRaw = new Product ($product['id_product'], $this->language_id);

                $product_link_rewrite = $productRaw->link_rewrite;
                if(is_array($product_link_rewrite)){
                    if(array_key_exists($this->language_id, $product_link_rewrite)){
                        $product_link_rewrite = $product_link_rewrite[$this->language_id];
                    }
                }

                $image_type = Configuration::get('CLERK_IMAGE_SIZE', $this->language_id, null, $this->shop_id);
                $image = Image::getCover($product['id_product']);
                $image_path = $context->link->getImageLink($product_link_rewrite, $image['id_image'], ImageType::getFormattedName($image_type));
                $base_domain = explode('//', _PS_BASE_URL_)[1];
                $image_check = substr(explode($base_domain, $image_path)[1], 0, 2);
                if ('/-' === $image_check) {
                    $iso = Context::getContext()->language->iso_code;
                    $image_path = _PS_BASE_URL_ . '/img/p/' . $iso . '-default-'.$image_type.'_default.jpg';
                }
                return $image_path;
            });

            if (Configuration::get('CLERK_INCLUDE_VARIANT_REFERENCES', $this->language_id, null, $this->shop_id) == '1') {

                $this->addFieldHandler('variant_images', function ($product) use ($context) {

                    $productRaw = new Product ($product['id_product'], $this->language_id);

                    $product_link_rewrite = $productRaw->link_rewrite;
                    if(is_array($product_link_rewrite)){
                        if(array_key_exists($this->language_id, $product_link_rewrite)){
                            $product_link_rewrite = $product_link_rewrite[$this->language_id];
                        }
                    }

                    $image_type = Configuration::get('CLERK_IMAGE_SIZE', $this->language_id, null, $this->shop_id);
                    $id_list = [];
                    $variant_images = [];
                    $varArray = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                    SELECT i.`id_image` as id
                    FROM `' . _DB_PREFIX_ . 'image` i
                    ' . Shop::addSqlAssociation('image', 'i') . '
                    WHERE i.`id_product` = ' . (int) $product['id_product'] . '
                    ORDER BY i.`position`');
                    foreach ($varArray as $obj) {
                        foreach ($obj as $key => $value) {
                            array_push($id_list, $value);
                        }
                    }
                    foreach($id_list as $id){
                        $variant_image = $context->link->getImageLink($product_link_rewrite, $id, ImageType::getFormattedName($image_type));
                        array_push($variant_images, $variant_image);
                    }
                    return $variant_images;
                });

            }
        } else {

            $this->addFieldHandler('image', function ($product) use ($context) {
                $productRaw = new Product ($product['id_product'], $this->language_id);

                $product_link_rewrite = $productRaw->link_rewrite;
                if(is_array($product_link_rewrite)){
                    if(array_key_exists($this->language_id, $product_link_rewrite)){
                        $product_link_rewrite = $product_link_rewrite[$this->language_id];
                    }
                }

                $image_type = Configuration::get('CLERK_IMAGE_SIZE', $this->language_id, null, $this->shop_id) . '_default';
                $image = Image::getCover($product['id_product']);
                $image_path = $context->link->getImageLink($product_link_rewrite, $image['id_image'], $image_type);
                $base_domain = explode('//', _PS_BASE_URL_)[1];
                $image_check = substr(explode($base_domain, $image_path)[1], 0, 2);
                if ('/-' === $image_check) {
                    $iso = Context::getContext()->language->iso_code;
                    $image_path = _PS_BASE_URL_ . '/img/p/' . $iso . '-default-'.$image_type.'.jpg';
                }
                return $image_path;
            });

            if (Configuration::get('CLERK_INCLUDE_VARIANT_REFERENCES', $this->language_id, null, $this->shop_id) == '1') {

                $this->addFieldHandler('variant_images', function ($product) use ($context) {

                    $productRaw = new Product ($product['id_product'], $this->language_id);

                    $product_link_rewrite = $productRaw->link_rewrite;
                    if(is_array($product_link_rewrite)){
                        if(array_key_exists($this->language_id, $product_link_rewrite)){
                            $product_link_rewrite = $product_link_rewrite[$this->language_id];
                        }
                    }

                    $image_type = Configuration::get('CLERK_IMAGE_SIZE', $this->language_id, null, $this->shop_id) . '_default';
                    $id_list = [];
                    $variant_images = [];
                    $varArray = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                    SELECT i.`id_image` as id
                    FROM `' . _DB_PREFIX_ . 'image` i
                    ' . Shop::addSqlAssociation('image', 'i') . '
                    WHERE i.`id_product` = ' . (int) $product['id_product'] . '
                    ORDER BY i.`position`');
                    foreach ($varArray as $obj) {
                        foreach ($obj as $key => $value) {
                            array_push($id_list, $value);
                        }
                    }
                    foreach($id_list as $id){
                        $variant_image = $context->link->getImageLink($product_link_rewrite, $id, $image_type);
                        array_push($variant_images, $variant_image);
                    }
                    return $variant_images;
                });

            }
        }


        $this->addFieldHandler('price', function ($product) use ($context) {
            global $cookie;
            $default_currency = Currency::getDefaultCurrency();
            $current_currency = new CurrencyCore($cookie->id_currency);

            if (Configuration::get('CLERK_DATASYNC_CONTEXTUAL_VAT', $this->language_id, null, $this->shop_id) == '1') {
                if ($context === null) {
                    $context = Context::getContext();
                }

                $address = new Address();
                $address->id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT', $this->language_id, 0, 0);
                $address->id_state = 0;
                $address->postcode = 0;
                $tax_rate = Tax::getProductTaxRate((int) $product['id_product'], $address);
                $tax_rate = ($tax_rate / 100) + 1;
                $price_exc_tax = Product::getPriceStatic($product['id_product'], false);

                $price = $price_exc_tax * $tax_rate;
            } else {
                $price = Product::getPriceStatic($product['id_product'], true);
            }

            if($current_currency->iso_code !== $default_currency->iso_code){
                $price = ($price / (float) $current_currency->conversion_rate);
            }

            return $price;
        });

        $this->addFieldHandler('list_price', function ($product) use ($context) {
            global $cookie;
            $default_currency = Currency::getDefaultCurrency();
            $current_currency = new CurrencyCore($cookie->id_currency);

            if (Configuration::get('CLERK_DATASYNC_CONTEXTUAL_VAT', $this->language_id, null, $this->shop_id) == '1') {
                if ($context === null) {
                    $context = Context::getContext();
                }

                $address = new Address();
                $address->id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT', $this->language_id, 0, 0);
                $address->id_state = 0;
                $address->postcode = 0;
                $tax_rate = Tax::getProductTaxRate((int) $product['id_product'], $address);
                $tax_rate = ($tax_rate / 100) + 1;
                $price_exc_tax = Product::getPriceStatic($product['id_product'], false, null, 6, null, false, false);

                $price = $price_exc_tax * $tax_rate;
            } else {
                $price = Product::getPriceStatic($product['id_product'], true, null, 6, null, false, false);
            }

            if($current_currency->iso_code !== $default_currency->iso_code){
                $price = ($price / (float) $current_currency->conversion_rate);
            }

            return $price;
        });

        $this->addFieldHandler('date_add', function ($product) {
            return strtotime($product['date_add']);
        });

        $this->addFieldHandler('qty', function ($product) {
            if (Configuration::get('CLERK_DATASYNC_QUERY_BY_STOCK', $this->language_id, null, $this->shop_id) == '1') {
                return (int)$product['quantity'];
            } else {
                return $this->getStockForProduct($product);
            }
        });

        $this->addFieldHandler('stock', function ($product) {
            if (Configuration::get('CLERK_DATASYNC_QUERY_BY_STOCK', $this->language_id, null, $this->shop_id) == '1') {
                return (int)$product['quantity'];
            } else {
                return $this->getStockForProduct($product);
            }
        });

        $this->addFieldHandler('supplier', function ($product) {
            $product_all_suppliers = ProductSupplier::getSupplierCollection($product['id_product'], true);

            $suppliers = $product_all_suppliers->getResults();
            $supplier_names = array();
            foreach($suppliers as $s){
                    $s_name = Supplier::getNameById($s->id_supplier);
                    array_push($supplier_names, $s_name);
            }
            return $supplier_names;
        });

        $this->addFieldHandler('description', function ($product) {
            $productRaw = new Product ($product['id_product'], $this->language_id);

            $product_desc = $productRaw->description_short;

            if(is_array($product_desc)){
                if(array_key_exists($this->language_id, $product_desc)){
                    $product_desc = $product_desc[$this->language_id];
                }
            }

            if($product_desc === ''){
                $product_desc = $productRaw->description;
            }

            if(is_array($product_desc)){
                if(array_key_exists($this->language_id, $product_desc)){
                    $product_desc = $product_desc[$this->language_id];
                }
            }

            return trim(strip_tags($product_desc));
        });

        $this->addFieldHandler('description_long', function ($product) {
            $productRaw = new Product ($product['id_product'], $this->language_id);

            $product_desc = $productRaw->description;

            if(is_array($product_desc)){
                if(array_key_exists($this->language_id, $product_desc)){
                    $product_desc = $product_desc[$this->language_id];
                }
            }

            return trim(strip_tags($product_desc));
        });

        $this->addFieldHandler('in_stock', function ($product) {
            if (Configuration::get('CLERK_DATASYNC_QUERY_BY_STOCK', $this->language_id, null, $this->shop_id) == '1') {
                return $product['quantity'] > 0;
            } else {
                return $this->getStockForProduct($product) > 0;
            }
        });

        $this->addFieldHandler('name', function ($product) {
            $productRaw = new Product ($product['id_product'], $this->language_id);
            $product_name = $productRaw->name;
            if(is_array($product_name)){
                if(array_key_exists($this->language_id, $product_name)){
                    $product_name = $product_name[$this->language_id];
                }
            }

            return $product_name;
        });

        $this->addFieldHandler('category_names', function ($product) {
            $category_names = array();
            $categoriesFull = Product::getProductCategoriesFull($product['id_product']);

            foreach ($categoriesFull as $category) {
                if(array_key_exists('name', $category)){
                    $category_names[] = $category['name'];
                }
            }

            return $category_names;
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

            $active = ' AND ((ps.active IS NULL AND p.active = 1) OR (p.active IS NULL AND ps.active = 1) OR (p.active = 1 AND ps.active = 1))';

            if (Configuration::get('CLERK_DATASYNC_INCLUDE_ONLY_LOCAL_STOCK', $this->language_id, null, $this->shop_id) == '1') {
                $active .= ' AND ((ps.available_for_order IS NULL AND p.available_for_order = 1) OR (p.available_for_order IS NULL AND ps.available_for_order = 1) OR (p.available_for_order = 1 AND ps.available_for_order = 1))';
            }

            if (Configuration::get('CLERK_DATASYNC_QUERY_BY_STOCK', $this->language_id, null, $this->shop_id) == '1') {

                /* Heavier quantity sorted query ensures no intermitent empty pages are returned */

                $sql = "SELECT p.id_product, p.reference, m.name as 'manufacturer_name', pl.link_rewrite, p.date_add,
                pl.description, pl.description_short, pl.name, p.visibility, psa.quantity as 'quantity',
                ps.active as 'shop_active', p.active as 'product_active',
                ps.available_for_order as 'shop_available', p.available_for_order as 'product_available'
                FROM "._DB_PREFIX_."product p
                LEFT JOIN "._DB_PREFIX_."product_lang pl ON (p.id_product = pl.id_product)
                LEFT JOIN "._DB_PREFIX_."category_product cp ON (p.id_product = cp.id_product)
                LEFT JOIN "._DB_PREFIX_."category_lang cl ON (cp.id_category = cl.id_category)
                LEFT JOIN "._DB_PREFIX_."manufacturer m ON (p.id_manufacturer = m.id_manufacturer)
                LEFT JOIN "._DB_PREFIX_."stock_available psa ON (p.id_product = psa.id_product)
                LEFT JOIN "._DB_PREFIX_."product_shop ps ON (p.id_product = ps.id_product)
                WHERE pl.id_lang = ". $language_id ." AND cl.id_lang = ". $language_id ."
                AND pl.id_shop = " . $shop_id . " AND cl.id_shop = ". $shop_id ."
                AND ps.id_shop = " . $shop_id . $active . "
                GROUP BY p.id_product
                ORDER BY quantity desc
                LIMIT ".$offset.",".$limit;

            } else {

                /* Lighter query sorted by id_product is Default */

                $sql = "SELECT p.id_product, p.reference, m.name as 'manufacturer_name', pl.link_rewrite, p.date_add,
                pl.description, pl.description_short, pl.name, p.visibility,
                ps.active as 'shop_active', p.active as 'product_active',
                ps.available_for_order as 'shop_available', p.available_for_order as 'product_available'
                FROM "._DB_PREFIX_."product p
                LEFT JOIN "._DB_PREFIX_."product_lang pl ON (p.id_product = pl.id_product)
                LEFT JOIN "._DB_PREFIX_."category_product cp ON (p.id_product = cp.id_product)
                LEFT JOIN "._DB_PREFIX_."category_lang cl ON (cp.id_category = cl.id_category)
                LEFT JOIN "._DB_PREFIX_."manufacturer m ON (p.id_manufacturer = m.id_manufacturer)
                LEFT JOIN "._DB_PREFIX_."product_shop ps ON (p.id_product = ps.id_product)
                WHERE pl.id_lang = ". $language_id ." AND cl.id_lang = ". $language_id ."
                AND pl.id_shop = " . $shop_id . " AND cl.id_shop = ". $shop_id ."
                AND ps.id_shop = " . $shop_id . $active . "
                GROUP BY p.id_product
                ORDER BY p.id_product asc
                LIMIT ".$offset.",".$limit;

            }

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
                $attribute_ids = [];
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

                            $attribute_ids[$setGroupfield][] = $combination['id_attribute'];
                            $attributes[$setGroupfield][] = $combination['attribute_name'];

                        } else {

                            if (!in_array($combination['attribute_name'], $attributes[$setGroupfield])) {

                                $attribute_ids[$setGroupfield][] = $combination['id_attribute'];
                                $attributes[$setGroupfield][] = $combination['attribute_name'];

                            }
                        }

                    }

                }

                $item = array();
                foreach ($this->fields as $field) {
                    $field = str_replace(' ','',$field);
                    if ($attributes && array_key_exists($field, $attributes)){
                        $item[$field . "_ids"] = $attribute_ids[$field];
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
                if (Configuration::get('CLERK_DATASYNC_PRODUCT_FEATURES', $this->language_id, null, $this->shop_id) == '1') {

                    $frontfeatures = Product::getFrontFeaturesStatic($this->language_id, $product['id_product']);

                    if( !empty( $frontfeatures ) ){
                        if( count($frontfeatures) > 0 ){
                            $features_object = array();
                            foreach($frontfeatures as $feature){
                                if( isset($feature['name']) ){
                                    $feature['name'] = str_replace( array(' ', '-'), '_', $feature['name'] );
                                    if( ! array_key_exists( $feature['name'], $features_object) ){
                                        $features_object[$feature['name']] = array();
                                        array_push($features_object[$feature['name']], $feature['value']);
                                    } else {
                                        array_push($features_object[$feature['name']], $feature['value']);
                                    }
                                }
                            }
                            foreach($features_object as $key => $value){
                                if(count($value) === 0){
                                    $value = "";
                                }
                                if(count($value) === 1){
                                    $value = $value[0];
                                }
                                $item[preg_replace('/([^0-9a-zA-Z_]+)/', '', $key)] = $value;
                            }
                        }
                    }
                }

                if (Configuration::get('CLERK_DATASYNC_INCLUDE_ONLY_LOCAL_STOCK', $this->language_id, null, $this->shop_id) == '1' && Configuration::get('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', $this->language_id, null, $this->shop_id) != '1') {
                    if($item['stock'] <= 0){
                        continue;
                    }
                }

                if(Configuration::get('CLERK_DATASYNC_INCLUDE_ONLY_LOCAL_STOCK', $this->language_id, null, $this->shop_id) == '1'){
                    if($productRaw->out_of_stock != '1' && $item['stock'] <= 0){
                        continue;
                    }
                }

                // Add Specific price to product data if present
                $specific_prices = SpecificPrice::getByProductId($product['id_product']);
                if( ! empty($specific_prices) && $productRaw->base_price ){
                    foreach($specific_prices as $sp_price){
                        if($sp_price['reduction_type'] == 'percentage'){
                            $tmp_tax = ($productRaw->tax_rate / 100 + 1);
                            $tmp_price = ($productRaw->base_price * $tmp_tax);
                            $reduction = 1 - $sp_price['reduction'];
                            $tmp_price = $tmp_price * $reduction;
                        }
                        if($sp_price['reduction_type'] == 'amount'){
                            $tmp_tax = ($sp_price['reduction_tax'] * ($productRaw->tax_rate / 100)) + 1;
                            $tmp_price = ($productRaw->base_price * $tmp_tax);
                            $tmp_price = $tmp_price - $sp_price['reduction'];
                        }
                        if(is_numeric($tmp_price)){
                            $item['customer_group_price_' . $sp_price['id_group']] = $tmp_price;
                        }
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
                'brand',
                'categories',
                'category_names',
                'date_add',
                'description',
                'id',
                'image',
                'in_stock',
                'list_price',
                'name',
                'on_sale',
                'price',
                'qty',
                'sku',
                'stock',
                'url'
            );

            if (Configuration::get('CLERK_INCLUDE_VARIANT_REFERENCES', $this->language_id, null, $this->shop_id) == '1') {
                array_push($default, 'variant_images');
            }

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
