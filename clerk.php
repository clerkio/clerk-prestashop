<?php

/**
 * @author Clerk.io
 * @copyright Copyright (c) 2017 Clerk.io
 *
 * @license MIT License
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
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

if (!defined('_PS_VERSION_')) {
    exit;
}


class Clerk extends Module
{
    const TYPE_PAGE = 'page';
    const TYPE_POPUP = 'popup';
    const TYPE_EMBED = 'embed';
    const TYPE_CMS = 'CMS Page';
    const LEVEL_ERROR = 'error';
    const LEVEL_WARN = 'warn';
    const LEVEL_ALL = 'all';
    const LOGGING_TO_FILE = 'file';
    const LOGGING_TO_COLLECT = 'collect';
    const DROPDOWN_POSITIONING = 'left';
    /**
     * @var bool
     */
    protected $settings_updated = false;
    /**
     * @var int
     */
    protected $language_id;
    /**
     * @var int
     */
    protected $shop_id;
    protected $api;
    private $logger;
    protected $language;

    /**
     * Clerk constructor.
     */
    public function __construct()
    {
        require_once(_PS_MODULE_DIR_ . '/clerk/controllers/admin/ClerkLogger.php');
        require_once(_PS_MODULE_DIR_ . '/clerk/controllers/admin/Clerk-Api.php');
        $this->logger = new ClerkLogger();
        $this->api = new Clerk_Api();
        $this->name = 'clerk';
        $this->tab = 'advertising_marketing';
        $this->version = '6.7.6';
        $this->author = 'Clerk';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->controllers = array('added', 'search');

        parent::__construct();
        $this->displayName = $this->l('Clerk');
        $this->description = $this->l('Clerk.io Turns More Browsers Into Buyers');


        //Set shop id
        if (!isset($_SESSION["shop_id"])) {

            $this->shop_id = (Tools::getValue('clerk_shop_select')) ? (int) Tools::getValue('clerk_shop_select') : $this->context->shop->id;
        } else {

            $this->shop_id = $_SESSION["shop_id"];
        }

        //Set language id
        $this->language_id = (Tools::getValue('clerk_language_select')) ? (int) Tools::getValue('clerk_language_select') : $this->context->language->id;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        //Install tab

        $tabId = (int) Tab::getIdFromClassName('AdminClerkDashboard');
        if (!$tabId) {
            $tabId = null;
        }

        $tab = new Tab($tabId);
        $tab->active = 1;
        $tab->name = array();
        $tab->class_name = 'AdminClerkDashboard';

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Clerk';
        }

        $tab->id_parent = (int) Tab::getIdFromClassName('DEFAULT');
        $tab->module = $this->name;
        $tab->icon = 1;

        //Initialize empty settings for all shops and languages
        foreach ($this->getAllShops() as $shop) {
            $emptyValues = array();
            $trueValues = array();
            $falseValues = array();
            $searchTemplateValues = array();
            $standardFacetAttributes = array();
            $liveSearchTemplateValues = array();
            $productTemplateValues = array();
            $powerstepTemplateValues = array();
            $powerstepTypeValues = array();
            $categoryTemplateValues = array();
            $cartTemplateValues = array();
            $exitIntentTemplateValues = array();
            $dropdownNumberValues = array();
            $facetPositionValues = array();
            $facetTitleValues = array();


            $defaultFacetString = [
                0 => "price",
                1 => "categories",
                2 => "on_sale",
                3 => "brand"
            ];
            $defaultClerkFacetsTitle = [
                'price' => 'Price',
                'brand' => 'Brand',
                'categories' => 'Categories',
                'on_sale' => 'On Sale',
            ];
            $defaultClerkFacetsPosition = [
                'price' => ['1'],
                'brand' => ['2'],
                'categories' => ['3'],
                'on_sale' => ['4'],
            ];

            foreach ($this->getAllLanguages($shop['id_shop']) as $language) {
                $defaultHookPositions[$language['id_lang']] = 'displayTop';
                $emptyValues[$language['id_lang']] = '';
                $trueValues[$language['id_lang']] = 1;
                $falseValues[$language['id_lang']] = 0;
                $imageValue[$language['id_lang']] = 'home';
                $dropdownNumberValues[$language['id_lang']] = 1;
                $searchTemplateValues[$language['id_lang']] = 'search-page';
                $standardFacetAttributes[$language['id_lang']] = json_encode($defaultFacetString);
                $liveSearchTemplateValues[$language['id_lang']] = 'live-search';
                $liveSearchSelector[$language['id_lang']] = '.search_query';
                $liveSearchFormSelector[$language['id_lang']] = '#search_widget > form';
                $productTemplateValues[$language['id_lang']] = 'product-page-others-also-bought,product-page-alternatives';
                $powerstepTemplateValues[$language['id_lang']] = 'power-step-others-also-bought,power-step-visitor-complementary,power-step-popular';
                $powerstepTypeValues[$language['id_lang']] = self::TYPE_PAGE;
                $categoryTemplateValues[$language['id_lang']] = 'category-page-popular';
                $cartTemplateValues[$language['id_lang']] = 'cart-others-also-bought';
                $pagesTypeValues[$language['id_lang']] = self::TYPE_CMS;
                $dropdownPositioningValues[$language['id_lang']] = self::DROPDOWN_POSITIONING;
                $loggingLevelValues[$language['id_lang']] = self::LEVEL_ERROR;
                $loggingToValues[$language['id_lang']] = self::LOGGING_TO_FILE;
                $exitIntentTemplateValues[$language['id_lang']] = 'exit-intent';
                $facetPositionValues[$language['id_lang']] = json_encode($defaultClerkFacetsPosition);
                $facetTitleValues[$language['id_lang']] = json_encode($defaultClerkFacetsTitle);
            }

            Configuration::updateValue('CLERK_PUBLIC_KEY', $emptyValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_PRIVATE_KEY', $emptyValues, false, null, $shop['id_shop']);

            // Adding option to switch header hook due to people removing hooks form their themes files. :)
            Configuration::updateValue('CLERK_TRACKING_HOOK_POSITION', $defaultHookPositions, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_SEARCH_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_SEARCH_CATEGORIES', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_SEARCH_NUMBER_CATEGORIES', $dropdownNumberValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_SEARCH_NUMBER_PAGES', $dropdownNumberValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_SEARCH_PAGES_TYPE', $pagesTypeValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_SEARCH_TEMPLATE', $searchTemplateValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_FACETED_NAVIGATION_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_FACETS_ATTRIBUTES', $standardFacetAttributes, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_FACETS_POSITION', $facetPositionValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_FACETS_TITLE', $facetTitleValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_FACETS_DESIGN', $emptyValues, false, null, $shop['id_shop']);


            Configuration::updateValue('CLERK_LIVESEARCH_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_CATEGORIES', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_TEMPLATE', $liveSearchTemplateValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_SELECTOR', $liveSearchSelector, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_FORM_SELECTOR', $liveSearchFormSelector, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_NUMBER_SUGGESTIONS', $dropdownNumberValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_NUMBER_CATEGORIES', $dropdownNumberValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_NUMBER_PAGES', $dropdownNumberValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_PAGES_TYPE', $pagesTypeValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_DROPDOWN_POSITION', $dropdownPositioningValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_POWERSTEP_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_POWERSTEP_TYPE', $powerstepTypeValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_POWERSTEP_TEMPLATES', $powerstepTemplateValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_DATASYNC_USE_REAL_TIME_UPDATES', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DATASYNC_INCLUDE_PAGES', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DATASYNC_PAGE_FIELDS', $emptyValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DATASYNC_INCLUDE_ONLY_LOCAL_STOCK', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DATASYNC_CONTEXTUAL_VAT', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DATASYNC_QUERY_BY_STOCK', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DATASYNC_COLLECT_EMAILS', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DATASYNC_COLLECT_BASKETS', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DATASYNC_SYNC_SUBSCRIBERS', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DATASYNC_FIELDS', $emptyValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DISABLE_ORDER_SYNC', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_INCLUDE_VARIANT_REFERENCES', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DATASYNC_PRODUCT_FEATURES', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_IMAGE_SIZE', $imageValue, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DATASYNC_DISABLE_CUSTOMER_SYNC', $falseValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_EXIT_INTENT_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_EXIT_INTENT_TEMPLATE', $exitIntentTemplateValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_PRODUCT_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_PRODUCT_TEMPLATE', $productTemplateValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_CATEGORY_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_CATEGORY_TEMPLATE', $categoryTemplateValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_CART_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_CART_TEMPLATE', $cartTemplateValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_LOGGING_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LOGGING_LEVEL', $loggingLevelValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LOGGING_TO', $loggingToValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_CART_EXCLUDE_DUPLICATES', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_POWERSTEP_EXCLUDE_DUPLICATES', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_PRODUCT_EXCLUDE_DUPLICATES', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_CATEGORY_EXCLUDE_DUPLICATES', $falseValues, false, null, $shop['id_shop']);
        }

        return parent::install() &&
            $tab->add() &&
            $this->registerHook('top') &&
            $this->registerHook('displayTop') &&
            $this->registerHook('footer') &&
            $this->registerHook('actionCartSave') &&
            $this->registerHook('displayOrderConfirmation') &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayHome') &&
            $this->registerHook('actionProductSave') &&
            $this->registerHook('actionProductDelete') &&
            $this->registerHook('actionUpdateQuantity') &&
            $this->registerHook('displayFooterProduct') &&
            $this->registerHook('displayHeaderCategory') &&
            $this->registerHook('displayShoppingCartFooter') &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->registerHook('displayCartModalFooter');
    }

    /**
     * Get all shops
     *
     * @return array
     */
    private function getAllShops()
    {
        $shops = array();
        $allShops = Shop::getShops();

        foreach ($allShops as $shop) {
            if (isset($shop['id_shop']) && isset($shop['name'])) {
                $shops[] = array(
                    'id_shop' => $shop['id_shop'],
                    'name' => $shop['name']
                );
            }
        }

        return $shops;
    }

    /**
     * Get all languages
     *
     * @param $shop_id
     * @return array
     */
    private function getAllLanguages($shop_id = null)
    {
        if (is_null($shop_id)) {
            $shop_id = $this->shop_id;
        }

        $languages = array();
        $allLanguages = Language::getLanguages(false, $shop_id);

        foreach ($allLanguages as $lang) {
            $languages[] = array(
                'id_lang' => $lang['id_lang'],
                'name' => $lang['name']
            );
        }

        switch ($this->context->language->iso_code) {

            case 'da':
                $this->language = 'Danish';
                break;
            case 'nl':
                $this->language = 'Dutch';
                break;
            case 'en':
                $this->language = 'English';
                break;
            case 'fi':
                $this->language = 'Finnish';
                break;
            case 'fr':
                $this->language = 'French';
                break;
            case 'de':
                $this->language = 'German';
                break;
            case 'hu':
                $this->language = 'Hungarian';
                break;
            case 'it':
                $this->language = 'Italian';
                break;
            case 'no':
                $this->language = 'Norwegian';
                break;
            case 'pt':
                $this->language = 'Portuguese';
                break;
            case 'ro':
                $this->language = 'Romanian';
                break;
            case 'ru':
                $this->language = 'Russian';
                break;
            case 'es':
                $this->language = 'Spanish';
                break;
            case 'sv':
                $this->language = 'Swedish';
                break;
            case 'tr':
                $this->language = 'Turkish';
                break;
        }

        return $languages;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminClerkDashboard');
        $id_tab2 = (int) Tab::getIdFromClassName('AdminModules&configure=clerk');

        if ($id_tab2) {
            $tab = new Tab($id_tab2);
            $tab->delete();
        }

        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }

        Configuration::deleteByName('CLERK_PUBLIC_KEY');
        Configuration::deleteByName('CLERK_PRIVATE_KEY');
        Configuration::deleteByName('CLERK_TRACKING_HOOK_POSITION');
        Configuration::deleteByName('CLERK_LANGUAGE');
        Configuration::deleteByName('CLERK_SEARCH_ENABLED');
        Configuration::deleteByName('CLERK_SEARCH_CATEGORIES');
        Configuration::deleteByName('CLERK_SEARCH_NUMBER_CATEGORIES');
        Configuration::deleteByName('CLERK_SEARCH_NUMBER_PAGES');
        Configuration::deleteByName('CLERK_SEARCH_PAGES_TYPE');
        Configuration::deleteByName('CLERK_SEARCH_TEMPLATE');
        Configuration::deleteByName('CLERK_FACETED_NAVIGATION_ENABLED');
        Configuration::deleteByName('CLERK_FACETS_ATTRIBUTES');
        Configuration::deleteByName('CLERK_FACETS_POSITION');
        Configuration::deleteByName('CLERK_FACETS_TITLE');
        Configuration::deleteByName('CLERK_FACETS_DESIGN');
        Configuration::deleteByName('CLERK_LIVESEARCH_ENABLED');
        Configuration::deleteByName('CLERK_LIVESEARCH_CATEGORIES');
        Configuration::deleteByName('CLERK_LIVESEARCH_TEMPLATE');
        Configuration::deleteByName('CLERK_LIVESEARCH_SELECTOR');
        Configuration::deleteByName('CLERK_LIVESEARCH_FORM_SELECTOR');
        Configuration::deleteByName('CLERK_LIVESEARCH_NUMBER_SUGGESTIONS');
        Configuration::deleteByName('CLERK_LIVESEARCH_NUMBER_CATEGORIES');
        Configuration::deleteByName('CLERK_LIVESEARCH_NUMBER_PAGES');
        Configuration::deleteByName('CLERK_LIVESEARCH_PAGES_TYPE');
        Configuration::deleteByName('CLERK_LIVESEARCH_DROPDOWN_POSITION');
        Configuration::deleteByName('CLERK_POWERSTEP_ENABLED');
        Configuration::deleteByName('CLERK_POWERSTEP_TYPE');
        Configuration::deleteByName('CLERK_POWERSTEP_TEMPLATES');
        Configuration::deleteByName('CLERK_DATASYNC_COLLECT_EMAILS');
        Configuration::deleteByName('CLERK_DATASYNC_COLLECT_BASKETS');
        Configuration::deleteByName('CLERK_DATASYNC_SYNC_SUBSCRIBERS');
        Configuration::deleteByName('CLERK_DATASYNC_DISABLE_CUSTOMER_SYNC');
        Configuration::deleteByName('CLERK_DATASYNC_USE_REAL_TIME_UPDATES');
        Configuration::deleteByName('CLERK_DATASYNC_PAGE_FIELDS');
        Configuration::deleteByName('CLERK_DATASYNC_INCLUDE_PAGES');
        Configuration::deleteByName('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS');
        Configuration::deleteByName('CLERK_DATASYNC_INCLUDE_ONLY_LOCAL_STOCK');
        Configuration::deleteByName('CLERK_DATASYNC_QUERY_BY_STOCK');
        Configuration::deleteByName('CLERK_DATASYNC_CONTEXTUAL_VAT');
        Configuration::deleteByName('CLERK_INCLUDE_VARIANT_REFERENCES');
        Configuration::deleteByName('CLERK_DATASYNC_PRODUCT_FEATURES');
        Configuration::deleteByName('CLERK_IMAGE_SIZE');
        Configuration::deleteByName('CLERK_DISABLE_ORDER_SYNC');
        Configuration::deleteByName('CLERK_DATASYNC_FIELDS');
        Configuration::deleteByName('CLERK_EXIT_INTENT_ENABLED');
        Configuration::deleteByName('CLERK_EXIT_INTENT_TEMPLATE');
        Configuration::deleteByName('CLERK_PRODUCT_ENABLED');
        Configuration::deleteByName('CLERK_PRODUCT_TEMPLATE');
        Configuration::deleteByName('CLERK_CATEGORY_ENABLED');
        Configuration::deleteByName('CLERK_CATEGORY_TEMPLATE');
        Configuration::deleteByName('CLERK_CART_ENABLED');
        Configuration::deleteByName('CLERK_CART_TEMPLATE');
        Configuration::deleteByName('CLERK_LOGGING_ENABLED');
        Configuration::deleteByName('CLERK_LOGGING_LEVEL');
        Configuration::deleteByName('CLERK_LOGGING_TO');
        Configuration::deleteByName('CLERK_CART_EXCLUDE_DUPLICATES');
        Configuration::deleteByName('CLERK_POWERSTEP_EXCLUDE_DUPLICATES');
        Configuration::deleteByName('CLERK_PRODUCT_EXCLUDE_DUPLICATES');
        Configuration::deleteByName('CLERK_CATEGORY_EXCLUDE_DUPLICATES');

        return parent::uninstall();
    }

    /**
     * Save configuration and show form
     */
    public function getContent()
    {
        $output = '';

        $this->processSubmit();

        if ($this->settings_updated) {
            $output .= $this->displayConfirmation($this->l('Settings updated.'));
        }

        return $output . $this->renderForm();
    }

    /**
     * Handle form submission
     */
    public function processSubmit()
    {
        if (Tools::isSubmit('submitClerk')) {
            //Determine if we're changing shop or language
            if (Tools::getValue('ignore_changes')) {
                return true;
            }

            if (
                (Tools::getValue('clerk_language_select') !== false && (int) Tools::getValue('clerk_language_select') === $this->language_id)
                || (Tools::getValue('clerk_language_select') === false
                    && (int) Configuration::get('PS_LANG_DEFAULT') === $this->language_id)
            ) {
                Configuration::updateValue('CLERK_PUBLIC_KEY', array(
                    $this->language_id => trim(Tools::getValue('clerk_public_key', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_PRIVATE_KEY', array(
                    $this->language_id => trim(Tools::getValue('clerk_private_key', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_TRACKING_HOOK_POSITION', array(
                    $this->language_id => trim(Tools::getValue('clerk_tracking_hook_position', 'top'))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LANGUAGE', array(
                    $this->language_id => Tools::getValue('clerk_language', 'auto')
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_SEARCH_ENABLED', array(
                    $this->language_id => Tools::getValue('clerk_search_enabled', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_SEARCH_CATEGORIES', array(
                    $this->language_id => Tools::getValue('clerk_search_categories', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_SEARCH_NUMBER_CATEGORIES', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_search_number_categories', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_SEARCH_NUMBER_PAGES', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_search_number_pages', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_SEARCH_PAGES_TYPE', array(
                    $this->language_id => Tools::getValue('clerk_search_pages_type', 'CMS Page')
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_SEARCH_TEMPLATE', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_search_template', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_FACETED_NAVIGATION_ENABLED', array(
                    $this->language_id => Tools::getValue('clerk_faceted_navigation_enabled', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_FACETS_DESIGN', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_facets_design', ''))
                ), false, null, $this->shop_id);

                /**
                 * kky facets sorting arrays for position
                 */

                $facetPos = Tools::getValue('clerk_facets_position', []);
                $facetTitle = Tools::getValue('clerk_facets_title', []);
                $enabledfacets = Tools::getValue('clerk_facets_attributes', []);

                Configuration::updateValue('CLERK_FACETS_POSITION', array(
                    $this->language_id => json_encode($facetPos)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_FACETS_ATTRIBUTES', array(
                    $this->language_id => json_encode($enabledfacets)
                ), false, null, $this->shop_id);

                /**
                 * kky facets cleaning clerk_facets_title array and removing empty for better smarty compatibility
                 */

                Configuration::updateValue('CLERK_FACETS_TITLE', array(
                    $this->language_id => json_encode($facetTitle)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LIVESEARCH_ENABLED', array(
                    $this->language_id => Tools::getValue('clerk_livesearch_enabled', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LIVESEARCH_CATEGORIES', array(
                    $this->language_id => Tools::getValue('clerk_livesearch_categories', '')
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LIVESEARCH_TEMPLATE', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_livesearch_template', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LIVESEARCH_SELECTOR', array(
                    $this->language_id => Tools::getValue('clerk_livesearch_selector', '.ui-autocomplete-input')
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LIVESEARCH_FORM_SELECTOR', array(
                    $this->language_id => Tools::getValue('clerk_livesearch_form_selector', '#search_widget > form')
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LIVESEARCH_NUMBER_SUGGESTIONS', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_livesearch_number_suggestions', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LIVESEARCH_NUMBER_CATEGORIES', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_livesearch_number_categories', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LIVESEARCH_NUMBER_PAGES', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_livesearch_number_pages', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LIVESEARCH_PAGES_TYPE', array(
                    $this->language_id => Tools::getValue('clerk_livesearch_pages_type', 'CMS Page')
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LIVESEARCH_DROPDOWN_POSITION', array(
                    $this->language_id => Tools::getValue('clerk_livesearch_dropdown_position', 'left')
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_POWERSTEP_ENABLED', array(
                    $this->language_id => Tools::getValue('clerk_powerstep_enabled', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_POWERSTEP_TYPE', array(
                    $this->language_id => Tools::getValue('clerk_powerstep_type', self::TYPE_PAGE)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_POWERSTEP_TEMPLATES', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_powerstep_templates', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_COLLECT_EMAILS', array(
                    $this->language_id => Tools::getValue('clerk_datasync_collect_emails', 1)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_COLLECT_BASKETS', array(
                    $this->language_id => Tools::getValue('clerk_datasync_collect_baskets', 1)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_SYNC_SUBSCRIBERS', array(
                    $this->language_id => Tools::getValue('clerk_datasync_sync_subscribers', 1)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_DISABLE_CUSTOMER_SYNC', array(
                    $this->language_id => Tools::getValue('clerk_datasync_disable_customer_sync', 1)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_USE_REAL_TIME_UPDATES', array(
                    $this->language_id => Tools::getValue('clerk_datasync_use_real_time_updates', 1)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_PAGE_FIELDS', array(
                    $this->language_id => Tools::getValue('clerk_datasync_page_fields', '')
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_INCLUDE_PAGES', array(
                    $this->language_id => Tools::getValue('clerk_datasync_include_pages', 1)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', array(
                    $this->language_id => Tools::getValue('clerk_datasync_include_out_of_stock_products', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_INCLUDE_ONLY_LOCAL_STOCK', array(
                    $this->language_id => Tools::getValue('clerk_datasync_include_only_local_stock', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_QUERY_BY_STOCK', array(
                    $this->language_id => Tools::getValue('clerk_datasync_query_by_stock', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_CONTEXTUAL_VAT', array(
                    $this->language_id => Tools::getValue('clerk_datasync_contextual_vat', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DISABLE_ORDER_SYNC', array(
                    $this->language_id => Tools::getValue('clerk_datasync_disable_order_synchronization', 1)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_INCLUDE_VARIANT_REFERENCES', array(
                    $this->language_id => Tools::getValue('clerk_datasync_include_variant_references', 1)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_PRODUCT_FEATURES', array(
                    $this->language_id => Tools::getValue('clerk_datasync_product_features', 1)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_IMAGE_SIZE', array(
                    $this->language_id => Tools::getValue('clerk_image_size', '')
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_FIELDS', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_datasync_fields', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_EXIT_INTENT_ENABLED', array(
                    $this->language_id => Tools::getValue('clerk_exit_intent_enabled', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_EXIT_INTENT_TEMPLATE', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_exit_intent_template', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_CATEGORY_ENABLED', array(
                    $this->language_id => Tools::getValue('clerk_category_enabled', 0)
                ), false, null, $this->shop_id);
                Configuration::updateValue('CLERK_CATEGORY_TEMPLATE', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_category_template', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_PRODUCT_ENABLED', array(
                    $this->language_id => Tools::getValue('clerk_product_enabled', 0)
                ), false, null, $this->shop_id);
                Configuration::updateValue('CLERK_PRODUCT_TEMPLATE', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_product_template', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_CART_ENABLED', array(
                    $this->language_id => Tools::getValue('clerk_cart_enabled', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_CART_TEMPLATE', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_cart_template', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LOGGING_ENABLED', array(
                    $this->language_id => Tools::getValue('clerk_logging_enabled', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LOGGING_LEVEL', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_logging_level', 'warn'))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LOGGING_TO', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_logging_to', 'collect'))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_CART_EXCLUDE_DUPLICATES', array(
                    $this->language_id => Tools::getValue('clerk_cart_exclude_duplicates', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_POWERSTEP_EXCLUDE_DUPLICATES', array(
                    $this->language_id => Tools::getValue('clerk_powerstep_exclude_duplicates', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_PRODUCT_EXCLUDE_DUPLICATES', array(
                    $this->language_id => Tools::getValue('clerk_product_exclude_duplicates', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_CATEGORY_EXCLUDE_DUPLICATES', array(
                    $this->language_id => Tools::getValue('clerk_category_exclude_duplicates', 0)
                ), false, null, $this->shop_id);
            }
            $this->InitializeSearchPowerstep();
            $this->settings_updated = true;
        }
    }

    /**
     *
     */
    public function InitializeSearchPowerstep()
    {

        if (Configuration::get('CLERK_LOGGING_ENABLED', $this->language_id, null, $this->shop_id) !== '1') {
        } else {

            $livesearch_initiated = Configuration::get('CLERK_LOGGING_LIVESEARCHFIRST', $this->language_id, null, $this->shop_id);

            $search_initiated = Configuration::get('CLERK_LOGGING_SEARCHFIRST', $this->language_id, null, $this->shop_id);

            $powerstep_initiated = Configuration::get('CLERK_LOGGING_POWERSTEPFIRST', $this->language_id, null, $this->shop_id);

            $datasync_collect_emails_initiated = Configuration::get('CLERK_LOGGING_DATASYNC_COLLECT_EMAILS', $this->language_id, null, $this->shop_id);

            $datasync_disable_order_synchronization_initiated = Configuration::get('CLERK_LOGGING_DATASYNC_DISABLE_ORDER_SYNCHRONIZATION', $this->language_id, null, $this->shop_id);

            $exit_intent_initiated = Configuration::get('CLERK_LOGGING_EXIT_INTENT', $this->language_id, null, $this->shop_id);

            $livesearch_enabled = Configuration::get('CLERK_LIVESEARCH_ENABLED', $this->language_id, null, $this->shop_id);

            $search_enabled = Configuration::get('CLERK_SEARCH_ENABLED', $this->language_id, null, $this->shop_id);

            $datasync_collect_emails_enabled = Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $this->language_id, null, $this->shop_id);

            $datasync_collect_baskets_enabled = Configuration::get('CLERK_DATASYNC_COLLECT_BASKETS', $this->language_id, null, $this->shop_id);

            $datasync_sync_subscribers_enabled = Configuration::get('CLERK_DATASYNC_SYNC_SUBSCRIBERS', $this->language_id, null, $this->shop_id);

            $datasync_disable_order_synchronization_enabled = Configuration::get('CLERK_DISABLE_ORDER_SYNC', $this->language_id, null, $this->shop_id);

            $exit_intent_enabled = Configuration::get('CLERK_EXIT_INTENT_ENABLED', $this->language_id, null, $this->shop_id);

            $powerstep_enabled = Configuration::get('CLERK_POWERSTEP_ENABLED', $this->language_id, null, $this->shop_id);

            if ($exit_intent_enabled == '1' && $exit_intent_initiated !== '1') {

                Configuration::updateValue('CLERK_LOGGING_EXIT_INTENT', array(
                    $this->language_id => 1
                ), false, null, $this->shop_id);

                $this->logger->log('Exit Intent initiated', []);
            }

            if ($exit_intent_enabled !== '1' && $exit_intent_initiated == '1') {

                Configuration::updateValue('CLERK_LOGGING_EXIT_INTENT', array(
                    $this->language_id => 0
                ), false, null, $this->shop_id);

                $this->logger->log('Exit Intent uninitiated', []);
            }

            if ($datasync_disable_order_synchronization_enabled == '1' && $datasync_disable_order_synchronization_initiated !== '1') {

                Configuration::updateValue('CLERK_LOGGING_DATASYNC_DISABLE_ORDER_SYNCHRONIZATION', array(
                    $this->language_id => 1
                ), false, null, $this->shop_id);

                $this->logger->log('Data Sync Disable Order Synchronization initiated', []);
            }

            if ($datasync_disable_order_synchronization_enabled !== '1' && $datasync_disable_order_synchronization_initiated == '1') {

                Configuration::updateValue('CLERK_LOGGING_DATASYNC_DISABLE_ORDER_SYNCHRONIZATION', array(
                    $this->language_id => 0
                ), false, null, $this->shop_id);

                $this->logger->log('Data Sync Disable Order Synchronization uninitiated', []);
            }

            if ($datasync_collect_emails_enabled == '1' && $datasync_collect_emails_initiated !== '1') {

                Configuration::updateValue('CLERK_LOGGING_DATASYNC_COLLECT_EMAILS', array(
                    $this->language_id => 1
                ), false, null, $this->shop_id);

                $this->logger->log('Data Sync Collect Emails initiated', []);
            }

            if ($datasync_collect_emails_enabled !== '1' && $datasync_collect_emails_initiated == '1') {

                Configuration::updateValue('CLERK_LOGGING_DATASYNC_COLLECT_EMAILS', array(
                    $this->language_id => 0
                ), false, null, $this->shop_id);

                $this->logger->log('Data Sync Collect Emails uninitiated', []);
            }

            if ($livesearch_enabled == '1' && $livesearch_initiated !== '1') {

                Configuration::updateValue('CLERK_LOGGING_LIVESEARCHFIRST', array(
                    $this->language_id => 1
                ), false, null, $this->shop_id);

                $this->logger->log('Live Search initiated', []);
            }

            if ($livesearch_enabled !== '1' && $livesearch_initiated == '1') {

                Configuration::updateValue('CLERK_LOGGING_LIVESEARCHFIRST', array(
                    $this->language_id => 0
                ), false, null, $this->shop_id);

                $this->logger->log('Live Search uninitiated', []);
            }

            if ($search_enabled == '1' && $search_initiated !== '1') {

                Configuration::updateValue('CLERK_LOGGING_SEARCHFIRST', array(
                    $this->language_id => 1
                ), false, null, $this->shop_id);

                $this->logger->log('Search initiated', []);
            }

            if ($search_enabled !== '1' && $search_initiated == '1') {

                Configuration::updateValue('CLERK_LOGGING_SEARCHFIRST', array(
                    $this->language_id => 0
                ), false, null, $this->shop_id);

                $this->logger->log('Search uninitiated', []);
            }


            if ($powerstep_enabled == '1' && $powerstep_initiated !== '1') {

                Configuration::updateValue('CLERK_LOGGING_POWERSTEPFIRST', array(
                    $this->language_id => 1
                ), false, null, $this->shop_id);

                $this->logger->log('Powerstep initiated', []);
            }

            if ($powerstep_enabled !== '1' && $powerstep_initiated == '1') {

                Configuration::updateValue('CLERK_LOGGING_POWERSTEPFIRST', array(
                    $this->language_id => 0
                ), false, null, $this->shop_id);

                $this->logger->log('Powerstep uninitiated', []);
            }
        }
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function renderForm()
    {
        $booleanType = 'radio';
        $LoggingView = array(
            'type' => 'html',
            'label' => $this->l('Logging View'),
            'name' => 'LoggingViewer',
            'html_content' =>
            '<script></script>',
        );

        //Use switch if available, looks better
        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true) {
            $booleanType = 'switch';
        }

        $shops = $this->getAllShops();
        $languages = $this->getAllLanguages();

        //Language selector
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Shop & Language'),
                    'icon' => 'icon-globe'
                ),
                'input' => array(
                    array(
                        'type' => 'languageselector',
                        'label' => $this->l('Select shop & language'),
                        'name' => 'clerk_language_selector',
                        'shops' => $shops,
                        'current_shop' => $this->shop_id,
                        'languages' => $languages,
                        'current_language' => $this->language_id,
                        'logoImg' => $this->_path . 'views/img/logo.png',
                        'moduleName' => $this->displayName,
                        'moduleVersion' => $this->version,
                        'prestashopVersion' => _PS_VERSION_,
                    )
                )
            )
        );


        //General settings
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('General'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Public Key'),
                        'name' => 'clerk_public_key',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Private Key'),
                        'name' => 'clerk_private_key',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Language'),
                        'name' => 'clerk_language',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                ['name' => 'Auto (' . $this->language . ')', 'Value' => 'auto'],
                                ['name' => 'Danish', 'Value' => 'danish'],
                                ['name' => 'Dutch', 'Value' => 'dutch'],
                                ['name' => 'English', 'Value' => 'english'],
                                ['name' => 'Finnish', 'Value' => 'finnish'],
                                ['name' => 'French', 'Value' => 'french'],
                                ['name' => 'German', 'Value' => 'german'],
                                ['name' => 'Hungarian', 'Value' => 'hungarian'],
                                ['name' => 'Italian', 'Value' => 'italian'],
                                ['name' => 'Norwegian', 'Value' => 'norwegian'],
                                ['name' => 'Portuguese', 'Value' => 'portuguese'],
                                ['name' => 'Romanian', 'Value' => 'romanian'],
                                ['name' => 'Russian', 'Value' => 'russian'],
                                ['name' => 'Spanish', 'Value' => 'spanish'],
                                ['name' => 'Swedish', 'Value' => 'swedish'],
                                ['name' => 'Turkish', 'Value' => 'turkish']
                            ),
                            'id' => 'Value',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Import Url'),
                        'name' => 'clerk_import_url',
                        'readonly' => true,
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Tracking Script Hook Position'),
                        'name' => 'clerk_tracking_hook_position',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                array(
                                    'value' => 'top',
                                    'name' => $this->l('Top')
                                ),
                                array(
                                    'value' => 'displayTop',
                                    'name' => $this->l('displayTop')
                                ),
                                array(
                                    'value' => 'footer',
                                    'name' => $this->l('Footer')
                                )
                            ),
                            'id' => 'value',
                            'name' => 'name',
                        )
                    ),
                ),
            ),
        );

        //Data-sync settings
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Data-sync settings'),
                    'icon' => 'icon-cloud-upload'
                ),
                'input' => array(
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Use Real-time Updates'),
                        'name' => 'clerk_datasync_use_real_time_updates',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_use_real_time_updates_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_use_real_time_updates_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Include Pages'),
                        'name' => 'clerk_datasync_include_pages',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_include_pages_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_include_pages_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Additional Fields For Pages'),
                        'name' => 'clerk_datasync_page_fields',
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Collect Emails'),
                        'name' => 'clerk_datasync_collect_emails',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_collect_emails_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_collect_emails_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Disable Customer Sync'),
                        'name' => 'clerk_datasync_disable_customer_sync',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_disable_customer_sync_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_disable_customer_sync_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Sync Subscribers'),
                        'name' => 'clerk_datasync_sync_subscribers',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_sync_subscribers_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_sync_subscribers_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Collect Baskets'),
                        'name' => 'clerk_datasync_collect_baskets',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_collect_baskets_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_collect_baskets_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Additional Fields Products'),
                        'name' => 'clerk_datasync_fields',
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Disable Order Synchronization'),
                        'name' => 'clerk_datasync_disable_order_synchronization',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_disable_order_synchronization_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_disable_order_synchronization_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Include Variant References'),
                        'name' => 'clerk_datasync_include_variant_references',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_include_variant_references_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_include_variant_references_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Include Product Features'),
                        'name' => 'clerk_datasync_product_features',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_product_features_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_product_features_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Include Out Of Stock Products'),
                        'name' => 'clerk_datasync_include_out_of_stock_products',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_include_out_of_stock_products_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_include_out_of_stock_products_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Only Check Local Stock'),
                        'name' => 'clerk_datasync_include_only_local_stock',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_include_only_local_stock_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_include_only_local_stock_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Get Product Vat by Country'),
                        'name' => 'clerk_datasync_contextual_vat',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_contextual_vat_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_contextual_vat_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Order Products Query By Stock'),
                        'name' => 'clerk_datasync_query_by_stock',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_query_by_stock_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_query_by_stock_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Image Size'),
                        'name' => 'clerk_image_size',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                ['name' => 'Home', 'Value' => 'home'],
                                ['name' => 'Small', 'Value' => 'small'],
                                ['name' => 'Medium', 'Value' => 'medium'],
                                ['name' => 'Large', 'Value' => 'large'],
                                ['name' => 'Thickbox', 'Value' => 'thickbox'],
                                ['name' => 'Category', 'Value' => 'category'],
                                ['name' => 'Scene', 'Value' => 'scene'],
                                ['name' => 'M Scene', 'Value' => 'm_scene']
                            ),
                            'id' => 'Value',
                            'name' => 'name',
                        )
                    ),
                ),
            ),
        );
        //Livesearch settings
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Live search Settings'),
                    'icon' => 'icon-search'
                ),
                'input' => array(
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Enabled'),
                        'name' => 'clerk_livesearch_enabled',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_livesearch_enabled_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_livesearch_enabled_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Include Categories'),
                        'name' => 'clerk_livesearch_categories',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_include_categories_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_include_categories_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Number of Suggestions'),
                        'name' => 'clerk_livesearch_number_suggestions',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                array(
                                    'value' => 1,
                                    'name' => $this->l('1')
                                ),
                                array(
                                    'value' => 2,
                                    'name' => $this->l('2')
                                ),
                                array(
                                    'value' => 3,
                                    'name' => $this->l('3')
                                ),
                                array(
                                    'value' => 4,
                                    'name' => $this->l('4')
                                ),
                                array(
                                    'value' => 5,
                                    'name' => $this->l('5')
                                ),
                                array(
                                    'value' => 6,
                                    'name' => $this->l('6')
                                ),
                                array(
                                    'value' => 7,
                                    'name' => $this->l('7')
                                ),
                                array(
                                    'value' => 8,
                                    'name' => $this->l('8')
                                ),
                                array(
                                    'value' => 9,
                                    'name' => $this->l('9')
                                ),
                                array(
                                    'value' => 10,
                                    'name' => $this->l('10')
                                )
                            ),
                            'id' => 'value',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Number of Categories'),
                        'name' => 'clerk_livesearch_number_categories',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                array(
                                    'value' => 1,
                                    'name' => $this->l('1')
                                ),
                                array(
                                    'value' => 2,
                                    'name' => $this->l('2')
                                ),
                                array(
                                    'value' => 3,
                                    'name' => $this->l('3')
                                ),
                                array(
                                    'value' => 4,
                                    'name' => $this->l('4')
                                ),
                                array(
                                    'value' => 5,
                                    'name' => $this->l('5')
                                ),
                                array(
                                    'value' => 6,
                                    'name' => $this->l('6')
                                ),
                                array(
                                    'value' => 7,
                                    'name' => $this->l('7')
                                ),
                                array(
                                    'value' => 8,
                                    'name' => $this->l('8')
                                ),
                                array(
                                    'value' => 9,
                                    'name' => $this->l('9')
                                ),
                                array(
                                    'value' => 10,
                                    'name' => $this->l('10')
                                )
                            ),
                            'id' => 'value',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Number of Pages'),
                        'name' => 'clerk_livesearch_number_pages',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                array(
                                    'value' => 1,
                                    'name' => $this->l('1')
                                ),
                                array(
                                    'value' => 2,
                                    'name' => $this->l('2')
                                ),
                                array(
                                    'value' => 3,
                                    'name' => $this->l('3')
                                ),
                                array(
                                    'value' => 4,
                                    'name' => $this->l('4')
                                ),
                                array(
                                    'value' => 5,
                                    'name' => $this->l('5')
                                ),
                                array(
                                    'value' => 6,
                                    'name' => $this->l('6')
                                ),
                                array(
                                    'value' => 7,
                                    'name' => $this->l('7')
                                ),
                                array(
                                    'value' => 8,
                                    'name' => $this->l('8')
                                ),
                                array(
                                    'value' => 9,
                                    'name' => $this->l('9')
                                ),
                                array(
                                    'value' => 10,
                                    'name' => $this->l('10')
                                )
                            ),
                            'id' => 'value',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Pages Type'),
                        'name' => 'clerk_livesearch_pages_type',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                array(
                                    'value' => 'CMS Page',
                                    'name' => $this->l('CMS Pages'),
                                ),
                            ),
                            'id' => 'value',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Dropdown Positioning'),
                        'name' => 'clerk_livesearch_dropdown_position',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                array(
                                    'value' => 'left',
                                    'name' => $this->l('Left'),
                                ),
                                array(
                                    'value' => 'center',
                                    'name' => $this->l('Center'),
                                ),
                                array(
                                    'value' => 'right',
                                    'name' => $this->l('Right'),
                                ),
                                array(
                                    'value' => 'below',
                                    'name' => $this->l('Below'),
                                ),
                                array(
                                    'value' => 'off',
                                    'name' => $this->l('Off'),
                                ),
                            ),
                            'id' => 'value',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Template'),
                        'placeholder' => 'Content ID',
                        'name' => 'clerk_livesearch_template',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Search field input selector'),
                        'name' => 'clerk_livesearch_selector',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Search field form selector'),
                        'name' => 'clerk_livesearch_form_selector',
                    ),
                ),
            ),
        );

        //Search settings
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Search Settings'),
                    'icon' => 'icon-search'
                ),
                'input' => array(
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Enabled'),
                        'name' => 'clerk_search_enabled',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_search_enabled_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_search_enabled_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Include Categories'),
                        'name' => 'clerk_search_categories',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_include_search_categories_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_include_search_categories_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Number of Categories'),
                        'name' => 'clerk_search_number_categories',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                array(
                                    'value' => 1,
                                    'name' => $this->l('1')
                                ),
                                array(
                                    'value' => 2,
                                    'name' => $this->l('2')
                                ),
                                array(
                                    'value' => 3,
                                    'name' => $this->l('3')
                                ),
                                array(
                                    'value' => 4,
                                    'name' => $this->l('4')
                                ),
                                array(
                                    'value' => 5,
                                    'name' => $this->l('5')
                                ),
                                array(
                                    'value' => 6,
                                    'name' => $this->l('6')
                                ),
                                array(
                                    'value' => 7,
                                    'name' => $this->l('7')
                                ),
                                array(
                                    'value' => 8,
                                    'name' => $this->l('8')
                                ),
                                array(
                                    'value' => 9,
                                    'name' => $this->l('9')
                                ),
                                array(
                                    'value' => 10,
                                    'name' => $this->l('10')
                                )
                            ),
                            'id' => 'value',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Number of Pages'),
                        'name' => 'clerk_search_number_pages',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                array(
                                    'value' => 1,
                                    'name' => $this->l('1')
                                ),
                                array(
                                    'value' => 2,
                                    'name' => $this->l('2')
                                ),
                                array(
                                    'value' => 3,
                                    'name' => $this->l('3')
                                ),
                                array(
                                    'value' => 4,
                                    'name' => $this->l('4')
                                ),
                                array(
                                    'value' => 5,
                                    'name' => $this->l('5')
                                ),
                                array(
                                    'value' => 6,
                                    'name' => $this->l('6')
                                ),
                                array(
                                    'value' => 7,
                                    'name' => $this->l('7')
                                ),
                                array(
                                    'value' => 8,
                                    'name' => $this->l('8')
                                ),
                                array(
                                    'value' => 9,
                                    'name' => $this->l('9')
                                ),
                                array(
                                    'value' => 10,
                                    'name' => $this->l('10')
                                )
                            ),
                            'id' => 'value',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Pages Type'),
                        'name' => 'clerk_search_pages_type',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                array(
                                    'value' => 'CMS Page',
                                    'name' => $this->l('CMS Pages'),
                                ),
                            ),
                            'id' => 'value',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Template'),
                        'name' => 'clerk_search_template',
                    ),
                ),
            ),
        );

        //Faceted navigation settings
        $facet_input = array();
        $facet_enable = array(
            'type' => $booleanType,
            'label' => $this->l('Enabled'),
            'name' => 'clerk_faceted_navigation_enabled',
            'is_bool' => true,
            'class' => 't',
            'values' => array(
                array(
                    'id' => 'clerk_faceted_navigation_enabled_on',
                    'value' => 1,
                    'label' => $this->l('Enabled')
                ),
                array(
                    'id' => 'clerk_faceted_navigation_enabled_off',
                    'value' => 0,
                    'label' => $this->l('Disabled')
                )
            )
        );

        $facets_design = array(
            'type' => 'text',
            'placeholder' => 'Template ID (optional)',
            'label' => $this->l('Design'),
            'name' => 'clerk_facets_design',
        );
        $facet_attribute_input = array(
            'type' => 'text',
            'placeholder' => 'Attribute name',
            'label' => $this->l('Add facet attributes'),
            'id' => 'clerk_custom_facet_attribute'
        );
        $clerk_custom_facet_script = array(
            'type' => 'html',
            'name' => 'custom_facet_script',
            'html_content' => '
            <script>
                window.addEventListener("load", function(){
                    let custom_facet_input = document.getElementById("clerk_custom_facet_attribute");
                    let facet_table = document.getElementById("facet_table");
                    custom_facet_input.addEventListener("keydown", function(event){
                        if(event.keyCode == 13){
                            event.preventDefault();
                        }
                    });
                    custom_facet_input.addEventListener("keyup", function(event){
                        if(event.keyCode == 13){
                            event.preventDefault();
                            attribute = custom_facet_input.value.trim();
                            custom_facet_input.value = "";
                            template = `<tr class="facets_lines">
                                            <td style="padding: 8px 10px 8px 0px;"><input type="text" name="clerk_facets_attributes[]" value="${attribute}" readonly="" /></td>
                                            <td style="padding-right: 10px;"><input type="text" name="clerk_facets_title[${attribute}][]" value="" /></td>
                                            <td style="padding-right: 10px;"><input type="text" name="clerk_facets_position[${attribute}][]" value="" /></td>
                                            <td style="padding-right: 10px;" onclick="removeFacet();"><i class="icon-remove"></i></td>
                                        </tr>`;
                            facet_table.innerHTML += template;
                            if(document.querySelectorAll("#facets_content td").length == 1){
                                document.querySelector("#facets_content").innerHTML = "<tr><th>Attribute</th><th>Title</th><th>Position</th><th>Delete</th></tr>";
                            }
                        }
                    });
                });
                const removeFacet = () => {
                    let element = event.target;
                    let parent = element.closest("tr");
                    parent.remove();
                }
            </script>
            ',
        );
        array_push($facet_input, $facet_enable);

        $_shop_id = (!empty(Shop::getContextShopID())) ? Shop::getContextShopID() : $this->shop_id;
        $_lang_id = (!empty(Language::getLanguages(true, $_shop_id, true))) ? Language::getLanguages(true, $_shop_id, true)[0] : $this->language_id;
        if (Tools::getValue('clerk_language_select')) {
            $_lang_id = (int) Tools::getValue('clerk_language_select');
        }
        
        if (Configuration::get('CLERK_FACETED_NAVIGATION_ENABLED', $_lang_id, null, $_shop_id) == true && Configuration::get('CLERK_PUBLIC_KEY', $_lang_id, null, $_shop_id) !== "") {

            $facetHTML = '<table style="margin-top:7px" id="facet_table"><tbody id="facets_content">';
            $positions = json_decode(Configuration::get('CLERK_FACETS_POSITION', $_lang_id, null, $_shop_id), true);
            $titles = json_decode(Configuration::get('CLERK_FACETS_TITLE', $_lang_id, null, $_shop_id), true);
            $attributes = json_decode(Configuration::get('CLERK_FACETS_ATTRIBUTES', $_lang_id, null, $_shop_id), true);

            if (is_array($attributes) && count($attributes) > 0) {
                $facetHTML .= '<tr><th>Attribute</th>' .
                    '<th>Title</th>' .
                    '<th>Position</th>' .
                    '<th>Delete</th></tr>';
                foreach ($attributes as $attribute) {
                    $attributeHTML = '<tr class="facets_lines">';
                    $attributeHTML .= '<td style="padding:8px 10px 8px 0px;"><input type="text" name="clerk_facets_attributes[]" value="' . $attribute . '" readonly=""></td>';
                    $attributeHTML .= '<td style="padding-right:10px;"><input type="text" name="clerk_facets_title[' . $attribute . '][]" value="' . $titles[$attribute][0] . '"></td>';
                    $attributeHTML .= '<td style="padding-right:10px;"><input type="text" name="clerk_facets_position[' . $attribute . '][]" value="' . $positions[$attribute][0] . '"></td>';
                    $attributeHTML .= '<td style="padding-right:10px;" onclick="removeFacet();" ><i class="icon-remove"></i></td></tr>';
                    $facetHTML .= $attributeHTML;
                }
            } else {
                $facetHTML .= '<tr><td>Please enter attributes in the input field above, in order to use them as facets</td></tr>';
            }

            $facetHTML .= '</tbody></table>';
            $facettable = array(
                'type' => 'html',
                'label' => $this->l('Facet Attributes'),
                'name' => 'faceted navigation',
                'html_content' => $facetHTML
            );
            array_push($facet_input, $facets_design, $facet_attribute_input, $facettable, $clerk_custom_facet_script);
        }


        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Faceted navigation'),
                    'icon' => 'icon-search'
                ),
                'input' => $facet_input,
            ),
        );

        //Category settings
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Category Settings'),
                    'icon' => 'icon-shopping-cart'
                ),
                'input' => array(
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Enabled'),
                        'name' => 'clerk_category_enabled',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_category_enabled_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_category_enabled_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Templates'),
                        'placeholder' => 'Content ID',
                        'name' => 'clerk_category_template',
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Filter Duplicates'),
                        'name' => 'clerk_category_exclude_duplicates',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_category_exclude_duplicates_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_category_exclude_duplicates_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                )
            ),
        );
        //Product settings
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Product Settings'),
                    'icon' => 'icon-shopping-cart'
                ),
                'input' => array(
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Enabled'),
                        'name' => 'clerk_product_enabled',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_product_enabled_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_product_enabled_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Templates'),
                        'placeholder' => 'Content ID',
                        'name' => 'clerk_product_template',
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Filter Duplicates'),
                        'name' => 'clerk_product_exclude_duplicates',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_product_exclude_duplicates_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_product_exclude_duplicates_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                )
            ),
        );

        //Powerstep settings
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Powerstep Settings'),
                    'icon' => 'icon-shopping-cart'
                ),
                'input' => array(
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Enabled'),
                        'name' => 'clerk_powerstep_enabled',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_powerstep_enabled_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_powerstep_enabled_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Powerstep Type'),
                        'name' => 'clerk_powerstep_type',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                array(
                                    'value' => self::TYPE_PAGE,
                                    'name' => $this->l('Page')
                                ),
                                array(
                                    'value' => self::TYPE_POPUP,
                                    'name' => $this->l('Popup')
                                ),
                                array(
                                    'value' => self::TYPE_EMBED,
                                    'name' => $this->l('Embedded')
                                )
                            ),
                            'id' => 'value',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Templates'),
                        'placeholder' => 'Content ID',
                        'name' => 'clerk_powerstep_templates',
                        'desc' => $this->l('A comma separated list of clerk templates to render')
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Filter Duplicates'),
                        'name' => 'clerk_powerstep_exclude_duplicates',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_powerstep_exclude_duplicates_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_powerstep_exclude_duplicates_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                )
            ),
        );

        //Cart settings
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Cart Settings'),
                    'icon' => 'icon-shopping-cart'
                ),
                'input' => array(
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Enabled'),
                        'name' => 'clerk_cart_enabled',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_cart_enabled_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_cart_enabled_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Templates'),
                        'placeholder' => 'Content ID',
                        'name' => 'clerk_cart_template',
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Filter Duplicates'),
                        'name' => 'clerk_cart_exclude_duplicates',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_cart_exclude_duplicates_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_cart_exclude_duplicates_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                )
            ),
        );

        //Exit intent settings
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Exit Intent Settings'),
                    'icon' => 'icon-shopping-cart'
                ),
                'input' => array(
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Enabled'),
                        'name' => 'clerk_exit_intent_enabled',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_exit_intent_enabled_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_exit_intent_enabled_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Template'),
                        'placeholder' => 'Content ID',
                        'name' => 'clerk_exit_intent_template',
                    ),
                )
            ),
        );
        if (Configuration::get('CLERK_LOGGING_ENABLED', $this->language_id, null, $this->shop_id) == true && Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id) == 'file') {

            $LoggingView = array(
                'type' => 'html',
                'label' => $this->l('Logging View'),
                'name' => 'LoggingViewer',
                'html_content' =>
                '<script>' .
                'function DOMready(fn) {' .
                'if (document.readyState != "loading") {' .
                '   fn();' .
                '} else if (document.addEventListener) {' .
                '    document.addEventListener("DOMContentLoaded", fn);' .
                '} else {' .
                '    document.attachEvent("onreadystatechange", function() {' .
                '    if (document.readyState != "loading")' .
                '        fn();' .
                '    });' .
                '}' .
                '}' .

                'window.DOMready(function() {' .
                'document.getElementById(\'clerk_logging_viewer\').scrollTop = document.getElementById(\'clerk_logging_viewer\').scrollHeight;' .
                '});' .
                '(function () {' .
                'var clerklog = new XMLHttpRequest();' .
                'clerklog.onreadystatechange = function() {' .
                'if (clerklog.readyState == XMLHttpRequest.DONE) {' . // XMLHttpRequest.DONE == 4
                'if (clerklog.status == 200) {' .
                'res = clerklog.responseText;' .
                'document.getElementById(\'clerk_logging_viewer\').innerHTML = res;' .
                '}' .
                'else if (clerklog.status == 400) {' .
                'console.log("There was an error 400");' .
                '}' .
                'else {' .
                'console.log("something else other than 200 was returned");' .
                '}' .
                '}' .
                '};' .

                'clerklog.open("GET", "/modules/clerk/clerk_log.log", true);' .
                'clerklog.send();' .

                'setTimeout(arguments.callee, 5000);' .
                '})();' .
                '</script><div style="height: 300px; white-space:pre-wrap; background: black; color: white; overflow: scroll;" id="clerk_logging_viewer"></div>',
            );
        }

        $ClerkConfirm = <<<CLERKJS

        <script>
        function DOMready(fn) {
            if (document.readyState != "loading") {
                fn();
            } else if (document.addEventListener) {
                document.addEventListener("DOMContentLoaded", fn);
            } else {
                document.attachEvent("onreadystatechange", function() {
                if (document.readyState != "loading")
                    fn();
                });
            }
        }


        class ConfirmDialog {
            constructor({titleText, questionText, trueButtonText, falseButtonText, parent }) {
                this.titleText = titleText || "Title";
                this.questionText = questionText || "Are you sure?";
                this.trueButtonText = trueButtonText || "Yes";
                this.falseButtonText = falseButtonText || "No";
                this.parent = parent || document.body;

                this.dialog = undefined;
                this.trueButton = undefined;
                this.falseButton = undefined;

                this._createDialog();
                this._appendDialog();
            }

            confirm() {
                return new Promise((resolve, reject) => {
                    const somethingWentWrongUponCreation =
                    !this.dialog || !this.trueButton || !this.falseButton;
                    if (somethingWentWrongUponCreation) {
                    reject('Someting went wrong when creating the modal');
                    return;
                    }

                    this.dialog.showModal();
                    this.trueButton.focus();

                    this.trueButton.addEventListener("click", () => {
                    resolve(true);
                    this._destroy();
                    });

                    this.falseButton.addEventListener("click", () => {
                    resolve(false);
                    this._destroy();
                    });
                });
            }
            _createDialog() {
                this.dialog = document.createElement("dialog");
                this.dialog.style.fontFamily = "inherit";
                this.dialog.style.borderRadius = "5px";
                this.dialog.style.border = "0";
                this.dialog.classList.add("confirm-dialog");

                const title = document.createElement("div");
                title.textContent = this.titleText;
                title.classList.add("confirm-dialog-title");
                title.style.fontSize = "22px";
                title.style.lineHeight = "20px";
                title.style.paddingBottom = "15px";

                this.dialog.appendChild(title);

                const question = document.createElement("div");
                question.textContent = this.questionText;
                question.classList.add("confirm-dialog-question");
                question.style.paddingBottom = "15px";
                this.dialog.appendChild(question);

                const buttonGroup = document.createElement("div");
                buttonGroup.classList.add("confirm-dialog-button-group");
                buttonGroup.style.float = "right";
                this.dialog.appendChild(buttonGroup);

                this.falseButton = document.createElement("button");
                this.falseButton.classList.add(
                    "confirm-dialog-button",
                    "confirm-dialog-button--false"
                );
                this.falseButton.type = "button";
                this.falseButton.textContent = this.falseButtonText;
                this.falseButton.style.backgroundColor = "#e74c3c";
                this.falseButton.style.color = "#fff";
                this.falseButton.style.textTransform = "uppercase";
                this.falseButton.style.fontSize = "14px";
                this.falseButton.style.fontWeight = "bold";
                this.falseButton.style.margin = "4px 4px 4px 4px";
                this.falseButton.style.padding = "6px 12px";
                this.falseButton.style.textAlign = "center";
                this.falseButton.style.verticalAlign = "middle";
                this.falseButton.style.borderRadius = "4px";
                this.falseButton.style.minHeight = "1em";
                this.falseButton.style.border = "0";

                buttonGroup.appendChild(this.falseButton);

                this.trueButton = document.createElement("button");
                this.trueButton.classList.add(
                    "confirm-dialog-button",
                    "confirm-dialog-button--true"
                );
                this.trueButton.type = "button";
                this.trueButton.textContent = this.trueButtonText;
                this.trueButton.style.backgroundColor = "#3498db";
                this.trueButton.style.color = "#fff";
                this.trueButton.style.textTransform = "uppercase";
                this.trueButton.style.fontSize = "14px";
                this.trueButton.style.fontWeight = "bold";
                this.trueButton.style.margin = "4px 4px 4px 4px";
                this.trueButton.style.padding = "6px 12px";
                this.trueButton.style.textAlign = "center";
                this.trueButton.style.verticalAlign = "middle";
                this.trueButton.style.borderRadius = "4px";
                this.trueButton.style.minHeight = "1em";
                this.trueButton.style.border = "0";

                buttonGroup.appendChild(this.trueButton);
            }

            _appendDialog() {
                this.parent.appendChild(this.dialog);
            }

            _destroy() {
                this.parent.removeChild(this.dialog);
                delete this;
            }
        }

        var before_logging_level;

        window.DOMready(function() {

            document.getElementById("clerk_logging_level").addEventListener('focus', function () {

                before_logging_level =  document.getElementById("clerk_logging_level").value;

            });

            document.getElementById('clerk_logging_level').addEventListener('change', async () =>{

                if (document.getElementById("clerk_logging_level").value !== 'all') {

                    before_logging_level =  document.getElementById("clerk_logging_level").value;

                    } else {

                        const dialog = new ConfirmDialog({
                                trueButtonText: "I\'m sure",
                                falseButtonText: "Cancel",
                                questionText: "Debug Mode should not be used in production! Are you sure you want to change logging level to Debug Mode ?",
                                titleText: "Changing Logging Level"
                            });


                        const shouldChangeLvl = await dialog.confirm();
                            if (shouldChangeLvl) {
                            // confirm change
                            }else{
                                document.getElementById('clerk_logging_level').value = before_logging_level;
                            }

                    }

            });

        });
        </script>
CLERKJS;

        $Fancybox = array(
            'type' => 'html',
            'name' => 'Fancybox',
            'html_content' => $ClerkConfirm
        );

        if (!_PS_MODE_DEV_) {

            $Debug_message = array(
                'type' => 'html',
                'label' => $this->l(''),
                'name' => 'Debug message',
                'html_content' => '<hr><strong>PrestaShop Debug Mode is disabled</strong>' .
                '<p>When debug mode is disabled, PrestaShop hides a lot of errors and making it impossible for Clerk logger to detect and catch these errors.</p>' .
                '<p>To make it possibel for Clerk logger to catch all errors you have to enable debug mode.</p>' .
                '<p>Debug is not recommended in production in a longer period of time.</p>' .
                '</br><p><strong>When you store is in debug mode</strong></p>' .
                '<ul>' .
                '<li>Caching is disabled.</li>' .
                '<li>Errors will be visible.</li>' .
                '<li>Clerk logger can catch all errors.</li>' .
                '</ul>' .
                '</br><p><strong>Step By Step Guide to enable debug mode</strong></p>' .
                '<ol>' .
                '<li>Please enable PrestaShop Debug Mode.</li>' .
                '<li>Enable Clerk Logging.</li>' .
                '<li>Set the logging level to "ERROR + WARN + DEBUG".</li>' .
                '<li>Set Logging to "my.clerk.io".</li>' .
                '</ol>' .
                '<p>Thanks, that will make it a lot easier for our customer support to help you.</p>' .
                '</br><p><strong>HOW TO ENABLE DEBUG MODE:</strong></p>' .
                '<p>Open config/defines.inc.php and usually at line 29 you will find</p>' .
                '<p>define(\'_PS_MODE_DEV_\', false);</p>' .
                '<p>change it to:</p>' .
                '<p>define(\'_PS_MODE_DEV_\', true);</p>' .
                '<hr>'
            );

            if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {

                $Debug_message = array(
                    'type' => 'html',
                    'label' => $this->l(''),
                    'name' => 'Debug message',
                    'html_content' => '<hr><strong>PrestaShop Debug Mode is disabled</strong>' .
                    '<p>When debug mode is disabled, PrestaShop hides a lot of errors and making it impossible for Clerk logger to detect and catch these errors.</p>' .
                    '<p>To make it possibel for Clerk logger to catch all errors you have to enable debug mode.</p>' .
                    '<p>Debug is not recommended in production in a longer period of time.</p>' .
                    '</br><p><strong>When you store is in debug mode</strong></p>' .
                    '<ul>' .
                    '<li>Caching is disabled.</li>' .
                    '<li>Errors will be visible.</li>' .
                    '<li>Clerk logger can catch all errors.</li>' .
                    '</ul>' .
                    '</br><p><strong>Step By Step Guide to enable debug mode</strong></p>' .
                    '<ol>' .
                    '<li>Please enable PrestaShop Debug Mode.</li>' .
                    '<li>Enable Clerk Logging.</li>' .
                    '<li>Set the logging level to "ERROR + WARN + DEBUG".</li>' .
                    '<li>Set Logging to "my.clerk.io".</li>' .
                    '</ol>' .
                    '<p>Thanks, that will make it a lot easier for our customer support to help you.</p>' .
                    '</br><p><strong>HOW TO ENABLE DEBUG MODE:</strong></p>' .
                    '<p>Advanced Parameters > Performance > DEBUG MODE PANEL > Set it to YES</p><hr>'
                );
            }
        } else {

            $Debug_message = array(
                'type' => 'html',
                'label' => $this->l(''),
                'name' => 'Debug message',
                'html_content' => '<hr><p style="color: red;"><strong>PrestaShop Debug Mode is enabled</strong></p>' .
                '<ul>' .
                '<li style="color: red;">Caching is disabled.</li>' .
                '<li style="color: red;">Errors will be visible.</li>' .
                '<li style="color: red;">Clerk logger can catch all errors.</li>' .
                '<li style="color: red;">Remember to disable it again after use!</li>' .
                '<li style="color: red;">It\'s not best practice to have it enabled in production.</li>' .
                '<li style="color: red;">it\'s only recommended for at very short period af time for debug use.</li>' .
                '</ul>' .
                '</br><p><strong>Step By Step Guide to disable debug mode</strong></p>' .
                '<ol>' .
                '<li>Please disable PrestaShop Debug Mode.</li>' .
                '<li>Keep Clerk Logging enabled.</li>' .
                '<li>Set the logging level to "ERROR + WARN".</li>' .
                '<li>Keep Logging to "my.clerk.io".</li>' .
                '</ol>' .
                '</br><p><strong>HOW TO DISABLE DEBUG MODE:</strong></p>' .
                '<p>Open config/defines.inc.php and usually at line 29 you will find</p>' .
                '<p>define(\'_PS_MODE_DEV_\', true);</p>' .
                '<p>change it to:</p>' .
                '<p>define(\'_PS_MODE_DEV_\', false);</p>' .
                '<hr>'
            );

            if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {

                $Debug_message = array(
                    'type' => 'html',
                    'label' => $this->l(''),
                    'name' => 'Debug message',
                    'html_content' => '<hr><p style="color: red;"><strong>PrestaShop Debug Mode is enabled</strong></p>' .
                    '<ul>' .
                    '<li style="color: red;">Caching is disabled.</li>' .
                    '<li style="color: red;">Errors will be visible.</li>' .
                    '<li style="color: red;">It will be possible for Clerk logger to catch errors.</li>' .
                    '<li style="color: red;">Remember to disable it again after use!</li>' .
                    '<li style="color: red;">It\'s not best practice to have it enabled in production.</li>' .
                    '<li style="color: red;">it\'s only recommended for at very short period af time for debug use.</li>' .
                    '</ul>' .
                    '</br><p><strong>Step By Step Guide to disable debug mode</strong></p>' .
                    '<ol>' .
                    '<li>Please disable PrestaShop Debug Mode.</li>' .
                    '<li>Keep Clerk Logging enabled.</li>' .
                    '<li>Set the logging level to "ERROR + WARN".</li>' .
                    '<li>Keep Logging to "my.clerk.io".</li>' .
                    '</ol>' .
                    '</br><p><strong>HOW TO DISABLE DEBUG MODE:</strong></p>' .
                    '<p>Advanced Parameters > Performance > DEBUG MODE PANEL > Set it to NO</p><hr>'
                );
            }
        }

        //Logging settings
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Logging Settings'),
                    'icon' => 'icon-cloud-upload'
                ),
                'input' => array(
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Enabled'),
                        'name' => 'clerk_logging_enabled',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_logging_enabled_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_logging_enabled_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Logging Level'),
                        'name' => 'clerk_logging_level',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                array(
                                    'value' => self::LEVEL_ERROR,
                                    'name' => $this->l('Only Errors')
                                ),
                                array(
                                    'value' => self::LEVEL_WARN,
                                    'name' => $this->l('Error + Warn')
                                ),
                                array(
                                    'value' => self::LEVEL_ALL,
                                    'name' => $this->l('Error + Warn + Debug Mode')
                                )
                            ),
                            'id' => 'value',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Logging To'),
                        'name' => 'clerk_logging_to',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                array(
                                    'value' => self::LOGGING_TO_FILE,
                                    'name' => $this->l('File')
                                ),
                                array(
                                    'value' => self::LOGGING_TO_COLLECT,
                                    'name' => $this->l('my.clerk.io')
                                )
                            ),
                            'id' => 'value',
                            'name' => 'name',
                        )
                    ),
                    $Debug_message,
                    $LoggingView,
                    $Fancybox

                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));

        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = $lang->id;

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitClerk';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        $helper->module = $this;
        $helper->base_tpl = 'clerkform.tpl';

        if (isset($this->context) && isset($this->context->controller)) {
            $this->context->controller->addJs($this->_path . ' /views/js/clerk.js');
        } else {
            Tools::addJs($this->_path . ' /views/js/clerk.js');
        }

        return $helper->generateForm($this->fields_form);
    }

    /**
     * Get configuration field values
     * @return array
     */

    public function getConfigFieldsValues()
    {
        $_shop_id = (!empty(Shop::getContextShopID())) ? Shop::getContextShopID() : $this->shop_id;
        $_lang_id = (!empty(Language::getLanguages(true, $_shop_id, true))) ? Language::getLanguages(true, $_shop_id, true)[0] : $this->language_id;
        if (Tools::getValue('clerk_language_select')) {
            $_lang_id = (int) Tools::getValue('clerk_language_select');
        }

        $sync_url = explode("module/clerk/version", (string) Context::getContext()->link->getModuleLink('clerk', 'version', [], null, $_lang_id, $_shop_id, false))[0];

        return array(
            'clerk_public_key' => Configuration::get('CLERK_PUBLIC_KEY', $_lang_id, null, $_shop_id),
            'clerk_private_key' => Configuration::get('CLERK_PRIVATE_KEY', $_lang_id, null, $_shop_id),
            'clerk_language' => Configuration::get('CLERK_LANGUAGE', $_lang_id, null, $_shop_id),
            'clerk_tracking_hook_position' => Configuration::get('CLERK_TRACKING_HOOK_POSITION', $_lang_id, null, $_shop_id),
            'clerk_import_url' => $sync_url,
            'clerk_search_enabled' => Configuration::get('CLERK_SEARCH_ENABLED', $_lang_id, null, $_shop_id),
            'clerk_search_categories' => Configuration::get('CLERK_SEARCH_CATEGORIES', $_lang_id, null, $_shop_id),
            'clerk_search_number_categories' => Configuration::get('CLERK_SEARCH_NUMBER_CATEGORIES', $_lang_id, null, $_shop_id),
            'clerk_search_number_pages' => Configuration::get('CLERK_SEARCH_NUMBER_PAGES', $_lang_id, null, $_shop_id),
            'clerk_search_pages_type' => Configuration::get('CLERK_SEARCH_PAGES_TYPE', $_lang_id, null, $_shop_id),
            'clerk_search_template' => Configuration::get('CLERK_SEARCH_TEMPLATE', $_lang_id, null, $_shop_id),
            'clerk_faceted_navigation_enabled' => Configuration::get('CLERK_FACETED_NAVIGATION_ENABLED', $_lang_id, null, $_shop_id),
            'clerk_facets_attributes' => Configuration::get('CLERK_FACETS_ATTRIBUTES', $_lang_id, null, $_shop_id),
            'clerk_facets_design' => Configuration::get('CLERK_FACETS_DESIGN', $_lang_id, null, $_shop_id),
            'clerk_facets_position' => Configuration::get('CLERK_FACETS_POSITION', $_lang_id, null, $_shop_id),
            'clerk_facets_title' => Configuration::get('CLERK_FACETS_TITLE', $_lang_id, null, $_shop_id),
            'clerk_livesearch_enabled' => Configuration::get('CLERK_LIVESEARCH_ENABLED', $_lang_id, null, $_shop_id),
            'clerk_livesearch_categories' => Configuration::get('CLERK_LIVESEARCH_CATEGORIES', $_lang_id, null, $_shop_id),
            'clerk_livesearch_template' => Configuration::get('CLERK_LIVESEARCH_TEMPLATE', $_lang_id, null, $_shop_id),
            'clerk_livesearch_selector' => Configuration::get('CLERK_LIVESEARCH_SELECTOR', $_lang_id, null, $_shop_id),
            'clerk_livesearch_form_selector' => Configuration::get('CLERK_LIVESEARCH_FORM_SELECTOR', $_lang_id, null, $_shop_id),
            'clerk_livesearch_number_suggestions' => Configuration::get('CLERK_LIVESEARCH_NUMBER_SUGGESTIONS', $_lang_id, null, $_shop_id),
            'clerk_livesearch_number_categories' => Configuration::get('CLERK_LIVESEARCH_NUMBER_CATEGORIES', $_lang_id, null, $_shop_id),
            'clerk_livesearch_number_pages' => Configuration::get('CLERK_LIVESEARCH_NUMBER_PAGES', $_lang_id, null, $_shop_id),
            'clerk_livesearch_pages_type' => Configuration::get('CLERK_LIVESEARCH_PAGES_TYPE', $_lang_id, null, $_shop_id),
            'clerk_livesearch_dropdown_position' => Configuration::get('CLERK_LIVESEARCH_DROPDOWN_POSITION', $_lang_id, null, $_shop_id),
            'clerk_powerstep_enabled' => Configuration::get('CLERK_POWERSTEP_ENABLED', $_lang_id, null, $_shop_id),
            'clerk_powerstep_type' => Configuration::get('CLERK_POWERSTEP_TYPE', $_lang_id, null, $_shop_id),
            'clerk_powerstep_templates' => Configuration::get('CLERK_POWERSTEP_TEMPLATES', $_lang_id, null, $_shop_id),
            'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $_lang_id, null, $_shop_id),
            'clerk_datasync_collect_baskets' => Configuration::get('CLERK_DATASYNC_COLLECT_BASKETS', $_lang_id, null, $_shop_id),
            'clerk_datasync_sync_subscribers' => Configuration::get('CLERK_DATASYNC_SYNC_SUBSCRIBERS', $_lang_id, null, $_shop_id),
            'clerk_datasync_disable_customer_sync' => Configuration::get('CLERK_DATASYNC_DISABLE_CUSTOMER_SYNC', $_lang_id, null, $_shop_id),
            'clerk_datasync_use_real_time_updates' => Configuration::get('CLERK_DATASYNC_USE_REAL_TIME_UPDATES', $_lang_id, null, $_shop_id),
            'clerk_datasync_include_pages' => Configuration::get('CLERK_DATASYNC_INCLUDE_PAGES', $_lang_id, null, $_shop_id),
            'clerk_datasync_page_fields' => Configuration::get('CLERK_DATASYNC_PAGE_FIELDS', $_lang_id, null, $_shop_id),
            'clerk_datasync_include_out_of_stock_products' => Configuration::get('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', $_lang_id, null, $_shop_id),
            'clerk_datasync_include_only_local_stock' => Configuration::get('CLERK_DATASYNC_INCLUDE_ONLY_LOCAL_STOCK', $_lang_id, null, $_shop_id),
            'clerk_datasync_contextual_vat' => Configuration::get('CLERK_DATASYNC_CONTEXTUAL_VAT', $_lang_id, null, $_shop_id),
            'clerk_datasync_query_by_stock' => Configuration::get('CLERK_DATASYNC_QUERY_BY_STOCK', $_lang_id, null, $_shop_id),
            'clerk_datasync_disable_order_synchronization' => Configuration::get('CLERK_DISABLE_ORDER_SYNC', $_lang_id, null, $_shop_id),
            'clerk_datasync_include_variant_references' => Configuration::get('CLERK_INCLUDE_VARIANT_REFERENCES', $_lang_id, null, $_shop_id),
            'clerk_datasync_product_features' => Configuration::get('CLERK_DATASYNC_PRODUCT_FEATURES', $_lang_id, null, $_shop_id),
            'clerk_datasync_fields' => Configuration::get('CLERK_DATASYNC_FIELDS', $_lang_id, null, $_shop_id),
            'clerk_image_size' => Configuration::get('CLERK_IMAGE_SIZE', $_lang_id, null, $_shop_id),
            'clerk_exit_intent_enabled' => Configuration::get('CLERK_EXIT_INTENT_ENABLED', $_lang_id, null, $_shop_id),
            'clerk_exit_intent_template' => Configuration::get('CLERK_EXIT_INTENT_TEMPLATE', $_lang_id, null, $_shop_id),
            'clerk_product_enabled' => Configuration::get('CLERK_PRODUCT_ENABLED', $_lang_id, null, $_shop_id),
            'clerk_product_template' => Configuration::get('CLERK_PRODUCT_TEMPLATE', $_lang_id, null, $_shop_id),
            'clerk_category_enabled' => Configuration::get('CLERK_CATEGORY_ENABLED', $_lang_id, null, $_shop_id),
            'clerk_category_template' => Configuration::get('CLERK_CATEGORY_TEMPLATE', $_lang_id, null, $_shop_id),
            'clerk_cart_enabled' => Configuration::get('CLERK_CART_ENABLED', $_lang_id, null, $_shop_id),
            'clerk_cart_template' => Configuration::get('CLERK_CART_TEMPLATE', $_lang_id, null, $_shop_id),
            'clerk_logging_enabled' => Configuration::get('CLERK_LOGGING_ENABLED', $_lang_id, null, $_shop_id),
            'clerk_logging_level' => Configuration::get('CLERK_LOGGING_LEVEL', $_lang_id, null, $_shop_id),
            'clerk_logging_to' => Configuration::get('CLERK_LOGGING_TO', $_lang_id, null, $_shop_id),
            'clerk_cart_exclude_duplicates' => Configuration::get('CLERK_CART_EXCLUDE_DUPLICATES', $_lang_id, null, $_shop_id),
            'clerk_powerstep_exclude_duplicates' => Configuration::get('CLERK_POWERSTEP_EXCLUDE_DUPLICATES', $_lang_id, null, $_shop_id),
            'clerk_product_exclude_duplicates' => Configuration::get('CLERK_PRODUCT_EXCLUDE_DUPLICATES', $_lang_id, null, $_shop_id),
            'clerk_category_exclude_duplicates' => Configuration::get('CLERK_CATEGORY_EXCLUDE_DUPLICATES', $_lang_id, null, $_shop_id),
        );
    }

    public function hookTop($params)
    {

        if (Configuration::get('CLERK_TRACKING_HOOK_POSITION', $this->context->language->id, null, $this->context->shop->id) !== 'top') {
            return;
        }

        switch ($this->context->language->iso_code) {

            case 'da':
                $this->language = 'danish';
                break;
            case 'nl':
                $this->language = 'dutch';
                break;
            case 'en':
                $this->language = 'english';
                break;
            case 'fi':
                $this->language = 'finnish';
                break;
            case 'fr':
                $this->language = 'french';
                break;
            case 'de':
                $this->language = 'german';
                break;
            case 'hu':
                $this->language = 'hungarian';
                break;
            case 'it':
                $this->language = 'italian';
                break;
            case 'no':
                $this->language = 'norwegian';
                break;
            case 'pt':
                $this->language = 'portuguese';
                break;
            case 'ro':
                $this->language = 'romanian';
                break;
            case 'ru':
                $this->language = 'russian';
                break;
            case 'es':
                $this->language = 'spanish';
                break;
            case 'sv':
                $this->language = 'swedish';
                break;
            case 'tr':
                $this->language = 'turkish';
                break;
        }

        if (Configuration::get('CLERK_LANGUAGE', $this->language_id, null, $this->shop_id) != 'auto') {

            $this->language = Configuration::get('CLERK_LANGUAGE', $this->language_id, null, $this->shop_id);
        }

        $this->context->smarty->assign(
            array(
                'clerk_public_key' => Configuration::get('CLERK_PUBLIC_KEY', $this->context->language->id, null, $this->context->shop->id),
                'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $this->context->language->id, null, $this->context->shop->id),
                'clerk_language' => $this->language,
                'customer_logged_in' => ($this->context->customer->logged == 1) ? true : false,
                'customer_group_id' => (Customer::getDefaultGroupId((int) $this->context->customer->id) !== null) ? Customer::getDefaultGroupId((int) $this->context->customer->id) : false,
                'currency_conversion_rate' => Context::getContext()->currency->getConversationRate() !== null ? Context::getContext()->currency->getConversationRate() : 1,
                'currency_symbol' => Context::getContext()->currency->getSign() !== null ? Context::getContext()->currency->getSign() : '',
                'currency_iso' => Context::getContext()->currency->iso_code !== null ? Context::getContext()->currency->iso_code !== null : '',
            )
        );
        $View = $this->display(__FILE__, 'clerk_js.tpl');

        if (Configuration::get('CLERK_SEARCH_ENABLED', $this->context->language->id, null, $this->context->shop->id)) {
            $key = $this->getCacheId('clerksearch-top' . ((!isset($params['hook_mobile']) || !$params['hook_mobile']) ? '' : '-hook_mobile'));
            $this->smarty->assign(
                array(
                    'clerksearch_type' => 'top',
                    'search_query' => (string) Tools::getValue('search_query', ''),
                    'livesearch_enabled' => (bool) Configuration::get('CLERK_LIVESEARCH_ENABLED', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_categories' => (int) Configuration::get('CLERK_LIVESEARCH_CATEGORIES', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_number_categories' => (int) Configuration::get('CLERK_LIVESEARCH_NUMBER_CATEGORIES', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_number_suggestions' => (int) Configuration::get('CLERK_LIVESEARCH_NUMBER_SUGGESTIONS', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_number_pages' => (int) Configuration::get('CLERK_LIVESEARCH_NUMBER_PAGES', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_pages_type' => (string) Configuration::get('CLERK_LIVESEARCH_PAGES_TYPE', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_dropdown_position' => (string) Configuration::get('CLERK_LIVESEARCH_DROPDOWN_POSITION', $this->context->language->id, null, $this->context->shop->id),
                    'search_enabled' => (bool) Configuration::get('CLERK_SEARCH_ENABLED', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_selector' => Configuration::get('CLERK_LIVESEARCH_SELECTOR', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_form_selector' => htmlspecialchars_decode(Configuration::get('CLERK_LIVESEARCH_FORM_SELECTOR', $this->context->language->id, null, $this->context->shop->id)),
                    'baseUrl' => Tools::getHttpHost(true) . __PS_BASE_URI__,
                    'livesearch_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_LIVESEARCH_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),
                )
            );

            $View .= $this->display(__FILE__, 'search-top.tpl', $key);
        }
        if (version_compare(_PS_VERSION_, '1.7.0', '<')) {
            $context = Context::getContext();
            $enabled = (Configuration::get('CLERK_POWERSTEP_ENABLED', $context->language->id, null, $this->context->shop->id) ? true : false);
            if ($enabled) {
                $correctType = (Configuration::get('CLERK_POWERSTEP_TYPE', $context->language->id, null, $this->context->shop->id) == self::TYPE_EMBED) ? true : false;
                if ($correctType) {

                    $Contents = explode(',', Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->context->language->id, null, $this->context->shop->id));

                    $exclude_duplicates_powerstep = (bool) Configuration::get('CLERK_POWERSTEP_EXCLUDE_DUPLICATES', $context->language->id, null, $this->context->shop->id);

                    $this->context->smarty->assign(
                        array(
                            'Contents' => $Contents,
                            'ProductId' => Tools::getValue('id_product'),
                            'ExcludeDuplicates' => $exclude_duplicates_powerstep
                        )
                    );
                    $View .= $this->display(__FILE__, 'powerstep_embedded_blockcart.tpl');
                }
            }
            if (Configuration::get('CLERK_CATEGORY_ENABLED', $context->language->id, null, $this->context->shop->id)) {
                $category_id = Tools::getValue("id_category");

                if ($category_id) {
                    $Contents = explode(',', Configuration::get('CLERK_CATEGORY_TEMPLATE', $this->context->language->id, null, $this->context->shop->id));

                    $exclude_duplicates_category = (bool) Configuration::get('CLERK_CATEGORY_EXCLUDE_DUPLICATES', $context->language->id, null, $this->context->shop->id);

                    $this->context->smarty->assign(
                        array(
                            'Contents' => $Contents,
                            'CategoryId' => $category_id,
                            'ExcludeDuplicates' => $exclude_duplicates_category
                        )
                    );

                    $View .= $this->display(__FILE__, 'category_products_embedded.tpl');
                }
            }
        }
        return $View;
    }

    public function hookDisplayTop($params)
    {

        if (Configuration::get('CLERK_TRACKING_HOOK_POSITION', $this->context->language->id, null, $this->context->shop->id) !== 'displayTop') {
            return;
        }

        switch ($this->context->language->iso_code) {

            case 'da':
                $this->language = 'danish';
                break;
            case 'nl':
                $this->language = 'dutch';
                break;
            case 'en':
                $this->language = 'english';
                break;
            case 'fi':
                $this->language = 'finnish';
                break;
            case 'fr':
                $this->language = 'french';
                break;
            case 'de':
                $this->language = 'german';
                break;
            case 'hu':
                $this->language = 'hungarian';
                break;
            case 'it':
                $this->language = 'italian';
                break;
            case 'no':
                $this->language = 'norwegian';
                break;
            case 'pt':
                $this->language = 'portuguese';
                break;
            case 'ro':
                $this->language = 'romanian';
                break;
            case 'ru':
                $this->language = 'russian';
                break;
            case 'es':
                $this->language = 'spanish';
                break;
            case 'sv':
                $this->language = 'swedish';
                break;
            case 'tr':
                $this->language = 'turkish';
                break;
        }

        if (Configuration::get('CLERK_LANGUAGE', $this->language_id, null, $this->shop_id) != 'auto') {

            $this->language = Configuration::get('CLERK_LANGUAGE', $this->language_id, null, $this->shop_id);
        }

        $this->context->smarty->assign(
            array(
                'clerk_public_key' => Configuration::get('CLERK_PUBLIC_KEY', $this->context->language->id, null, $this->context->shop->id),
                'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $this->context->language->id, null, $this->context->shop->id),
                'clerk_language' => $this->language,
                'customer_logged_in' => ($this->context->customer->logged == 1) ? true : false,
                'customer_group_id' => (Customer::getDefaultGroupId((int) $this->context->customer->id) !== null) ? Customer::getDefaultGroupId((int) $this->context->customer->id) : false,
                'currency_conversion_rate' => Context::getContext()->currency->getConversationRate() !== null ? Context::getContext()->currency->getConversationRate() : 1,
                'currency_symbol' => Context::getContext()->currency->getSign() !== null ? Context::getContext()->currency->getSign() : '',
                'currency_iso' => Context::getContext()->currency->iso_code !== null ? Context::getContext()->currency->iso_code !== null : '',
                )
        );
        $View = $this->display(__FILE__, 'clerk_js.tpl');

        if (Configuration::get('CLERK_SEARCH_ENABLED', $this->context->language->id, null, $this->context->shop->id)) {
            $key = $this->getCacheId('clerksearch-top' . ((!isset($params['hook_mobile']) || !$params['hook_mobile']) ? '' : '-hook_mobile'));
            $this->smarty->assign(
                array(
                    'clerksearch_type' => 'top',
                    'search_query' => (string) Tools::getValue('search_query', ''),
                    'livesearch_enabled' => (bool) Configuration::get('CLERK_LIVESEARCH_ENABLED', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_categories' => (int) Configuration::get('CLERK_LIVESEARCH_CATEGORIES', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_number_categories' => (int) Configuration::get('CLERK_LIVESEARCH_NUMBER_CATEGORIES', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_number_suggestions' => (int) Configuration::get('CLERK_LIVESEARCH_NUMBER_SUGGESTIONS', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_number_pages' => (int) Configuration::get('CLERK_LIVESEARCH_NUMBER_PAGES', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_pages_type' => (string) Configuration::get('CLERK_LIVESEARCH_PAGES_TYPE', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_dropdown_position' => (string) Configuration::get('CLERK_LIVESEARCH_DROPDOWN_POSITION', $this->context->language->id, null, $this->context->shop->id),
                    'search_enabled' => (bool) Configuration::get('CLERK_SEARCH_ENABLED', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_selector' => Configuration::get('CLERK_LIVESEARCH_SELECTOR', $this->context->language->id, null, $this->context->shop->id),
                    'livesearch_form_selector' => htmlspecialchars_decode(Configuration::get('CLERK_LIVESEARCH_FORM_SELECTOR', $this->context->language->id, null, $this->context->shop->id)),
                    'baseUrl' => Tools::getHttpHost(true) . __PS_BASE_URI__,
                    'livesearch_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_LIVESEARCH_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),
                )
            );

            $View .= $this->display(__FILE__, 'search-top.tpl', $key);
        }
        if (version_compare(_PS_VERSION_, '1.7.0', '<')) {
            $context = Context::getContext();
            $enabled = (Configuration::get('CLERK_POWERSTEP_ENABLED', $context->language->id, null, $this->context->shop->id) ? true : false);
            if ($enabled) {
                $correctType = (Configuration::get('CLERK_POWERSTEP_TYPE', $context->language->id, null, $this->context->shop->id) == self::TYPE_EMBED) ? true : false;
                if ($correctType) {

                    $Contents = explode(',', Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->context->language->id, null, $this->context->shop->id));

                    $exclude_duplicates_powerstep = (bool) Configuration::get('CLERK_POWERSTEP_EXCLUDE_DUPLICATES', $context->language->id, null, $this->context->shop->id);

                    $this->context->smarty->assign(
                        array(
                            'Contents' => $Contents,
                            'ProductId' => Tools::getValue('id_product'),
                            'ExcludeDuplicates' => $exclude_duplicates_powerstep
                        )
                    );
                    $View .= $this->display(__FILE__, 'powerstep_embedded_blockcart.tpl');
                }
            }
            if (Configuration::get('CLERK_CATEGORY_ENABLED', $context->language->id, null, $this->context->shop->id)) {
                $category_id = Tools::getValue("id_category");

                if ($category_id) {
                    $Contents = explode(',', Configuration::get('CLERK_CATEGORY_TEMPLATE', $this->context->language->id, null, $this->context->shop->id));

                    $exclude_duplicates_category = (bool) Configuration::get('CLERK_CATEGORY_EXCLUDE_DUPLICATES', $context->language->id, null, $this->context->shop->id);

                    $this->context->smarty->assign(
                        array(
                            'Contents' => $Contents,
                            'CategoryId' => $category_id,
                            'ExcludeDuplicates' => $exclude_duplicates_category
                        )
                    );

                    $View .= $this->display(__FILE__, 'category_products_embedded.tpl');
                }
            }
        }
        return $View;
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookFooter()
    {

        $templateOutput = '';

        if (Configuration::get('CLERK_TRACKING_HOOK_POSITION', $this->context->language->id, null, $this->context->shop->id) === 'footer') {

            switch ($this->context->language->iso_code) {
                case 'da':
                    $this->language = 'danish';
                    break;
                case 'nl':
                    $this->language = 'dutch';
                    break;
                case 'en':
                    $this->language = 'english';
                    break;
                case 'fi':
                    $this->language = 'finnish';
                    break;
                case 'fr':
                    $this->language = 'french';
                    break;
                case 'de':
                    $this->language = 'german';
                    break;
                case 'hu':
                    $this->language = 'hungarian';
                    break;
                case 'it':
                    $this->language = 'italian';
                    break;
                case 'no':
                    $this->language = 'norwegian';
                    break;
                case 'pt':
                    $this->language = 'portuguese';
                    break;
                case 'ro':
                    $this->language = 'romanian';
                    break;
                case 'ru':
                    $this->language = 'russian';
                    break;
                case 'es':
                    $this->language = 'spanish';
                    break;
                case 'sv':
                    $this->language = 'swedish';
                    break;
                case 'tr':
                    $this->language = 'turkish';
                    break;
            }

            if (Configuration::get('CLERK_LANGUAGE', $this->language_id, null, $this->shop_id) != 'auto') {

                $this->language = Configuration::get('CLERK_LANGUAGE', $this->language_id, null, $this->shop_id);
            }

            $this->context->smarty->assign(
                array(
                    'clerk_public_key' => Configuration::get('CLERK_PUBLIC_KEY', $this->context->language->id, null, $this->context->shop->id),
                    'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $this->context->language->id, null, $this->context->shop->id),
                    'clerk_language' => $this->language,
                    'customer_logged_in' => ($this->context->customer->logged == 1) ? true : false,
                    'customer_group_id' => (Customer::getDefaultGroupId((int) $this->context->customer->id) !== null) ? Customer::getDefaultGroupId((int) $this->context->customer->id) : false,
                    'currency_conversion_rate' => Context::getContext()->currency->getConversationRate() !== null ? Context::getContext()->currency->getConversationRate() : 1,
                    'currency_symbol' => Context::getContext()->currency->getSign() !== null ? Context::getContext()->currency->getSign() : '',
                    'currency_iso' => Context::getContext()->currency->iso_code !== null ? Context::getContext()->currency->iso_code !== null : '',
                    )
            );
            $templateOutput .= $this->display(__FILE__, 'clerk_js.tpl');

            if (Configuration::get('CLERK_SEARCH_ENABLED', $this->context->language->id, null, $this->context->shop->id)) {
                $this->smarty->assign(
                    array(
                        'clerksearch_type' => 'top',
                        'search_query' => (string) Tools::getValue('search_query', ''),
                        'livesearch_enabled' => (bool) Configuration::get('CLERK_LIVESEARCH_ENABLED', $this->context->language->id, null, $this->context->shop->id),
                        'livesearch_categories' => (int) Configuration::get('CLERK_LIVESEARCH_CATEGORIES', $this->context->language->id, null, $this->context->shop->id),
                        'livesearch_number_categories' => (int) Configuration::get('CLERK_LIVESEARCH_NUMBER_CATEGORIES', $this->context->language->id, null, $this->context->shop->id),
                        'livesearch_number_suggestions' => (int) Configuration::get('CLERK_LIVESEARCH_NUMBER_SUGGESTIONS', $this->context->language->id, null, $this->context->shop->id),
                        'livesearch_number_pages' => (int) Configuration::get('CLERK_LIVESEARCH_NUMBER_PAGES', $this->context->language->id, null, $this->context->shop->id),
                        'livesearch_pages_type' => (string) Configuration::get('CLERK_LIVESEARCH_PAGES_TYPE', $this->context->language->id, null, $this->context->shop->id),
                        'livesearch_dropdown_position' => (string) Configuration::get('CLERK_LIVESEARCH_DROPDOWN_POSITION', $this->context->language->id, null, $this->context->shop->id),
                        'search_enabled' => (bool) Configuration::get('CLERK_SEARCH_ENABLED', $this->context->language->id, null, $this->context->shop->id),
                        'livesearch_selector' => Configuration::get('CLERK_LIVESEARCH_SELECTOR', $this->context->language->id, null, $this->context->shop->id),
                        'livesearch_form_selector' => htmlspecialchars_decode(Configuration::get('CLERK_LIVESEARCH_FORM_SELECTOR', $this->context->language->id, null, $this->context->shop->id)),
                        'baseUrl' => Tools::getHttpHost(true) . __PS_BASE_URI__,
                        'livesearch_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_LIVESEARCH_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),
                    )
                );

                $templateOutput .= $this->display(__FILE__, 'search-top.tpl');
            }
            if (version_compare(_PS_VERSION_, '1.7.0', '<')) {
                $context = Context::getContext();
                $enabled = (Configuration::get('CLERK_POWERSTEP_ENABLED', $context->language->id, null, $this->context->shop->id) ? true : false);
                if ($enabled) {
                    $correctType = (Configuration::get('CLERK_POWERSTEP_TYPE', $context->language->id, null, $this->context->shop->id) == self::TYPE_EMBED) ? true : false;
                    if ($correctType) {

                        $Contents = explode(',', Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->context->language->id, null, $this->context->shop->id));

                        $exclude_duplicates_powerstep = (bool) Configuration::get('CLERK_POWERSTEP_EXCLUDE_DUPLICATES', $context->language->id, null, $this->context->shop->id);

                        $this->context->smarty->assign(
                            array(
                                'Contents' => $Contents,
                                'ProductId' => Tools::getValue('id_product'),
                                'ExcludeDuplicates' => $exclude_duplicates_powerstep
                            )
                        );
                        $templateOutput .= $this->display(__FILE__, 'powerstep_embedded_blockcart.tpl');
                    }
                }
                if (Configuration::get('CLERK_CATEGORY_ENABLED', $context->language->id, null, $this->context->shop->id)) {
                    $category_id = Tools::getValue("id_category");

                    if ($category_id) {
                        $Contents = explode(',', Configuration::get('CLERK_CATEGORY_TEMPLATE', $this->context->language->id, null, $this->context->shop->id));

                        $exclude_duplicates_category = (bool) Configuration::get('CLERK_CATEGORY_EXCLUDE_DUPLICATES', $context->language->id, null, $this->context->shop->id);

                        $this->context->smarty->assign(
                            array(
                                'Contents' => $Contents,
                                'CategoryId' => $category_id,
                                'ExcludeDuplicates' => $exclude_duplicates_category
                            )
                        );

                        $templateOutput .= $this->display(__FILE__, 'category_products_embedded.tpl');
                    }
                }
            }
        }


        //Determine if we should redirect to powerstep
        $controller = $this->context->controller;
        $cookie = $this->context->cookie;


        //Determine if powerstep is enabled
        if (Configuration::get('CLERK_POWERSTEP_ENABLED', $this->context->language->id, null, $this->context->shop->id)) {
            if ($cookie->clerk_show_powerstep == true) {
                if (Configuration::get('CLERK_POWERSTEP_TYPE', $this->context->language->id, null, $this->context->shop->id) === self::TYPE_PAGE) {
                    $url = $this->context->link->getModuleLink('clerk', 'added', array('id_product' => $cookie->clerk_last_product));

                    //Clear cookies
                    unset($cookie->clerk_show_powerstep);
                    unset($cookie->clerk_last_product);

                    Tools::redirect($url);
                } else {

                    $id_product = $cookie->clerk_last_product;

                    $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);

                    if (!Validate::isLoadedObject($product)) {
                        Tools::redirect('index.php');
                    }

                    $image = Image::getCover($id_product);

                    $templatesConfig = Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->context->language->id, null, $this->context->shop->id);
                    $templates = array_filter(explode(',', $templatesConfig));

                    $exclude_duplicates_powerstep = (bool) Configuration::get('CLERK_POWERSTEP_EXCLUDE_DUPLICATES', $this->context->language->id, null, $this->context->shop->id);

                    $categories = $product->getCategories();
                    $category = reset($categories);

                    $this->context->smarty->assign(
                        array(
                            'templates' => $templates,
                            'product' => $product,
                            'category' => $category,
                            'image' => $image,
                            'order_process' => Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order',
                            'ExcludeDuplicates' => $exclude_duplicates_powerstep
                        )
                    );

                    //Clear cookies
                    unset($cookie->clerk_show_powerstep);
                    unset($cookie->clerk_last_product);

                    $templateOutput .= $this->display(__FILE__, 'powerstep_modal.tpl');
                }
            }
        }

        $is_v16 = true;

        if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
            $is_v16 = false;
        }

        $templatesConfig = Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->context->language->id, null, $this->context->shop->id);
        $templates = array_filter(explode(',', $templatesConfig));

        $id_product = '0';

        if (isset($cookie->clerk_last_product)) {

            $id_product = $cookie->clerk_last_product;
        }

        $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);

        $categories = $product->getCategories();

        $clerk_cart_update = false;
        $clerk_cart_products = '[]';
        if ($cookie->clerk_cart_update == true) {
            $clerk_cart_update = true;
            $clerk_cart_products = $cookie->clerk_cart_products;
        }
        $template_links = new Link();
        //Assign template variables
        $this->context->smarty->assign(
            array(
                'clerk_public_key' => Configuration::get('CLERK_PUBLIC_KEY', $this->context->language->id, null, $this->context->shop->id),
                'clerk_datasync_use_real_time_updates' => Configuration::get('CLERK_DATASYNC_USE_REAL_TIME_UPDATES', $this->context->language->id, null, $this->context->shop->id),
                'clerk_datasync_include_out_of_stock_products' => Configuration::get('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', $this->context->language->id, null, $this->context->shop->id),
                'clerk_datasync_include_only_local_stock' => Configuration::get('CLERK_DATASYNC_INCLUDE_ONLY_LOCAL_STOCK', $this->context->language->id, null, $this->context->shop->id),
                'clerk_datasync_query_by_stock' => Configuration::get('CLERK_DATASYNC_QUERY_BY_STOCK', $this->context->language->id, null, $this->context->shop->id),
                'clerk_datasync_contextual_vat' => Configuration::get('CLERK_DATASYNC_CONTEXTUAL_VAT', $this->context->language->id, null, $this->context->shop->id),
                'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $this->context->language->id, null, $this->context->shop->id),
                'clerk_datasync_collect_baskets' => Configuration::get('CLERK_DATASYNC_COLLECT_BASKETS', $this->context->language->id, null, $this->context->shop->id),
                'clerk_datasync_sync_subscribers' => Configuration::get('CLERK_DATASYNC_SYNC_SUBSCRIBERS', $this->context->language->id, null, $this->context->shop->id),
                'exit_intent_enabled' => (bool) Configuration::get('CLERK_EXIT_INTENT_ENABLED', $this->context->language->id, null, $this->context->shop->id),
                'exit_intent_template' => explode(',', Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_EXIT_INTENT_TEMPLATE', $this->context->language->id, null, $this->context->shop->id)))),
                'product_enabled' => (bool) Configuration::get('CLERK_PRODUCT_ENABLED', $this->context->language->id, null, $this->context->shop->id),
                'product_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_PRODUCT_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),
                'category_enabled' => (bool) Configuration::get('CLERK_CATEGORY_ENABLED', $this->context->language->id, null, $this->context->shop->id),
                'category_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_CATEGORY_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),
                'cart_enabled' => (bool) Configuration::get('CLERK_CART_ENABLED', $this->context->language->id, null, $this->context->shop->id),
                'cart_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_CART_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),
                'powerstep_enabled' => Configuration::get('CLERK_POWERSTEP_ENABLED', $this->context->language->id, null, $this->context->shop->id),
                'powerstep_type' => Configuration::get('CLERK_POWERSTEP_TYPE', $this->context->language->id, null, $this->context->shop->id),
                'clerk_logging_level' => Configuration::get('CLERK_LOGGING_LEVEL', $this->context->language->id, null, $this->context->shop->id),
                'clerk_logging_enabled' => Configuration::get('CLERK_LOGGING_ENABLED', $this->context->language->id, null, $this->context->shop->id),
                'clerk_logging_to' => Configuration::get('CLERK_LOGGING_TO', $this->context->language->id, null, $this->context->shop->id),
                'clerk_collect_cart' => Configuration::get('CLERK_DATASYNC_COLLECT_BASKETS', $this->context->language->id, null, $this->context->shop->id),
                'clerk_cart_exclude_duplicates' => (bool) Configuration::get('CLERK_CART_EXCLUDE_DUPLICATES', $this->context->language->id, null, $this->context->shop->id),
                'clerk_powerstep_exclude_duplicates' => (bool) Configuration::get('CLERK_POWERSTEP_EXCLUDE_DUPLICATES', $this->context->language->id, null, $this->context->shop->id),
                'clerk_product_exclude_duplicates' => (bool) Configuration::get('CLERK_PRODUCT_EXCLUDE_DUPLICATES', $this->context->language->id, null, $this->context->shop->id),
                'clerk_category_exclude_duplicates' => (bool) Configuration::get('CLERK_CATEGORY_EXCLUDE_DUPLICATES', $this->context->language->id, null, $this->context->shop->id),
                'clerk_cart_update' => $clerk_cart_update,
                'clerk_cart_products' => $clerk_cart_products,
                'templates' => $templates,
                'unix' => time(),
                'isv17' => $is_v16,
                'clerk_basket_link' => $template_links->getModuleLink('clerk', 'clerkbasket'),
                'clerk_added_link' => $template_links->getModuleLink('clerk', 'added'),
                'clerk_powerstep_link' => $template_links->getModuleLink('clerk', 'powerstep')
            )
        );

        $this->context->cookie->clerk_cart_update = false;

        $templateOutput .= $this->display(__FILE__, 'visitor_tracking.tpl');

        return $templateOutput;
    }

    /**
     * @param $params
     * @return string
     */
    public function hookDisplayShoppingCartFooter($params)
    {

        if (Configuration::get('CLERK_CART_ENABLED', $this->context->language->id, null, $this->context->shop->id)) {

            $ProductsIds = [];

            $Contents = explode(',', Configuration::get('CLERK_CART_TEMPLATE', $this->context->language->id, null, $this->context->shop->id));

            $exclude_duplicates_cart = (bool) Configuration::get('CLERK_CART_EXCLUDE_DUPLICATES', $this->context->language->id, null, $this->context->shop->id);

            $PreProductIds = $params['cart']->getProducts(true);

            foreach ($PreProductIds as $PreProductId) {

                $ProductsIds[] = $PreProductId['id_product'];
            }
            $ProductsIds = implode(",", $ProductsIds);

            $this->context->smarty->assign(
                array(

                    'Contents' => $Contents,
                    'ProductId' => $ProductsIds,
                    'ExcludeDuplicates' => $exclude_duplicates_cart

                )
            );

            return $this->display(__FILE__, 'related-products.tpl');
        }
    }
    /**
     * @param $params
     * @return string
     */
    public function hookDisplayHeaderCategory($params)
    {

        $context = Context::getContext();

        if (Configuration::get('CLERK_CATEGORY_ENABLED', $context->language->id, null, $this->context->shop->id)) {

            $Contents = explode(',', Configuration::get('CLERK_CATEGORY_TEMPLATE', $this->context->language->id, null, $this->context->shop->id));

            $exclude_duplicates_category = (bool) Configuration::get('CLERK_CATEGORY_EXCLUDE_DUPLICATES', $this->context->language->id, null, $this->context->shop->id);

            $category_id = Tools::getValue("id_category");

            if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
                $this->context->smarty->assign(
                    array(

                        'Contents' => $Contents,
                        'CategoryId' => $category_id,
                        'ExcludeDuplicates' => $exclude_duplicates_category

                    )
                );
            } else {

                $this->context->smarty->assign(
                    array(

                        'Contents' => $Contents,
                        'CategoryId' => $category_id,
                        'ExcludeDuplicates' => $exclude_duplicates_category

                    )
                );
            }


            return $this->display(__FILE__, 'category_products.tpl');
        }
    }
    /**
     * @param $params
     * @return string
     */
    public function hookDisplayFooterProduct($params)
    {

        $context = Context::getContext();

        if (Configuration::get('CLERK_PRODUCT_ENABLED', $context->language->id, null, $this->context->shop->id)) {

            $Contents = explode(',', Configuration::get('CLERK_PRODUCT_TEMPLATE', $this->context->language->id, null, $this->context->shop->id));

            $exclude_duplicates_product = (bool) Configuration::get('CLERK_PRODUCT_EXCLUDE_DUPLICATES', $this->context->language->id, null, $this->context->shop->id);

            if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
                $this->context->smarty->assign(
                    array(

                        'Contents' => $Contents,
                        'ProductId' => $params['product']['id'],
                        'ExcludeDuplicates' => $exclude_duplicates_product

                    )
                );
            } else {

                $this->context->smarty->assign(
                    array(

                        'Contents' => $Contents,
                        'ProductId' => $params['product']->id,
                        'ExcludeDuplicates' => $exclude_duplicates_product

                    )
                );
            }


            return $this->display(__FILE__, 'related-products.tpl');
        }
    }
    /**
     * @param $params
     * @return string
     */
    public function hookDisplayCartModalFooter($params)
    {
        $context = Context::getContext();
        $enabled = (bool) Configuration::get('CLERK_POWERSTEP_ENABLED', $context->language->id, null, $this->context->shop->id);
        $type = (string) Configuration::get('CLERK_POWERSTEP_TYPE', $context->language->id, null, $this->context->shop->id);

        $exclude_duplicates_powerstep = (bool) Configuration::get('CLERK_POWERSTEP_EXCLUDE_DUPLICATES', $this->context->language->id, null, $this->context->shop->id);

        if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
            $Contents = explode(',', Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->context->language->id, null, $this->context->shop->id));
            $this->context->smarty->assign(
                array(

                    'Contents' => $Contents,
                    'ProductId' => Tools::getValue('id_product'),
                    'Enabled' => $enabled,
                    'Type' => $type,
                    'ExcludeDuplicates' => $exclude_duplicates_powerstep

                )
            );
            return $this->display(__FILE__, 'powerstep_embedded17.tpl');
        }
    }
    /**
     * Hook cart save action
     */
    public function hookActionCartSave()
    {

        $cookie = $this->context->cookie;

        if (Tools::getValue('add')) {
            $this->context->cookie->clerk_show_powerstep = true;
            $this->context->cookie->clerk_last_product = Tools::getValue('id_product');
        }

        $collect_baskets = Configuration::get('CLERK_DATASYNC_COLLECT_BASKETS', $this->context->language->id, null, $this->context->shop->id);

        if ($collect_baskets) {

            if ($this->context->cart) {

                $cart_products = $this->context->cart->getProducts();

                $cart_product_ids = array();

                foreach ($cart_products as $product)
                    $cart_product_ids[] = (int) $product['id_product'];

                if ($this->context->customer->email) {
                    $Endpoint = 'https://api.clerk.io/v2/log/basket/set';

                    $data_string = json_encode([
                        'key' => Configuration::get('CLERK_PUBLIC_KEY', $this->context->language->id, null, $this->context->shop->id),
                        'products' => $cart_product_ids,
                        'email' => $this->context->customer->email
                    ]);

                    $curl = curl_init();

                    curl_setopt($curl, CURLOPT_URL, $Endpoint);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
                    curl_exec($curl);
                } else {
                    if (isset($cookie->clerk_cart_products)) {

                        if ($cookie->clerk_cart_products != json_encode($cart_product_ids)) {

                            $this->context->cookie->clerk_cart_update = true;
                            $this->context->cookie->clerk_cart_products = json_encode($cart_product_ids);
                        }
                    } else {

                        $this->context->cookie->clerk_cart_update = true;
                        $this->context->cookie->clerk_cart_products = json_encode($cart_product_ids);
                    }
                }
            }
        }
    }

    /**
     * Add sales tracking to order confirmation
     *
     * @param $params
     *
     * @return mixed
     */
    public function hookDisplayOrderConfirmation($params)
    {
        $order = isset($params['order']) ? $params['order'] : $params['objOrder'];

        if ($order) {
            $products = $order->getProducts();

            $productArray = array();

            $discounts = $order->total_discounts_tax_incl;

            $product_cart_count = 0;
            foreach ($products as $product) {
                $qty = (int) $product['product_quantity'];
                $product_cart_count += $qty;
            }

            $discount_per_product = $discounts / $product_cart_count;

            foreach ($products as $product) {
                $productArray[] = array(
                    'id' => $product['id_product'],
                    'quantity' => $product['product_quantity'],
                    'price' => $product['product_price_wt'] - $discount_per_product,
                );
                $_product_id = $product['id_product'];
                $_product = new Product($_product_id, $this->context->language->id);
                // group product get and update parent
                if (Pack::isPacked($_product_id) && method_exists(Pack::class, 'getPacksContainingItem')) {
                    $PackParents = Pack::getPacksContainingItem($_product_id, $_product->id_pack_product_attribute, $this->context->language->id);
                    foreach ($PackParents as $PackParent) {
                        $productRaw = new Product($PackParent->id, $this->context->language->id);
                        $this->api->addProduct($productRaw, $productRaw->id);
                    }
                }
                $this->api->addProduct($_product, $_product_id);
            }

            $this->context->smarty->assign(
                array(
                    'clerk_order_id' => $order->id,
                    'clerk_customer_email' => $this->context->customer->email,
                    'clerk_products' => json_encode($productArray),
                    'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $this->context->language->id, null, $this->context->shop->id),
                )
            );

            return $this->display(__FILE__, 'sales_tracking.tpl');
        }
    }

    /**
     * Add clerk css to backend
     *
     * @param $arr
     */
    public function hookActionAdminControllerSetMedia($params)
    {
        if (isset($this->context) && isset($this->context->controller)) {
            $this->context->controller->addCss($this->_path . 'views/css/clerk.css');
        } else {
            Tools::addCSS($this->_path . '/views/css/clerk.css');
        }
    }

    /**
     * Render powerstep modal for PS 1.7
     *
     * @param Cart $cart
     * @param $id_product
     * @param $id_product_attribute
     * @return mixed
     * @throws Exception
     */
    public function renderModal(Cart $cart, $id_product, $id_product_attribute)
    {
        $CartPresenter = PrestaShop\PrestaShop\Adapter\Cart\CartPresenter;
        $data = (new $CartPresenter)->present($cart);
        $product = null;

        foreach ($data['products'] as $p) {
            if ($p['id_product'] == $id_product && $p['id_product_attribute'] == $id_product_attribute) {
                $product = $p;
                break;
            }
        }

        $contentConfig = Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->context->language->id, null, $this->context->shop->id);
        $contents = array_filter(explode(',', $contentConfig));
        $exclude_duplicates_powerstep = (bool) Configuration::get('CLERK_POWERSTEP_EXCLUDE_DUPLICATES', $this->context->language->id, null, $this->context->shop->id);

        foreach ($contents as $key => $content) {

            $contents[$key] = str_replace(' ', '', $content);
        }

        $category = $product['id_category_default'];

        $this->smarty->assign(
            array(
                'product' => $product,
                'category' => $category,
                'cart' => $data,
                'cart_url' => $this->getCartSummaryURL(),
                'contents' => $contents,
                'ExcludeDuplicates' => $exclude_duplicates_powerstep
            )
        );

        return $this->fetch('module:clerk/views/templates/front/powerstepmodal.tpl');
    }

    /**
     * Get URL for cart page
     *
     * @return string
     */
    private function getCartSummaryURL()
    {
        return $this->context->link->getPageLink(
            'cart',
            null,
            $this->context->language->id,
            array(
                'action' => 'show'
            ),
            false,
            null,
            true
        );
    }

    public function hookActionProductDelete($params)
    {

        if (Configuration::get('CLERK_DATASYNC_USE_REAL_TIME_UPDATES', $this->language_id, null, $this->shop_id) != '0') {

            $product_id = $params['id_product'];

            $this->api->removeProduct($product_id);
        }
    }

    public function hookActionProductSave($params)
    {

        $product_id = $params['id_product'];
        $product = $params['product'];

        // group product get and update parent
        if (Pack::isPacked($product_id) && method_exists(Pack::class, 'getPacksContainingItem')) {
            $PackParents = Pack::getPacksContainingItem($product_id, $product->id_pack_product_attribute, $this->language_id);
            foreach ($PackParents as $PackParent) {
                $productRaw = new Product($PackParent->id, $this->language_id);
                $this->api->addProduct($productRaw, $productRaw->id);
            }
        }

        $this->api->addProduct($product, $product_id);
    }

    public function hookActionUpdateQuantity($params)
    {
        if (Configuration::get('CLERK_DATASYNC_USE_REAL_TIME_UPDATES', $this->language_id, null, $this->shop_id) != '0') {

            if (Configuration::get('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', $this->language_id, null, $this->shop_id) != '1' && Configuration::get('CLERK_DATASYNC_INCLUDE_ONLY_LOCAL_STOCK', $this->language_id, null, $this->shop_id) != '0') {
                if ($params['quantity'] <= 0) {
                    $this->api->removeProduct($params['id_product']);
                } else {
                    $this->api->addProduct(0, $params['id_product'], $params['quantity']);
                }
            }
        }
    }

    function isJSON($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
}