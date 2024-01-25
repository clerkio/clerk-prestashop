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
     * @param $string
     * @return array|string|string[]|null
     */
    private static function handleizeName($string){
        $string = str_replace([' ', '-'], '_', $string);
        return preg_replace('/([^0-9a-zA-Z_]+)/', '', $string);
    }

    private static function getAttributes($language_id)
    {
        if (version_compare(_PS_VERSION_, '8.0.0', '>=')) {
            return ProductAttribute::getAttributes($language_id, true);
        } else {
            return Attribute::getAttributes($language_id, true);
        }
    }

    /**
     * @param $shop_id
     * @param $language_id
     * @return array
     */
    public static function getAdditionalFields($shop_id, $language_id){
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
     * @param bool $strip_html
     * @return mixed
     */
    private static function getFieldMultiLang($field, $language_id, $strip_html = false){
        if(is_array($field) && array_key_exists($language_id, $field)){
            $field = $field[$language_id];
        }
        return $strip_html ? trim(strip_tags($field)) : $field;
    }

    /**
     * @param $context
     * @param $product_id
     * @param $language_id
     * @return mixed
     */
    private static function getUrl($context, $product_id, $language_id){
        return $context->link->getProductLink($product_id, null, null, null, $language_id);
    }

    /**
     * @param $product
     * @param $product_id
     * @param $shop_id
     * @param $language_id
     * @param $context
     * @return mixed|string
     */
    private static function getImageUrl($product, $product_id, $shop_id, $language_id, $context){
        $image_instance = Image::getCover($product_id);
        $size = ProductHelper::getImageSize($shop_id, $language_id);
        $link_rewrite = ProductHelper::getFieldMultiLang($product->link_rewrite, $language_id);
        $product_image = $context->link->getImageLink($link_rewrite, $image_instance['id_image'], $size);

        // Run through placeholder function to fix bad paths before return
        return ProductHelper::getPlaceholderImageUrl($context, $product_image, $size);
    }

    /**
     * @param $context
     * @param $shop_id
     * @param $language_id
     * @param $product
     * @return array
     */
    private static function getVariantImageUrls($context, $shop_id, $language_id, $product, $product_id){
        $variant_images = [];
        $size = ProductHelper::getImageSize($shop_id, $language_id);
        $link_rewrite = ProductHelper::getFieldMultiLang($product->link_rewrite, $language_id);
        try {
            $variant_image_ids = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                        SELECT i.`id_image` as id
                        FROM `' . _DB_PREFIX_ . 'image` i
                        ' . Shop::addSqlAssociation('image', 'i') . '
                        WHERE i.`id_product` = ' . (int) $product_id . '
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

    /**
     * @param $shop_id
     * @param $language_id
     * @return string
     */
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
     * @param $product
     * @param $language_id
     * @return string
     */
    private static function getBrandName($product, $language_id)
    {
        $manufacturer = $product->id_manufacturer ? new Manufacturer($product->id_manufacturer, $language_id) : null;
        return (Validate::isLoadedObject($manufacturer)) ? $manufacturer->name : '';
    }

    /**
     * @param $product_id
     * @return array
     */
    private static function getSupplierNames($product_id)
    {
        $suppliers = ProductSupplier::getSupplierCollection($product_id, true)->getResults();
        $supplier_names = [];
        foreach ($suppliers as $supplier){
            $supplier_names[] = Supplier::getNameById($supplier->id_supplier);
        }
        return $supplier_names;
    }

    private static function getPriceInfo($shop_id, $language_id, $product_id, $product, $product_data)
    {
        global $cookie;
        $default_currency = Currency::getDefaultCurrency();
        $current_currency = new CurrencyCore($cookie->id_currency);
        if (Configuration::get('CLERK_DATASYNC_CONTEXTUAL_VAT', $language_id, null, $shop_id)) {
            $address = new Address();
            $address->id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT', $language_id, 0, 0);
            $address->id_state = 0;
            $address->postcode = 0;
            $tax_rate = Tax::getProductTaxRate($product_id, $address);
            $tax_rate = ($tax_rate / 100) + 1;

            $product_data['price'] = Product::getPriceStatic($product_id, false) * $tax_rate;
            $product_data['list_price'] = Product::getPriceStatic($product_id, false, null, 6, null, false, false) * $tax_rate;
        } else {
            $product_data['price'] = Product::getPriceStatic($product_id, true);
            $product_data['list_price'] = Product::getPriceStatic($product_id, true, null, 6, null, false, false);
        }

        if($current_currency->iso_code !== $default_currency->iso_code){
            $product_data['price'] = $product_data['price'] / (float) $current_currency->conversion_rate;
            $product_data['list_price'] = $product_data['list_price'] / (float) $current_currency->conversion_rate;
        }
        $product_data['on_sale'] = $product_data['price'] < $product_data['list_price'];

        $product_data = ProductHelper::getCustomerGroupPrices($product_id, $product, $product_data);
        $product_data = ProductHelper::getSpecificPriceOverride($shop_id, $language_id, $product_id, $product, $product_data);
        return ProductHelper::getUnitPrices($product, $product_data);
    }

    private static function getSpecificPriceOverride($shop_id, $language_id, $product_id, $product, $product_data)
    {
        $sp = SpecificPrice::getSpecificPrice($product_id, $shop_id, null, null, null, 1);
        $bp = $product->base_price ?: $product->price;
        $spPrices = [];
        if(empty($sp)){
            return $product_data;
        }
        foreach ($sp as $p){
            if(!is_array($p)){
                continue;
            }
            if(!array_key_exists('reduction_type', $p) || !array_key_exists('reduction', $p) || !array_key_exists('id_group', $p)){
               continue;
            }
            if (array_key_exists('id_shop_group', $p) && $p['id_shop_group'] != 0) {
                continue;
            }
            if (array_key_exists('id_currency', $p) && $p['id_currency'] != 0) {
                continue;
            }
            if (array_key_exists('id_country', $p) && $p['id_country'] != 0) {
                continue;
            }
            if (array_key_exists('id_group', $p) && $p['id_group'] != 0) {
                continue;
            }
            if (array_key_exists('id_customer', $p) && $p['id_customer'] != 0) {
                continue;
            }
            if (array_key_exists('id_product_attribute', $p) && $p['id_product_attribute'] != 0) {
                continue;
            }
            if($p['reduction_type'] == 'percentage'){
                $tax = ($product->tax_rate / 100) + 1;
                $reduction = 1 - $p['reduction'];
                $spPrices[] = $bp * $tax * $reduction;
            }
            if($p['reduction_type'] == 'amount'){
                $red = $p['reduction_tax'] ? ($p['reduction_tax'] * (($product->tax_rate / 100) + 1)) : $p['reduction_tax'];
                $spPrices[] = $p['price'] - $red;
            }
        }
        if(!empty($spPrices)){
            $product_data['price'] = min($spPrices);
        }
        return $product_data;
    }

    private static function getCustomerGroupPrices($product_id, $product, $product_data)
    {
        $sp = SpecificPrice::getByProductId($product_id);
        $bp = $product->base_price ?: $product->price;
        if(!$sp || !$bp){
            return $product_data;
        }
        foreach ($sp as $p){
            if(!array_key_exists('reduction_type', $p) || !array_key_exists('reduction', $p) || !array_key_exists('id_group', $p)){
                continue;
            }
            if($p['reduction_type'] == 'percentage'){
                $tax = ($product->tax_rate / 100 ) + 1;
                $reduction = 1 - $p['reduction'];
                $product_data['customer_group_price_' . $p['id_group']] = $bp * $tax * $reduction;
            }
            if($p['reduction_type'] == 'amount'){
                $tax = ($p['reduction_tax'] * (($product->tax_rate / 100) + 1 ));
                $product_data['customer_group_price_' . $p['id_group']] = ($bp * $tax) - $p['reduction'];
            }
        }
        return $product_data;
    }

    private static function getUnitPrices($product, $product_data)
    {
        if(empty($product->unity)){
            return $product_data;
        }

        $n = !empty($product->number_of_units) ? (float) $product->number_of_units : 1;
        $u = $product->unity;
        $product_data['unit_price'] = (float) $product_data['price'] / $n;
        $product_data['unit_list_price'] = (float) $product_data['list_price'] / $n;
        $product_data['unit_price_label'] = $u;
        $product_data['base_unit'] = strval(number_format((float)$n, 2)) . " / " . $u;

        return $product_data;
    }

    /**
     * @param $product
     * @param $product_id
     * @return bool
     */
    private static function getAtcStatus($product, $product_id){
        if (property_exists($product, 'active') && $product->active != 1) {
            return false;
        }

        // Disable because of catalog mode enabled in Prestashop settings
        if (property_exists($product, 'catalog_mode') && $product->catalog_mode) {
            return false;
        }

        // Disable because of "Available for order" checkbox unchecked in product settings
        if (property_exists($product, 'available_for_order') && (bool) $product->available_for_order === false) {
            return false;
        }

        $stock = StockAvailable::getQuantityAvailableByProduct($product_id, null);

        // Disable because of stock management
        if (
            Configuration::get('PS_STOCK_MANAGEMENT')
            && !StockAvailable::outOfStock($product_id)
            && ($stock <= 0
                || (property_exists($product, 'minimal_quantity') && $stock - $product->minimal_quantity < 0))
        ) {
            return false;
        }

        // Enable ATC
        return true;
    }

    /**
     * @param $product_id
     * @param $product_data
     * @return array
     */
    private static function getCategoryInfo($product_id, $product_data)
    {
        $categories = Product::getProductCategoriesFull($product_id);
        foreach ($categories as $category) {
            if (array_key_exists('id_category', $category)) {
                $product_data['categories'][] = (int) $category['id_category'];
            }
            if (array_key_exists('name', $category)) {
                $product_data['category_names'][] = $category['name'];
            }
        }
        return $product_data;
    }

    /**
     * @param $shop_id
     * @param $language_id
     * @param $product_id
     * @param $product_data
     * @return mixed
     */
    private static function getProductFeatures($shop_id, $language_id, $product_id, $product_data)
    {
        if(!Configuration::get('CLERK_DATASYNC_PRODUCT_FEATURES', $language_id, null, $shop_id)){
            return $product_data;
        }
        $ff = Product::getFrontFeaturesStatic($language_id, $product_id);
        if(empty($ff)){
            return $product_data;
        }
        $ff_ref = [];
        foreach ($ff as $f){
            if(!isset($f['name'])){
                continue;
            }
            $f['name'] = ProductHelper::handleizeName($f['name']);
            if(!array_key_exists($f['name'], $ff_ref)){
                $ff_ref[$f['name']] = [];
            }
            $ff_ref[$f['name']][] = $f['value'];
        }
        foreach ($ff_ref as $f => $v){
            if( is_array($v) && count($v) === 1 ){
                $v = $v[0];
            }
            if( is_array($v) && count($v) === 0 ){
                continue;
            }
            $product_data[$f] = $v;
        }
        return $product_data;
    }

    /**
     * @param $shop_id
     * @param $language_id
     * @param $product_id
     * @param $product_data
     * @return mixed
     */
    private static function getProductTags($shop_id, $language_id, $product_id, $product_data){
        if(!Configuration::get('CLERK_DATASYNC_PRODUCT_TAGS', $language_id, null, $shop_id)){
            return $product_data;
        }
        $tags = Tag::getProductTags($product_id);
        if(empty($tags)){
            return $product_data;
        }
        $product_data['tags'] = ProductHelper::getFieldMultiLang($tags, $language_id);
        return $product_data;
    }

    private static function getVariantData($context, $shop_id, $language_id, $product_id, $product, $product_data)
    {
        if (!Configuration::get('CLERK_INCLUDE_VARIANT_REFERENCES', $language_id, null, $shop_id)) {
            return $product_data;
        }

        $combos = $product->getAttributeCombinations($language_id, true);
        if(empty($combos)){
            return $product_data;
        }
        $product_data['variants'] = [];
        $product_data['variant_skus'] = [];
        $product_data['variant_prices'] = [];
        $product_data['variant_stocks'] = [];

        $attributeIds = [];
        $attributeLabels = [];

        foreach ($combos as $c){
            if (isset($c['reference']) && !in_array($c['reference'], $product_data['variant_skus'])) {
                $product_data['variant_skus'][] = $c['reference'];
            }
            if (isset($c['id_product_attribute']) && !in_array($c['id_product_attribute'], $product_data['variants'])) {
                $product_data['variants'][] = $c['id_product_attribute'];
            }
            if (isset($c['price'])) {
                $product_data['variant_prices'][] = (float) $c['price'];
            }
            if (isset($c['quantity'])) {
                $product_data['variant_stocks'][] = (int) $c['quantity'];
            }
            $setGroupField = ProductHelper::handleizeName($c['group_name']);
            // ATTRIBUTE VARIATION NAMES, NORMALLY COLORS AND SIZES
            if(!array_key_exists($setGroupField, $attributeLabels)){
                $attributeIds[$setGroupField][] = $c['id_attribute'];
                $attributeLabels[$setGroupField][] = $c['attribute_name'];
            }
            if (!in_array($c['attribute_name'], $attributeLabels[$setGroupField])) {
                $attributeIds[$setGroupField][] = $c['id_attribute'];
                $attributeLabels[$setGroupField][] = $c['attribute_name'];
            }
        }

        $af = ProductHelper::getAdditionalFields($shop_id, $language_id);

        foreach ( $af as $f ){
            if(array_key_exists($f, $attributeLabels)){
                $product_data[$f . "_ids"] = $attributeIds[$f];
                $product_data[$f] = $attributeLabels[$f];
            }
        }

        $product_data['variant_images'] = ProductHelper::getVariantImageUrls($context, $shop_id, $language_id, $product, $product_id);

        return $product_data;
    }

    private static function getChildData($shop_id, $language_id, $product_id, $product_data){
        if(!Pack::isPack($product_id)){
            return $product_data;
        }
        $af = ProductHelper::getAdditionalFields($shop_id, $language_id);
        $pa = ProductHelper::getAttributes($language_id);
        $ch = Pack::getItems($product_id, $language_id);
        foreach ($af as $f) {
            $childAttributes = [];
            foreach ($ch as $c) {

                if(isset($c->id_pack_product_attribute)){
                    try {
                        $combo = new Combination($c->id_pack_product_attribute);
                        $comboAttributes = $combo->getAttributesName($language_id);
                        foreach ($comboAttributes as $cmb){
                            foreach ($pa as $a) {
                                if($a['id_attribute'] !== $cmb['id_attribute']){
                                    continue;
                                }
                                $aName = ProductHelper::handleizeName($a['public_name']);
                                if($aName == $f){
                                    $childAttributes[] = $a['name'];
                                }
                            }
                        }
                    } catch (PrestaShopDatabaseException|PrestaShopException $e) {}
                }

                if(isset($c->{ $f })){
                    $childAttributes[] = $c->{ $f };
                }
            }
            if(empty($childAttributes)){
                continue;
            }

            $product_data['child_' . $f . 's'] = array_values($childAttributes);

        }
        return $product_data;
    }

    private static function getCustomFields($shop_id, $language_id, $product, $product_data)
    {
        $af = ProductHelper::getAdditionalFields($shop_id, $language_id);
        foreach ($af as $f) {
            if(isset($product->{$f})){
                $product_data[$f] = $product->{ $f };
            }
        }
        return $product_data;
    }

    /**
     * @param $context
     * @param $shop_id
     * @param $language_id
     * @param $product_id
     * @param null $product
     * @param null $product_stock
     * @return mixed|void
     */
    public static function buildData($context, $shop_id, $language_id, $product_id, $product = null, $product_stock = null){

        if (!$product && !$product_id) {
            return;
        }

        if (!$product && $product_id) {
            $product = new Product($product_id, $language_id);
        }

        if(!$product_id && $product){
            $product_id = $product->id_product;
        }

        if (!$product->active) {
            return;
        }
        if (isset($product->id_product_attribute) && (!Configuration::get('CLERK_DATASYNC_QUERY_BY_STOCK', $language_id, null, $shop_id) || null === $product_stock || version_compare(_PS_VERSION_, '1.7.8', '>='))){
            $product_stock = StockAvailable::getQuantityAvailableByProduct($product_id, $product->id_product_attribute, $shop_id);
        } else {
            $product_stock = (int) $product->quantity;
        }

        if (!Configuration::get('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', $language_id, null, $shop_id) && $product_stock <= 0) {
            return;
        }

        if (!Configuration::get('CLERK_DATASYNC_INCLUDE_ONLY_LOCAL_STOCK', $language_id, null, $shop_id) && $product_stock <= 0 && !$product->out_of_stock) {
            return;
        }

        $product_data = [
            'id' => $product_id,
            'categories' => [],
            'category_names' => [],
            'sku' => $product->reference,
            'in_stock' => $product_stock > 0,
            'stock' => $product_stock,
            'qty' => $product_stock,
            'date_add' => strtotime($product->date_add),
            'created_at' => strtotime($product->date_add),
        ];

        $product_data = ProductHelper::getCategoryInfo($product_id, $product_data);
        $product_data = ProductHelper::getPriceInfo($shop_id, $language_id, $product_id, $product, $product_data);
        $product_data = ProductHelper::getProductFeatures($shop_id, $language_id, $product_id, $product_data);
        $product_data = ProductHelper::getProductTags($shop_id, $language_id, $product_id, $product_data);

        $product_data['name'] = ProductHelper::getFieldMultiLang($product->name, $language_id, true);
        $product_data['description'] = ProductHelper::getFieldMultiLang($product->description_short, $language_id, true);
        $product_data['description_long'] = ProductHelper::getFieldMultiLang($product->description, $language_id, true);
        $product_data['brand'] = ProductHelper::getBrandName($product, $language_id);
        $product_data['url'] = ProductHelper::getUrl($context, $product_id, $language_id);
        $product_data['image'] = ProductHelper::getImageUrl($product, $product_id, $shop_id, $language_id, $context);
        $product_data['supplier'] = ProductHelper::getSupplierNames($product_id);
        $product_data['atc_enabled'] = ProductHelper::getAtcStatus($product, $product_id);

        $product_data = ProductHelper::getCustomFields($shop_id, $language_id, $product, $product_data);
        $product_data = ProductHelper::getVariantData($context, $shop_id, $language_id, $product_id, $product, $product_data);
        return ProductHelper::getChildData($shop_id, $language_id, $product_id, $product_data);

}
}