<?php

class Clerk_Api
{
    /**
     * @var string
     */
    protected $baseurl = 'http://api.clerk.io/v2/';
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

                foreach ($categoriesFull as $category) {
                    $categories[] = (int)$category['id_category'];
                }

                $image = Image::getCover($product_id);

                if ($product->id_manufacturer) {

                    $manufacturer = new Manufacturer($product->id_manufacturer, $this->language_id);

                } else {

                    $manufacturer = '';

                }

                if (version_compare(PHP_VERSION, '7.4') >= 0) {

                    $Product_params = [
                        'id' => $product_id,
                        'name' => $product->name[$this->language_id],
                        'description' => $product->description[$this->language_id],
                        'price' => (float)Product::getPriceStatic($product_id, true),
                        'list_price' => (float)Product::getPriceStatic($product_id, true, null, 6, null, false, false),
                        'url' => $context->link->getProductLink($product_id),
                        'categories' => $categories,
                        'sku' => $product->reference,
                        'on_sale' => $product->on_sale,
                        'brand' => (Validate::isLoadedObject($manufacturer))? $manufacturer->name : '',
                        'in_stock' => $this->getStockForProduct($product) > 0,
                        'qty' => $qty,

                    ];

                    if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {

                        $Product_params['image'] = $context->link->getImageLink($product->link_rewrite[$this->language_id], $image['id_image'], ImageType::getFormattedName('home'));

                    }
                    else {

                        $Product_params['image'] = $context->link->getImageLink($product->link_rewrite[$this->language_id], $image['id_image'], 'home_default');

                    }
                }
                else {

                    $Product_params = [
                        'id' => $product_id,
                        'name' => $product->name{$this->language_id},
                        'description' => $product->description{$this->language_id},
                        'price' => (float)Product::getPriceStatic($product_id, true),
                        'list_price' => (float)Product::getPriceStatic($product_id, true, null, 6, null, false, false),
                        'url' => $context->link->getProductLink($product_id),
                        'categories' => $categories,
                        'sku' => $product->reference,
                        'on_sale' => $product->on_sale,
                        'brand' => (Validate::isLoadedObject($manufacturer))? $manufacturer->name : '',
                        'in_stock' => $this->getStockForProduct($product) > 0,
                        'qty' => $qty,

                    ];

                    if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {

                        $Product_params['image'] = $context->link->getImageLink($product->link_rewrite{$this->language_id}, $image['id_image'], ImageType::getFormattedName('home'));

                    }
                    else {

                        $Product_params['image'] = $context->link->getImageLink($product->link_rewrite{$this->language_id}, $image['id_image'], 'home_default');

                    }
                }

                // new stuff

                $combinations = $product->getAttributeCombinations((int)$this->language_id, true);

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

                 //Get custom fields from configuration
                 $default = array(
                    'id',
                    'name',
                );
                 $fieldsConfig = Configuration::get('CLERK_DATASYNC_FIELDS',$this->language_id, null, $this->shop_id);
                 $tempfields = explode(',', $fieldsConfig);
                 $fields = array_merge($default, $tempfields);

                foreach ($fields as $field) {

                    $field = str_replace(' ','',$field);
                    if ($attributes && array_key_exists($field, $attributes)){
                        $Product_params[$field] = $attributes[$field];
                    }

                    if (isset($product->$field) && !array_key_exists($field, $Product_params)) {
                        $Product_params[$field] = $product->$field;
                    }
                }

                if(Pack::isPack($product_id)){
                    foreach($customFields as $_field){

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
                        $Product_params['variants'] = $variants;
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