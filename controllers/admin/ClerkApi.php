<?php

class Clerk_Api
{
    /**
     * @var string
     */
    protected $baseurl = 'https://api.clerk.io/v2/';
    /**
     * @var ClerkLogger
     */
    protected $logger;

    /**
     * @var int
     */
    private $language_id;

    /**
     * @var int
     */
    private $shop_id;
    /**
     * @var array[]|int[]
     */
    protected $all_contexts;

    public function __construct()
    {
        require_once(sprintf("%s/clerk/helpers/Context.php", _PS_MODULE_DIR_));
        $context = Context::getContext();

        $this->shop_id = (!empty(Tools::getValue('clerk_shop_select'))) ? (int)Tools::getValue('clerk_shop_select') : $context->shop->id;
        $this->language_id = (!empty(Tools::getValue('clerk_language_select'))) ? (int)Tools::getValue('clerk_language_select') : $context->language->id;

        $this->logger = new ClerkLogger();

        $this->all_contexts = ContextHelper::getAllContexts();
    }

    /**
     * @param $product
     * @param $product_id
     * @param int $qty
     */
    public function updateProduct($product, $product_id, $qty = 0)
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

                $categories = [];
                $categoriesFull = Product::getProductCategoriesFull($product_id);

                $category_names = [];

                foreach ($categoriesFull as $category) {
                    if (array_key_exists('id_category', $category)) {
                        $categories[] = (int)$category['id_category'];
                    }
                    if (array_key_exists('name', $category)) {
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

                if (isset($product->name)) {
                    if (is_array($product->name)) {
                        $product_name = $product->name[$this->language_id];
                    }
                    if (is_string($product->name)) {
                        $product_name = $product->name;
                    }
                }

                $product_description = '';

                $product_description_long = '';

                $product_data_sync_fields = Configuration::get('CLERK_DATASYNC_FIELDS', $this->language_id, null, $this->shop_id);

                $product_fields_array = explode(',', $product_data_sync_fields);

                if (isset($product->description_short)) {
                    if (is_array($product->description_short)) {
                        if (array_key_exists($this->language_id, $product->description_short)) {
                            $product_description = $product->description_short[$this->language_id];
                        }
                    }
                    if (is_string($product->description_short)) {
                        $product_description = $product->description_short;
                    }
                }

                if ($product_description == '') {
                    if (isset($product->description)) {
                        if (is_array($product->description)) {
                            if (array_key_exists($this->language_id, $product->description)) {
                                $product_description = $product->description[$this->language_id];
                                $product_description_long = $product->description[$this->language_id];
                            }
                        }
                        if (is_string($product->description)) {
                            $product_description = $product->description;
                            $product_description_long = $product->description;
                        }
                    }
                }

                $product_data = [
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

                if (in_array('description_long', $product_fields_array)) {
                    $product_data['description_long'] = trim(strip_tags($product_description_long));
                }

                if (Configuration::get('CLERK_DATASYNC_CONTEXTUAL_VAT', $this->language_id, null, $this->shop_id) == '1') {
                    $address = new Address();
                    $address->id_country = (int)Configuration::get('PS_COUNTRY_DEFAULT', $this->language_id, 0, 0);
                    $address->id_state = 0;
                    $address->postcode = 0;
                    $tax_rate = Tax::getProductTaxRate((int)$product_id, $address);
                    $tax_rate = ($tax_rate / 100) + 1;
                    $price_exc_tax = Product::getPriceStatic($product_id, false);
                    $list_price_exc_tax = Product::getPriceStatic($product_id, false, null, 6, null, false, false);

                    $product_data['price'] = $price_exc_tax * $tax_rate;
                    $product_data['list_price'] = $list_price_exc_tax * $tax_rate;
                } else {
                    $product_data['price'] = Product::getPriceStatic($product_id, true);
                    $product_data['list_price'] = Product::getPriceStatic($product_id, true, null, 6, null, false, false);
                }


                global $cookie;
                $default_currency = Currency::getDefaultCurrency();
                $current_currency = new CurrencyCore($cookie->id_currency);

                if ($current_currency->iso_code !== $default_currency->iso_code) {
                    $product_data['price'] = (float)$product_data['price'] / (float)$current_currency->conversion_rate;
                    $product_data['list_price'] = (float)$product_data['list_price'] / (float)$current_currency->conversion_rate;
                }


                $product_data['on_sale'] = $product_data['price'] < $product_data['list_price'];


                if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
                    $image_type = Configuration::get('CLERK_IMAGE_SIZE', $this->language_id, null, $this->shop_id);
                    $product_data['image'] = $context->link->getImageLink($product->link_rewrite[$this->language_id], $image['id_image'], ImageType::getFormattedName($image_type));
                } else {
                    $image_type = Configuration::get('CLERK_IMAGE_SIZE', $this->language_id, null, $this->shop_id) . '_default';
                    $product_data['image'] = $context->link->getImageLink($product->link_rewrite[$this->language_id], $image['id_image'], $image_type);
                }

                $base_domain = explode('//', _PS_BASE_URL_)[1];
                $image_check = substr(explode($base_domain, $product_data['image'])[1], 0, 2);
                if ('/-' === $image_check) {
                    $iso = Context::getContext()->language->iso_code;
                    $image_type = Configuration::get('CLERK_IMAGE_SIZE', $this->language_id, null, $this->shop_id) . '_default';
                    $product_data['image'] = _PS_BASE_URL_ . '/img/p/' . $iso . '-default-' . $image_type . '.jpg';
                }

                $combinations = $product->getAttributeCombinations((int)$this->language_id, true);

                $attributes = [];
                $attribute_ids = [];

                $variant_ids = [];
                $variant_skus = [];
                $variant_prices = [];
                $variant_stocks = [];

                if (!empty($combinations)) {
                    foreach ($combinations as $combination) {

                        // BUILD SKUS
                        if (isset($combination['reference']) && !in_array($combination['reference'], $variant_skus)) {
                            $variant_skus[] = $combination['reference'];
                        }

                        // BUILD VARIANT_IDS
                        if (isset($combination['id_product_attribute']) && !in_array($combination['id_product_attribute'], $variant_ids)) {
                            $variant_ids[] = $combination['id_product_attribute'];
                        }

                        // BUILD VARIANT PRICES
                        if (isset($combination['price'])) {
                            $variant_prices[] = (float)$combination['price'];
                        }

                        // BUILD VARIANT STOCKS
                        if (isset($combination['quantity'])) {
                            $variant_stocks[] = (int)$combination['quantity'];
                        }

                        // CLEAN GROUP NAME
                        $setGroupField = str_replace(' ', '', $combination['group_name']);

                        // ATTRIBUTE VARIATION NAMES, NORMALLY COLORS AND SIZES
                        if (!isset($attributes[$setGroupField])) {
                            $attribute_ids[$setGroupField][] = $combination['id_attribute'];
                            $attributes[$setGroupField][] = $combination['attribute_name'];
                        }
                        if (!in_array($combination['attribute_name'], $attributes[$setGroupField])) {
                            $attribute_ids[$setGroupField][] = $combination['id_attribute'];
                            $attributes[$setGroupField][] = $combination['attribute_name'];
                        }

                    }
                }

                //Get custom fields from configuration
                $default = array(
                    'id',
                    'name',
                );
                $fieldsConfig = Configuration::get('CLERK_DATASYNC_FIELDS', $this->language_id, null, $this->shop_id);
                $tmp_fields = explode(',', $fieldsConfig);
                $fields = array_merge($default, $tmp_fields);

                if (Configuration::get('CLERK_INCLUDE_VARIANT_REFERENCES', $this->language_id, null, $this->shop_id)) {
                    if(!empty($variant_ids)){
                        $product_data['variants'] = $variant_ids;
                    }
                    if(!empty($variant_skus)){
                        $product_data['variant_skus'] = $variant_skus;
                    }
                    if(!empty($variant_prices)){
                        $product_data['variant_prices'] = $variant_prices;
                    }
                    if(!empty($variant_stocks)){
                        $product_data['variant_stocks'] = $variant_stocks;
                    }
                }

                foreach ($fields as $field) {
                    $field = str_replace(' ', '', $field);
                    if ($attributes && array_key_exists($field, $attributes)) {
                        $product_data[$field . "_ids"] = $attribute_ids[$field];
                        $product_data[$field] = $attributes[$field];
                    }

                    if (isset($product->$field) && !array_key_exists($field, $product_data)) {
                        $product_data[$field] = $product->$field;
                    }
                }

                if (Pack::isPack($product_id)) {
                    foreach ($fields as $_field) {
                        if (empty($attribute_array)) {
                            if (version_compare(_PS_VERSION_, '8.0.0', '>=')) {
                                $attribute_array = ProductAttribute::getAttributes($this->language_id, true);
                            } else {
                                $attribute_array = Attribute::getAttributes($this->language_id, true);
                            }
                        }

                        $child_attributes = [];
                        $children = Pack::getItems($product_id, $this->language_id);

                        foreach ($children as $child) {
                            if (isset($child->id_pack_product_attribute)) {
                                $combination = new Combination($child->id_pack_product_attribute);
                                $combination_names = $combination->getAttributesName($this->language_id);

                                foreach ($combination_names as $comb) {
                                    foreach ($attribute_array as $attribute) {
                                        if ($attribute['id_attribute'] === $comb['id_attribute']) {
                                            if (str_replace(' ', '', $attribute['public_name']) == str_replace(' ', '', $_field)) {
                                                $child_attributes [] = $attribute['name'];
                                            }
                                        }
                                    }
                                }
                            }

                            if ($attributes && array_key_exists($_field, $attributes)) {
                                $child_attributes [$_field] = $attributes[$_field];
                            }

                            if (isset($child->$_field)) {
                                $child_attributes [] = $child->$_field;
                            }
                        }

                        if (!empty($child_attributes)) {
                            $product_data['child_' . $_field . 's'] = array_values($child_attributes);
                        }
                    }
                }


                // Adding Product Features
                if (Configuration::get('CLERK_DATASYNC_PRODUCT_FEATURES', $this->language_id, null, $this->shop_id) == '1') {
                    $product_front_features = Product::getFrontFeaturesStatic($this->language_id, $product_id);

                    if (!empty($product_front_features)) {
                        $features_object = [];
                        foreach ($product_front_features as $feature) {
                            if (isset($feature['name'])) {
                                $feature['name'] = str_replace(array(' ', '-'), '_', $feature['name']);
                                if (!array_key_exists($feature['name'], $features_object)) {
                                    $features_object[$feature['name']] = [];
                                }
                                $features_object[$feature['name']][] = $feature['value'];
                            }
                        }
                        foreach ($features_object as $key => $value) {
                            if (count($value) === 0) {
                                $value = "";
                            }
                            if (count($value) === 1) {
                                $value = $value[0];
                            }
                            $product_data[$key] = $value;
                        }
                    }
                }

                if (Configuration::get('CLERK_DATASYNC_PRODUCT_TAGS', $this->language_id, null, $this->shop_id) == '1') {
                    $productTags = Tag::getProductTags($product_id);
                    if (!empty($productTags) && is_array($productTags) && array_key_exists($this->language_id, $productTags)) {
                        $product_data['tags'] = $productTags[$this->language_id];
                    }
                }

                $productRaw = new Product ($product_id, $this->language_id);

                if (!empty($productRaw)) {

                    if (isset($productRaw->unity) && !empty($productRaw->unity)) {
                        $number_of_units = isset($productRaw->number_of_units) && $productRaw->number_of_units > 0 ? (float)$productRaw->number_of_units : 1;
                        $unit_price_unit = $productRaw->unity;
                        $product_data['unit_price'] = (float)$product_data['price'] / $number_of_units;
                        $product_data['unit_list_price'] = (float)$product_data['list_price'] / $number_of_units;
                        $product_data['unit_price_label'] = $unit_price_unit;
                        $product_data['base_unit'] = strval(number_format((float)$number_of_units, 2)) . " / " . $unit_price_unit;
                    }

                    if (!empty($fields) && in_array('atc_enabled', $fields)) {
                        $atc_enabled = true;
                        // If the product is disabled, we disable add to cart button
                        if (property_exists($productRaw, 'active') && $productRaw->active != 1) {
                            $atc_enabled = false;
                        }

                        // Disable because of catalog mode enabled in Prestashop settings
                        if (property_exists($productRaw, 'catalog_mode') && $productRaw->catalog_mode) {
                            $atc_enabled = false;
                        }

                        // Disable because of "Available for order" checkbox unchecked in product settings
                        if (property_exists($productRaw, 'available_for_order') && (bool)$productRaw->available_for_order === false) {
                            $atc_enabled = false;
                        }
                        $stock = StockAvailable::getQuantityAvailableByProduct($product_id, null);

                        // Disable because of stock management
                        if (Configuration::get('PS_STOCK_MANAGEMENT')
                            && !StockAvailable::outOfStock($product_id)
                            && ($stock <= 0
                                || (property_exists($productRaw, 'minimal_quantity') && $stock - $productRaw->minimal_quantity < 0))
                        ) {
                            $atc_enabled = false;
                        }
                        $product_data['atc_enabled'] = $atc_enabled;
                    }
                    if (!empty($fields) && in_array('minimal_quantity', $fields) && property_exists($productRaw, 'minimal_quantity')) {
                        $product_data['minimal_quantity'] = $productRaw->minimal_quantity;
                    }

                    // GET PRODUCT SPECIFIC PRICE
                    $specificPrices = SpecificPrice::getSpecificPrice($product_id, $this->shop_id, null, null, null, 1);
                    if (!empty($specificPrices)) {
                        $specificPriceValues = [];
                        foreach ($specificPrices as $spPrice) {
                            if (!is_array($spPrice)) {
                                continue;
                            }
                            if (array_key_exists('id_shop_group', $spPrice) && $spPrice['id_shop_group'] != 0) {
                                continue;
                            }
                            if (array_key_exists('id_currency', $spPrice) && $spPrice['id_currency'] != 0) {
                                continue;
                            }
                            if (array_key_exists('id_country', $spPrice) && $spPrice['id_country'] != 0) {
                                continue;
                            }
                            if (array_key_exists('id_group', $spPrice) && $spPrice['id_group'] != 0) {
                                continue;
                            }
                            if (array_key_exists('id_customer', $spPrice) && $spPrice['id_customer'] != 0) {
                                continue;
                            }
                            if (array_key_exists('id_product_attribute', $spPrice) && $spPrice['id_product_attribute'] != 0) {
                                continue;
                            }
                            $tmp_price = null;
                            if ($spPrice['reduction_type'] == 'percentage') {
                                $tmp_tax = ($productRaw->tax_rate / 100) + 1;
                                $tmp_price = ($productRaw->base_price * $tmp_tax);
                                $reduction = 1 - $spPrice['reduction'];
                                $tmp_price = $tmp_price * $reduction;
                            }
                            if ($spPrice['reduction_type'] == 'amount') {
                                $reduction = $spPrice['reduction_tax'] != 0 ? (($productRaw->tax_rate / 100) + 1) * $spPrice['reduction'] : $spPrice['reduction'];
                                $tmp_price = $spPrice['price'] - $reduction;
                            }
                            if (is_numeric($tmp_price)) {
                                $specificPriceValues[] = $tmp_price;
                            }
                        }
                        if (!empty($specificPriceValues)) {
                            $specificPrice = min($specificPriceValues);
                            $product_data['price'] = (float)$specificPrice;
                        }
                    }

                }

                $params = [
                    'key' => Configuration::get('CLERK_PUBLIC_KEY', $this->language_id, null, $this->shop_id),
                    'private_key' => Configuration::get('CLERK_PRIVATE_KEY', $this->language_id, null, $this->shop_id),
                    'products' => [$product_data],
                ];

                $this->post('products', $params);
                $this->logger->log('Created product ' . $product_data['name'], ['params' => $params['products']]);
            }
        } catch (Exception $e) {
            $this->logger->error('ERROR addProduct', ['error' => $e->getMessage()]);
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
                'products' => [$product_id],
            ];

            $this->delete('products', $params);
            $this->logger->log('Removed product ', ['params' => $params['products']]);
        } catch (Exception $e) {
            $this->logger->error('ERROR removeProduct', ['error' => $e->getMessage()]);
        }
    }



    private function getStockForProduct($product)
    {
        try {
            $id_product_attribute = $product->id_product_attribute ?: null;

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
     * @param string $endpoint
     * @param array $params
     * @return object|void
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

    /**
     * Perform a DELETE request
     *
     * @param string $endpoint
     * @param array $params
     */
    private function delete($endpoint, $params = [])
    {
        try {
            $url = $this->baseurl . $endpoint . '?' . http_build_query($params);
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($curl);

            curl_close($curl);

            $this->logger->log('DELETE request', ['endpoint' => $endpoint, 'params' => $params, 'response' => $response]);
            return $response;

        } catch (Exception $e) {
            $this->logger->error('DELETE request failed', ['error' => $e->getMessage()]);
        }
    }


    /**
     * Perform a PATCH request
     *
     * @param string $endpoint
     * @param array $params
     * @return object|void
     */
    private function patch($endpoint, $params = [])
    {
        try {
            $url = $this->baseurl . $endpoint;
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));

            $response = json_decode(curl_exec($curl));

            curl_close($curl);

            $this->logger->log('PATCH request', ['endpoint' => $endpoint, 'params' => $params, 'response' => $response]);

            return $response;

        } catch (Exception $e) {
            $this->logger->error('PATCH request failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Post Received Token for Verification
     *
     * @param array|void $data
     * @return array
     */
    public function verifyToken($data = null)
    {

        if (!$data) {
            return [];
        }

        try {

            $endpoint = 'token/verify';

            $data['key'] = Configuration::get('CLERK_PUBLIC_KEY', $this->language_id, null, $this->shop_id);

            $response = $this->get($endpoint, $data);

            if (!$response) {
                return [];
            } else {
                return (array)$response;
            }

        } catch (Exception $e) {
            $this->logger->error('ERROR verify_token', array('error' => $e->getMessage()));
            return [];
        }

    }
}
