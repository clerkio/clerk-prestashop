<?php
require "ClerkAbstractFrontController.php";

class ClerkProductModuleFrontController extends ClerkAbstractFrontController
{
    /**
     * @var array
     */
    protected $fieldMap = [
        'id_product' => 'id',
        'manufacturer_name' => 'brand',
        'reference' => 'sku',
    ];

    /**
     * ClerkProductModuleFrontController constructor.
     */
	public function __construct()
    {
		parent::__construct();

        $this->addFieldHandler('on_sale', function($product) {
            return (bool) $product['on_sale'];
        });

        $this->addFieldHandler('url', function($product) {
            return $this->context->link->getProductLink($product['id_product']);
        });

		$this->addFieldHandler('image', function($product) {
            $image = Image::getCover($product['id_product']);
            return $this->context->link->getImageLink($product['link_rewrite'], $image['id_image']);
        });

		$this->addFieldHandler('price', function($product) {
		    return Product::getPriceStatic($product['id_product'], true);
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
		$products = $product->getProducts(Configuration::get('PS_LANG_DEFAULT'), $this->offset, $this->limit, $this->order_by, $this->order, false, true);

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
        return [
            'id',
            'name',
            'description',
            'price',
            'image',
            'url',
            'categories',
            'brand',
            'sku',
            'on_sale',
        ];
    }
}