<?php
/**
 * @author Clerk.io
 * @copyright Copyright (c) 2017 Clerk.io
 *
 * @license MIT License
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
     * ClerkProductModuleFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        require_once(_PS_MODULE_DIR_ . $this->module->name . '/controllers/admin/ClerkLogger.php');
        require_once(_PS_MODULE_DIR_ . $this->module->name . '/helpers/Product.php');

        $this->shop_id = $this->getShopId();
        $this->language_id = $this->getLanguageId();
        $this->logger = new ClerkLogger();
    }

    /**
     * Get response
     *
     * @return array
     */
    public function getJsonResponse()
    {
        try {
            header('User-Agent: ClerkExtensionBot Prestashop/v' . _PS_VERSION_ . ' Clerk/v' . Module::getInstanceByName('clerk')->version . ' PHP/v' . phpversion());
            $language_id = $this->getLanguageId();
            $shop_id = $this->getShopId();
            $offset = $this->offset;
            $limit = $this->limit;

            $sql = $this->getCollectionQuery($shop_id, $language_id, $limit, $offset);
            $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

            $context = Context::getContext();

            $response = [];
            foreach ($products as $product) {
                $item = ProductHelper::buildData($context, $shop_id, $language_id, $product['id_product']);
                if(!$item){
                    continue;
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

            if (Configuration::get('CLERK_INCLUDE_VARIANT_REFERENCES', $this->getLanguageId(), null, $this->getShopId())) {
                $default[] = 'variants';
                $default[] = 'variant_images';
                $default[] = 'variant_skus';
                $default[] = 'variant_prices';
                $default[] = 'variant_stocks';
            }

            $fields = ProductHelper::getAdditionalFields($this->getShopId(), $this->getLanguageId());

            return array_merge($default, $fields);

        } catch (Exception $e) {

            $this->logger->error('ERROR getDefaultFields', ['error' => $e->getMessage()]);

        }
    }

    /**
     * @param $shop_id
     * @param $language_id
     * @param $limit
     * @param $offset
     * @return string
     */
    protected function getCollectionQuery($shop_id, $language_id, $limit, $offset)
    {

        /* Get Products SQL in order to get the overselling parameter, in addition to the normal values. */

        $active = ' AND ( (ps.active IS NULL AND p.active = 1) OR (p.active IS NULL AND ps.active = 1) OR (p.active = 1 AND ps.active = 1) )';

        if (Configuration::get('CLERK_DATASYNC_STATUS_SCOPE_SHOP', $language_id, null, $shop_id)) {
            $active = ' AND p.active = 1';
        }

        if (Configuration::get('CLERK_DATASYNC_INCLUDE_ONLY_LOCAL_STOCK', $language_id, null, $shop_id)) {
            $active .= ' AND ((ps.available_for_order IS NULL AND p.available_for_order = 1) OR (p.available_for_order IS NULL AND ps.available_for_order = 1) OR (p.available_for_order = 1 AND ps.available_for_order = 1))';
        }

        if (Configuration::get('CLERK_DATASYNC_QUERY_BY_STOCK', $language_id, null, $->shop_id)) {
            return "SELECT p.id_product, p.reference, m.name as 'manufacturer_name', pl.link_rewrite, p.date_add,
                pl.description, pl.description_short, pl.name, p.visibility, psa.quantity as 'quantity',
                ps.active as 'shop_active', p.active as 'product_active', p.ean13,
                ps.available_for_order as 'shop_available', p.available_for_order as 'product_available',
                ps.show_price as 'shop_show_price', p.show_price as 'product_show_price'
                FROM " . _DB_PREFIX_ . "product p
                LEFT JOIN " . _DB_PREFIX_ . "product_lang pl ON (p.id_product = pl.id_product)
                LEFT JOIN " . _DB_PREFIX_ . "category_product cp ON (p.id_product = cp.id_product)
                LEFT JOIN " . _DB_PREFIX_ . "category_lang cl ON (cp.id_category = cl.id_category)
                LEFT JOIN " . _DB_PREFIX_ . "manufacturer m ON (p.id_manufacturer = m.id_manufacturer)
                LEFT JOIN " . _DB_PREFIX_ . "stock_available psa ON (p.id_product = psa.id_product)
                LEFT JOIN " . _DB_PREFIX_ . "product_shop ps ON (p.id_product = ps.id_product)
                WHERE pl.id_lang = " . $language_id . " AND cl.id_lang = " . $language_id . "
                AND pl.id_shop = " . $shop_id . " AND cl.id_shop = " . $shop_id . "
                AND ps.id_shop = " . $shop_id . $active . "
                GROUP BY p.id_product
                ORDER BY quantity desc
                LIMIT " . $offset . "," . $limit;
        }

        return "SELECT p.id_product, p.reference, m.name as 'manufacturer_name', pl.link_rewrite, p.date_add,
            pl.description, pl.description_short, pl.name, p.visibility, p.ean13,
            ps.active as 'shop_active', p.active as 'product_active',
            ps.available_for_order as 'shop_available', p.available_for_order as 'product_available',
            ps.show_price as 'shop_show_price', p.show_price as 'product_show_price'
            FROM " . _DB_PREFIX_ . "product p
            LEFT JOIN " . _DB_PREFIX_ . "product_lang pl ON (p.id_product = pl.id_product)
            LEFT JOIN " . _DB_PREFIX_ . "category_product cp ON (p.id_product = cp.id_product)
            LEFT JOIN " . _DB_PREFIX_ . "category_lang cl ON (cp.id_category = cl.id_category)
            LEFT JOIN " . _DB_PREFIX_ . "manufacturer m ON (p.id_manufacturer = m.id_manufacturer)
            LEFT JOIN " . _DB_PREFIX_ . "product_shop ps ON (p.id_product = ps.id_product)
            WHERE pl.id_lang = " . $language_id . " AND cl.id_lang = " . $language_id . "
            AND pl.id_shop = " . $shop_id . " AND cl.id_shop = " . $shop_id . "
            AND ps.id_shop = " . $shop_id . $active . "
            GROUP BY p.id_product
            ORDER BY p.id_product asc
            LIMIT " . $offset . "," . $limit;


    }

}
