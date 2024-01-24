<?php

class ProductHelper {

    /**
     * @param $shop_id
     * @param $language_id
     * @return false|string
     */
    public static function shouldLiveUpdate($shop_id, $language_id){
         return Configuration::get('CLERK_DATASYNC_USE_REAL_TIME_UPDATES', $language_id, null, $shop_id);
    }

    /**
     * @param $shop_id
     * @param $language_id
     * @return array
     */
    private static function getAdditionalFields($shop_id, $language_id){
        $fields = explode(',', Configuration::get('CLERK_DATASYNC_FIELDS', $language_id, null, $shop_id));
        $trimmed_fields = [];
        foreach ($fields as $field){
            if(empty($field)){
                continue;
            }
            $trimmed_fields[] = trim($field);
        }
        return $trimmed_fields;
    }

    /**
     * @param $field
     * @param $language_id
     * @return mixed
     */
    private static function getFieldMultiLang($field, $language_id, $strip_html = false){
        if(is_array($field) && array_key_exists($language_id, $field)){
            $field = $field[$language_id];
        }
        return $strip_html ? trim(strip_tags($field)) : $field;
    }

    private static function getUrl($context, $product_id, $language_id){
        return $context->link->getProductLink($product_id, null, null, null, $language_id);
    }

    private static function getImageUrl($product, $product_id, $shop_id, $language_id, $context){
        $image_instance = Image::getCover($product_id);
        $size = ProductHelper::getImageSize($shop_id, $language_id);
        $link_rewrite = ProductHelper::getFieldMultiLang($product->link_rewrite, $language_id);
        $product_image = $context->link->getImageLink($link_rewrite, $image_instance['id_image'], $size);

        // Run through placeholder function to fix bad paths before return
        return ProductHelper::getPlaceholderImageUrl($context, $product_image, $size);
    }

    private static function getVariantImageUrls($context, $shop_id, $language_id, $product){
        $variant_images = [];
        $size = ProductHelper::getImageSize($shop_id, $language_id);
        $link_rewrite = ProductHelper::getFieldMultiLang($product->link_rewrite, $language_id);
        try {
            $variant_image_ids = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                        SELECT i.`id_image` as id
                        FROM `' . _DB_PREFIX_ . 'image` i
                        ' . Shop::addSqlAssociation('image', 'i') . '
                        WHERE i.`id_product` = ' . (int)$product['id_product'] . '
                        ORDER BY i.`position`');
            foreach ($variant_image_ids as $vid_column){
                foreach ($vid_column as $_ => $image_id){
                    $variant_images[] = $context->link->getImageLink($link_rewrite, $image_id, $size);
                }
            }
        } catch (PrestaShopDatabaseException|PrestaShopException $e) {
            return $variant_images;
        }
        return $variant_images;
    }

    private static function getImageSize($shop_id, $language_id){
        $image_size = Configuration::get('CLERK_IMAGE_SIZE', $language_id, null, $shop_id);
        if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
            return ImageType::getFormattedName($image_size);
        } else {
            return $image_size . '_default';
        }
    }

    private static function getPlaceholderImageUrl($context, $product_image, $image_type)
    {
        // Set placeholder image if bad pattern
        $base_domain = explode('//', _PS_BASE_URL_)[1];
        $image_check = substr(explode($base_domain, $product_image)[1], 0, 2);
        if ('/-' === $image_check) {
            $product_image = _PS_BASE_URL_ . '/img/p/' . $context->language->iso_code . '-default-' . $image_type . '.jpg';
        }
        return $product_image;
    }



    /**
     * @param $shop_id
     * @param $language_id
     * @param $product_id
     * @param $product
     * @param $product_stock
     * @return mixed|void
     */
    public static function buildData($context, $shop_id, $language_id, $product_id, $product = null, $product_stock = null, $additional_fields = null){

        if (!$product && !$product_id) {
            return;
        }

        if (!$product && $product_id) {
            $product = new Product($product_id);
        }

        if(!$product_id && $product){
            $product_id = $product->id_product;
        }

        if (!$product->active) {
            return;
        }
        if ($product->id_product_attribute && (!Configuration::get('CLERK_DATASYNC_QUERY_BY_STOCK', $language_id, null, $shop_id) || null === $product_stock)){
            $product_stock = StockAvailable::getQuantityAvailableByProduct($product_id, $product->id_product_attribute, $shop_id);
        } else {
            $product_stock = (int) $product['quantity'];
        }

        if (!Configuration::get('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', $language_id, null, $shop_id) && $product_stock <= 0) {
            return;
        }

        if (null === $additional_fields){
            $additional_fields = ProductHelper::getAdditionalFields($shop_id, $language_id);
        }

        $manufacturer = $product->id_manufacturer ? new Manufacturer($product->id_manufacturer, $language_id) : null;
        $brand = (Validate::isLoadedObject($manufacturer)) ? $manufacturer->name : '';

        $product_data = [
            'id' => $product_id,
            'categories' => [],
            'category_names' => [],
            'sku' => $product->reference,
            'brand' => $brand,
            'in_stock' => $product_stock > 0,
            'qty' => $product_stock
        ];

        $categoriesFull = Product::getProductCategoriesFull($product_id);
        foreach ($categoriesFull as $category) {
            if (array_key_exists('id_category', $category)) {
                $product_data['categories'][] = (int)$category['id_category'];
            }
            if (array_key_exists('name', $category)) {
                $product_data['category_names'][] = $category['name'];
            }
        }

        $product_data['name'] = ProductHelper::getFieldMultiLang($product->name, $language_id, true);
        $product_data['description'] = ProductHelper::getFieldMultiLang($product->description_short, $language_id, true);
        $product_data['description_long'] = ProductHelper::getFieldMultiLang($product->description, $language_id, true);
        $product_data['url'] = ProductHelper::getUrl($context, $product_id, $language_id);
        $product_data['image'] = ProductHelper::getImageUrl($product, $product_id, $shop_id, $language_id, $context);


        if (Configuration::get('CLERK_INCLUDE_VARIANT_REFERENCES', $language_id, null, $shop_id)) {
            // TODO: Implement more variant fields here.
            $product_data['variant_images'] = ProductHelper::getVariantImageUrls($context, $shop_id, $language_id, $product);
        }


}
}