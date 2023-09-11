<?php

class Clerk_Api
{
    /**
     * @var string
     */
    protected $baseurl = 'https://api.clerk.io/v2/';
    protected $logger;

    /**
     * @var int
     */
    private $language_id;

    /**
     * @var int
     */
    private $shop_id;

    public function __construct()
    {
        $context = Context::getContext();

        $this->shop_id = (!empty(Tools::getValue('clerk_shop_select'))) ? (int)Tools::getValue('clerk_shop_select') : $context->shop->id;
        $this->language_id = (!empty(Tools::getValue('clerk_language_select'))) ? (int)Tools::getValue('clerk_language_select') : $context->language->id;

        $this->logger = new ClerkLogger();
    }

    /**
     * @param $product
     * @param $product_id
     */
    public function addProduct($product, $product_id, $qty = 0)
    {
        try {
            $continue = true;

            if ($product === 0) {
                $product = new Product($product_id);
            }

            if (!$product->active) {
                $continue = false;
                $this->removeProduct($product_id);
            }

            if ($qty === 0) {
                $qty = $this->getStockForProduct($product);
            }

            if (Configuration::get('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', $this->language_id, null, $this->shop_id) != '1') {
                if ($qty <= 0) {
                    $continue = false;
                }
            }

            if ($continue) {
                $context = Context::getContext();

                $categories = array();
                $categoriesFull = Product::getProductCategoriesFull($product_id);

                $category_names = array();

                foreach ($categoriesFull as $category) {
                    if(array_key_exists('id_category', $category)){
                        $categories[] = (int)$category['id_category'];
                    }
                    if(array_key_exists('name', $category)){
                        $category_names[] = $category['name'];
                    }
                }

                $image = Image::getCover($product_id);

                if ($product->id_manufacturer) {
                    $manufacturer = new Manufacturer($product->id_manufacturer, $this->language_id);
                } else {
                    $manufacturer = '';
                }

                $product_name = '';

                if(isset($product->name)){
                    if(is_array($product->name)){
                        $product_name = $product->name[$this->language_id];
                    }
                    if(is_string($product->name)){
                        $product_name = $product->name;
                    }
                }

                $product_description = '';

                $product_description_long = '';

                $product_data_sync_fields = Configuration::get('CLERK_DATASYNC_FIELDS', $this->language_id, null, $this->shop_id);

                $product_fields_array = explode(',', $product_data_sync_fields);

                if(isset($product->description_short)){
                    if(is_array($product->description_short)){
                        if(array_key_exists($this->language_id, $product->description_short)){
                            $product_description = $product->description_short[$this->language_id];
                        }
                    }
                    if(is_string($product->description_short)){
                        $product_description = $product->description_short;
                    }
                }

                if($product_description == ''){
                    if(isset($product->description)){
                        if(is_array($product->description)){
                            if(array_key_exists($this->language_id, $product->description)){
                                $product_description = $product->description[$this->language_id];
                                $product_description_long = $product->description[$this->language_id];
                            }
                        }
                        if(is_string($product->description)){
                            $product_description = $product->description;
                            $product_description_long = $product->description;
                        }
                    }
                }

                $Product_params = [
                    'id' => $product_id,
                    'name' => $product_name,
                    'description' => trim(strip_tags($product_description)),
                    'url' => $context->link->getProductLink($product_id),
                    'categories' => $categories,
                    'category_names' => $category_names,
                    'sku' => $product->reference,
                    'brand' => (Validate::isLoadedObject($manufacturer)) ? $manufacturer->name : '',
                    'in_stock' => $this->getStockForProduct($product) > 0,
                    'qty' => $qty
                ];

                if(in_array('description_long', $product_fields_array)){
                    $Product_params['description_long'] = trim(strip_tags($product_description_long));
                }

                if (Configuration::get('CLERK_DATASYNC_CONTEXTUAL_VAT', $this->language_id, null, $this->shop_id) == '1') {
                    $address = new Address();
                    $address->id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT', $this->language_id, 0, 0);
                    $address->id_state = 0;
                    $address->postcode = 0;
                    $tax_rate = Tax::getProductTaxRate((int) $product_id, $address);
                    $tax_rate = ($tax_rate / 100) + 1;
                    $price_exc_tax = Product::getPriceStatic($product_id, false);
                    $list_price_exc_tax = Product::getPriceStatic($product_id, false, null, 6, null, false, false);

                    $Product_params['price'] = $price_exc_tax * $tax_rate;
                    $Product_params['list_price'] = $list_price_exc_tax * $tax_rate;
                } else {
                    $Product_params['price'] = Product::getPriceStatic($product_id, true);
                    $Product_params['list_price'] = Product::getPriceStatic($product_id, true, null, 6, null, false, false);
                }


                global $cookie;
                $default_currency = Currency::getDefaultCurrency();
                $current_currency = new CurrencyCore($cookie->id_currency);

                if($current_currency->iso_code !== $default_currency->iso_code){
                    $Product_params['price'] = (float) $Product_params['price'] / (float) $current_currency->conversion_rate;
                    $Product_params['list_price'] = (float) $Product_params['list_price'] / (float) $current_currency->conversion_rate;
                }


                $Product_params['on_sale'] = $Product_params['price'] < $Product_params['list_price'];


                if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
                    $image_type = Configuration::get('CLERK_IMAGE_SIZE', $this->language_id, null, $this->shop_id);
                    $Product_params['image'] = $context->link->getImageLink($product->link_rewrite[$this->language_id], $image['id_image'], ImageType::getFormattedName($image_type));
                } else {
                    $image_type = Configuration::get('CLERK_IMAGE_SIZE', $this->language_id, null, $this->shop_id) . '_default';
                    $Product_params['image'] = $context->link->getImageLink($product->link_rewrite[$this->language_id], $image['id_image'], $image_type);
                }

                $base_domain = explode('//', _PS_BASE_URL_)[1];
                $image_check = substr(explode($base_domain, $Product_params['image'])[1], 0, 2);
                if ('/-' === $image_check) {
                    $iso = Context::getContext()->language->iso_code;
                    $image_type = Configuration::get('CLERK_IMAGE_SIZE', $this->language_id, null, $this->shop_id) . '_default';
                    $Product_params['image'] = _PS_BASE_URL_ . '/img/p/' . $iso . '-default-'.$image_type.'.jpg';
                }

                $combinations = $product->getAttributeCombinations((int)$this->language_id, true);

                $attributes = [];
                $attribute_ids = [];
                $variants = [];

                if (count($combinations) > 0) {
                    foreach ($combinations as $combination) {
                        if (isset($combination['reference']) && $combination['reference'] != '' && !in_array($combination['reference'], $variants)) {
                            array_push($variants, $combination['reference']);
                        } elseif (isset($combination['id_product_attribute']) && !in_array($combination['id_product_attribute'], $variants)) {
                            array_push($variants, $combination['id_product_attribute']);
                        }

                        $setGroupfield = str_replace(' ', '', $combination['group_name']);

                        if (!isset($attributes[$setGroupfield])) {
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

                //Get custom fields from configuration
                $default = array(
                            'id',
                            'name',
                            );
                $fieldsConfig = Configuration::get('CLERK_DATASYNC_FIELDS', $this->language_id, null, $this->shop_id);
                $tempfields = explode(',', $fieldsConfig);
                $fields = array_merge($default, $tempfields);

                foreach ($fields as $field) {
                    $field = str_replace(' ', '', $field);
                    if ($attributes && array_key_exists($field, $attributes)) {
                        $Product_params[$field . "_ids"] = $attribute_ids[$field];
                        $Product_params[$field] = $attributes[$field];
                    }

                    if (isset($product->$field) && !array_key_exists($field, $Product_params)) {
                        $Product_params[$field] = $product->$field;
                    }
                }

                if (Pack::isPack($product_id)) {
                    foreach ($fields as $_field) {
                        if (empty($attriarr)) {
                            $attriarr = Attribute::getAttributes($this->language_id, true);
                        };

                        $childatributes = [];
                        $children = Pack::getItems($product_id, $this->language_id);

                        foreach ($children as $child) {
                            if (isset($child->id_pack_product_attribute)) {
                                $combination = new Combination($child->id_pack_product_attribute);
                                $combarr = $combination->getAttributesName($this->language_id);

                                foreach ($combarr as $comb) {
                                    foreach ($attriarr as $attri) {
                                        if ($attri['id_attribute'] === $comb['id_attribute']) {
                                            if (str_replace(' ', '', $attri['public_name']) == str_replace(' ', '', $_field)) {
                                                $childatributes[] = $attri['name'];
                                            }
                                        }
                                    }
                                }
                            }

                            if ($attributes && array_key_exists($_field, $attributes)) {
                                $childatributes[$_field] = $attributes[$_field];
                            }

                            if (isset($child->$_field)) {
                                $childatributes[] = $child->$_field;
                            }
                        }

                        if (!empty($childatributes)) {
                            $Product_params['child_'.$_field.'s'] = array_values($childatributes);
                        }
                    }
                }

                if (Configuration::get('CLERK_INCLUDE_VARIANT_REFERENCES', $this->language_id, null, $this->shop_id) == '1') {
                    if (!empty($variants)) {
                        $Product_params['variants'] = $variants;
                    }
                }

                // Adding Product Features
                if (Configuration::get('CLERK_DATASYNC_PRODUCT_FEATURES', $this->language_id, null, $this->shop_id) == '1') {
                    $frontfeatures = Product::getFrontFeaturesStatic($this->language_id, $product_id);

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
                                $Product_params[$key] = $value;
                            }
                        }
                    }
                }

                $productRaw = new Product ($product_id, $this->language_id);

                if(!empty($productRaw)){

                    if(isset($productRaw->unity) && ! empty($productRaw->unity)){
                        $number_of_units = isset($productRaw->number_of_units) && $productRaw->number_of_units > 0 ? (float) $productRaw->number_of_units : 1;
                        $unit_price_unit =  $productRaw->unity;
                        $Product_params['unit_price'] = (float) $Product_params['price'] / $number_of_units;
                        $Product_params['unit_list_price'] = (float) $Product_params['list_price'] / $number_of_units;
                        $Product_params['unit_price_label'] = $unit_price_unit;
                        $Product_params['base_unit'] = strval(number_format( (float) $number_of_units, 2 ) ) . " / " . $unit_price_unit;
                    }

                    if(!empty($fields) && in_array('atc_enabled', $fields)) {
                        $atc_enabled = true;
                        // If the product is disabled, we disable add to cart button
                        if ( property_exists( $productRaw, 'active' ) && $productRaw->active != 1 ) {
                            $atc_enabled = false;
                        }

                        // Disable because of catalog mode enabled in Prestashop settings
                        if ( property_exists( $productRaw, 'catalog_mode' ) && $productRaw->catalog_mode ) {
                            $atc_enabled = false;
                        }

                        // Disable because of "Available for order" checkbox unchecked in product settings
                        if ( property_exists( $productRaw, 'available_for_order') && (bool) $productRaw->available_for_order === false) {
                            $atc_enabled = false;
                        }
                        $stock = StockAvailable::getQuantityAvailableByProduct($product_id, null);

                        // Disable because of stock management
                        if ( Configuration::get('PS_STOCK_MANAGEMENT')
                            && ! StockAvailable::outOfStock($product_id)
                            && ( $stock <= 0
                            || ( property_exists( $productRaw, 'minimal_quantity') && $stock - $productRaw->minimal_quantity < 0 ) )
                        ) {
                            $atc_enabled = false;
                        }
                        $Product_params['atc_enabled'] = $atc_enabled;
                    }

                }

                $params = [
                    'key' => Configuration::get('CLERK_PUBLIC_KEY', $this->language_id, null, $this->shop_id),
                    'private_key' => Configuration::get('CLERK_PRIVATE_KEY', $this->language_id, null, $this->shop_id),
                    'products' => [$Product_params],
                ];

                $this->post('product/add', $params);
                $this->logger->log('Created product ' . $Product_params['name'], ['params' => $params['products']]);
            }
        } catch (Exception $e) {
            $this->logger->error('ERROR addProduct', ['error' => $e->getMessage()]);
        }
    }

    private function getStockForProduct($product)
    {
        try {
            $id_product_attribute = isset($product->id_product_attribute) ? $product->id_product_attribute : null;

            if (isset($this->stock[$product->id][$id_product_attribute])) {
                return $this->stock[$product->id][$id_product_attribute];
            }

            $availableQuantity = StockAvailable::getQuantityAvailableByProduct($product->id, $id_product_attribute);

            $this->stock[$product->id][$id_product_attribute] = $availableQuantity;

            return $this->stock[$product->id][$id_product_attribute];
        } catch (Exception $e) {
            $this->logger->error('ERROR getStockForProduct', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Perform a POST request
     *
     * @param string $endpoint
     * @param array $params
     */
    private function post($endpoint, $params = [])
    {
        try {
            $url = $this->baseurl . $endpoint;
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));

            $response = json_decode(curl_exec($curl));

            curl_close($curl);

            $this->logger->log('POST request', ['endpoint' => $endpoint, 'params' => $params, 'response' => $response]);
        } catch (Exception $e) {
            $this->logger->error('POST request failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove product
     *
     * @param $product_id
     */
    public function removeProduct($product_id)
    {
        try {
            $params = [
                'key' => Configuration::get('CLERK_PUBLIC_KEY', $this->language_id, null, $this->shop_id),
                'private_key' => Configuration::get('CLERK_PRIVATE_KEY', $this->language_id, null, $this->shop_id),
                'products' => $product_id . ',',
            ];

            $this->get('product/remove', $params);
            $this->logger->log('Removed product ', ['params' => $params['products']]);
        } catch (Exception $e) {
            $this->logger->error('ERROR removeProduct', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param string $endpoint
     * @param array $params
     * @return object|string
     */
    public function get($endpoint, $params = [])
    {
        try {
            $url = $this->baseurl . $endpoint . '?' . http_build_query($params);
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $response = json_decode(curl_exec($curl));

            curl_close($curl);

            $this->logger->log('GET request', ['endpoint' => $endpoint, 'params' => $params, 'response' => $response]);

            return $response;
        } catch (Exception $e) {
            $this->logger->error('GET request failed', ['error' => $e->getMessage()]);
        }
    }
}
