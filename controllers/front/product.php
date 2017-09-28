<?php
require "ClerkAbstractFrontController.php";

class ClerkProductModuleFrontController extends ClerkAbstractFrontController
{
    /**
     * @var array
     */
    protected $fieldMap = array(
        'id_product' => 'id',
        'manufacturer_name' => 'brand',
        'reference' => 'sku',
    );

    /**
     * ClerkProductModuleFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->addFieldHandler('on_sale', function($product) {
            return (Product::getPriceStatic($product['id_product'], true) < Product::getPriceStatic($product['id_product'], true, null, 6, null, false, false));
        });

        //Needed for PHP 5.3 support
        $context = $this->context;

        $this->addFieldHandler('url', function($product) use($context) {
            return $context->link->getProductLink($product['id_product']);
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

        $this->addFieldHandler('categories', function($product) {
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
        $fieldsConfig = Configuration::get('CLERK_DATASYNC_FIELDS', $this->getLanguageId(), null, $this->getShopId());

        $fields = explode(',', $fieldsConfig);

        return array_merge($default, $fields);
    }
}