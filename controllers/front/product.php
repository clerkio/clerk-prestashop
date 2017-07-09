<?php
require "ClerkAbstractFrontController.php";

class ClerkProductModuleFrontController extends ClerkAbstractFrontController
{
    /**
     * Mapped fields
     *
     * @var array
     */
    protected $fieldMap = array(
        'id_product' => 'id',
        'manufacturer_name' => 'brand',
        'reference' => 'sku',
    );

    /**
     * Localized attributes
     *
     * @var array
     */
    protected $localizedAttributes = array(
        'name',
        'description',
        'url',
    );

    /**
     * ClerkProductModuleFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        //Needed for PHP 5.3 support
        $context = $this->context;

        $this->addFieldHandler('on_sale', function($product) {
            return (bool) $product['on_sale'];
        });

        $this->addFieldHandler('url', function($product, $id_lang) use($context) {
            return $context->link->getProductLink($product['id_product'], null, null, null, $id_lang);
        });

        $this->addFieldHandler('image', function($product) use($context) {
            $image = Image::getCover($product['id_product']);
            return $context->link->getImageLink($product['link_rewrite'], $image['id_image'], 'home_default');
        });

        $this->addFieldHandler('price', function($product) {
            return Product::getPriceStatic($product['id_product'], true);
        });

        $this->addFieldHandler('list_price', function($product) {
            //Get price without reduction
            return Product::getPriceStatic($product['id_product'], true, null, 6, null, false, false);
        });

        $this->addFieldHandler('categories', function($product, $id_lang) {
            $categories = array();
            $categoriesFull = Product::getProductCategoriesFull($product['id_product'], $id_lang);

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
         $products = $this->getAllProducts();

        $response = $this->filterProducts($products);

        return array_values($response);
    }

    /**
     * Filters products and build localized array structure
     *
     * @todo refactor this
     * @param $allProducts
     */
    protected function filterProducts($allProducts)
    {
        $response = array();

        foreach ($allProducts as $isoCode => $languageProducts) {
            $allIds = array_column($languageProducts, 'id');

            foreach ($allIds as $key_id => $id_product) {
                if (!isset($response[$id_product])) {
                    $response[$id_product] = array();
                }

                foreach ($languageProducts[$key_id] as $attribute => $value) {

                    if (in_array($attribute, $this->localizedAttributes)) {
                        if (! isset($response[$id_product][$attribute])) {
                            $response[$id_product][$attribute] = [];
                        }

                        $response[$id_product][$attribute][$isoCode] = $value;
                    } else {
                        $response[$id_product][$attribute] = $value;
                    }
                }
            }
        }

        return $response;
    }

    /**
     * Get default fields for products
     *
     * @return array
     */
    protected function getDefaultFields()
    {
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
        );

        //Get custom fields from configuration
        $fieldsConfig = Configuration::get('CLERK_DATASYNC_FIELDS', '');

        $fields = explode(',', $fieldsConfig);

        return array_merge($default, $fields);
    }

    /**
     * Get all products for all languages
     *
     * @return array
     */
    protected function getAllProducts()
    {
        /** @var ProductCore $product */
        $productCore = new Product();

        $languages = Language::getLanguages(true, $this->context->shop->id);

        $items = array();

        //Get products for all languages
        foreach ($languages as $language) {
            $products = $productCore->getProducts($language['id_lang'], $this->offset, $this->limit, $this->order_by, $this->order, false, true);

            $items[$language['iso_code']] = array();

            //Get data for each product
            foreach ($products as $product) {
                $item = $this->getProductData($product, $language['id_lang']);

                $items[$language['iso_code']][] = $item;
            }
        }

        return $items;
    }

    /**
     * Get data for product
     *
     * @param $product
     * @return array
     */
    protected function getProductData($product, $id_lang)
    {
        $item = array();
        $fields = array_flip($this->fieldMap);

        foreach ($this->fields as $field) {
            if (array_key_exists($field, array_flip($this->fieldMap))) {
                $item[$field] = $product[$fields[$field]];
            } elseif (isset($product[$field])) {
                $item[$field] = $product[$field];
            }

            //Check if there's a fieldHandler assigned for this field
            if (isset($this->fieldHandlers[$field])) {
                $item[$field] = $this->fieldHandlers[$field]($product, $id_lang);
            }
        }

        return $item;
    }
}