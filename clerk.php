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

use PrestaShop\PrestaShop\Adapter\Cart\CartPresenter;

if (!defined('_PS_VERSION_')) {
    exit;
}


class Clerk extends Module
{
    const TYPE_PAGE = 'page';
    const TYPE_POPUP = 'popup';
    const LEVEL_ERROR = 'error';
    const LEVEL_WARN = 'warn';
    const LEVEL_ALL = 'all';
    const LOGGING_TO_FILE = 'file';
    const LOGGING_TO_COLLECT = 'collect';
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
        $this->version = '5.3.1';
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

            $this->shop_id = (!empty(Tools::getValue('clerk_shop_select'))) ? (int)Tools::getValue('clerk_shop_select') : $this->context->shop->id;

        } else {

            $this->shop_id = $_SESSION["shop_id"];

        }

        //Set language id
        $this->language_id = (!empty(Tools::getValue('clerk_language_select'))) ? (int)Tools::getValue('clerk_language_select') : $this->context->language->id;
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
            $liveSearchTemplateValues = array();
            $powerstepTemplateValues = array();
            $powerstepTypeValues = array();
            $exitIntentTemplateValues = array();

            foreach ($this->getAllLanguages($shop['id_shop']) as $language) {
                $emptyValues[$language['id_lang']] = '';
                $trueValues[$language['id_lang']] = 1;
                $falseValues[$language['id_lang']] = 0;
                $searchTemplateValues[$language['id_lang']] = 'search-page';
                $liveSearchTemplateValues[$language['id_lang']] = 'live-search';
                $powerstepTemplateValues[$language['id_lang']] = 'power-step-others-also-bought,power-step-visitor-complementary,power-step-popular';
                $powerstepTypeValues[$language['id_lang']] = self::TYPE_PAGE;
                $exitIntentTemplateValues[$language['id_lang']] = 'exit-intent';
            }

            Configuration::updateValue('CLERK_PUBLIC_KEY', $emptyValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_PRIVATE_KEY', $emptyValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_SEARCH_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_SEARCH_TEMPLATE', $searchTemplateValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_LIVESEARCH_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_CATEGORIES', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_TEMPLATE', $liveSearchTemplateValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_NUMBER_SUGGESTIONS', $liveSearchTemplateValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_NUMBER_CATEGORIES', $liveSearchTemplateValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_NUMBER_PAGES', $liveSearchTemplateValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_PAGES_TYPE', $liveSearchTemplateValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_DROPDOWN_POSITION', $liveSearchTemplateValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_POWERSTEP_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_POWERSTEP_TYPE', $powerstepTypeValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_POWERSTEP_TEMPLATES', $powerstepTemplateValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_DATASYNC_COLLECT_EMAILS', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DATASYNC_FIELDS', $emptyValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DISABLE_ORDER_SYNC', $falseValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_EXIT_INTENT_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_EXIT_INTENT_TEMPLATE', $exitIntentTemplateValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_PRODUCT_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_PRODUCT_TEMPLATE', $exitIntentTemplateValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_CART_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_CART_TEMPLATE', $exitIntentTemplateValues, false, null, $shop['id_shop']);
        }

        return parent::install() &&
            $tab->add() &&
            $this->registerHook('top') &&
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
            $this->registerHook('displayShoppingCartFooter') &&
            $this->registerHook('actionAdminControllerSetMedia');

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
            $shops[] = array(
                'id_shop' => $shop['id_shop'],
                'name' => $shop['name']
            );
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
        $id_tab = (int)Tab::getIdFromClassName('AdminClerkDashboard');
        $id_tab2 = (int)Tab::getIdFromClassName('AdminModules&configure=clerk');

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
        Configuration::deleteByName('CLERK_LANGUAGE');
        Configuration::deleteByName('CLERK_SEARCH_ENABLED');
        Configuration::deleteByName('CLERK_SEARCH_TEMPLATE');
        Configuration::deleteByName('CLERK_LIVESEARCH_ENABLED');
        Configuration::deleteByName('CLERK_LIVESEARCH_CATEGORIES');
        Configuration::deleteByName('CLERK_LIVESEARCH_TEMPLATE');
        Configuration::deleteByName('CLERK_LIVESEARCH_NUMBER_SUGGESTIONS');
        Configuration::deleteByName('CLERK_LIVESEARCH_NUMBER_CATEGORIES');
        Configuration::deleteByName('CLERK_LIVESEARCH_PAGES');
        Configuration::deleteByName('CLERK_POWERSTEP_ENABLED');
        Configuration::deleteByName('CLERK_POWERSTEP_TYPE');
        Configuration::deleteByName('CLERK_POWERSTEP_TEMPLATES');
        Configuration::deleteByName('CLERK_DATASYNC_COLLECT_EMAILS');
        Configuration::deleteByName('CLERK_DATASYNC_USE_REAL_TIME_UPDATES');
        Configuration::deleteByName('CLERK_DATASYNC_PAGE_FIELDS');
        Configuration::deleteByName('CLERK_DATASYNC_INCLUDE_PAGES');
        Configuration::deleteByName('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS');
        Configuration::deleteByName('CLERK_DISABLE_ORDER_SYNC');
        Configuration::deleteByName('CLERK_DATASYNC_FIELDS');
        Configuration::deleteByName('CLERK_EXIT_INTENT_ENABLED');
        Configuration::deleteByName('CLERK_EXIT_INTENT_TEMPLATE');
        Configuration::deleteByName('CLERK_PRODUCT_ENABLED');
        Configuration::deleteByName('CLERK_PRODUCT_TEMPLATE');
        Configuration::deleteByName('CLERK_CART_ENABLED');
        Configuration::deleteByName('CLERK_CART_TEMPLATE');
        Configuration::deleteByName('CLERK_LOGGING_ENABLED');
        Configuration::deleteByName('CLERK_LOGGING_LEVEL');
        Configuration::deleteByName('CLERK_LOGGING_TO');

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
            if (!empty(Tools::getValue('ignore_changes'))) {
                return true;
            }

            if ((Tools::getValue('clerk_language_select') !== false && (int)Tools::getValue('clerk_language_select') === $this->language_id)
                || (Tools::getValue('clerk_language_select') === false
                    && (int)Configuration::get('PS_LANG_DEFAULT') === $this->language_id)) {
                Configuration::updateValue('CLERK_PUBLIC_KEY', array(
                    $this->language_id => trim(Tools::getValue('clerk_public_key', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_PRIVATE_KEY', array(
                    $this->language_id => trim(Tools::getValue('clerk_private_key', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LANGUAGE', array(
                    $this->language_id => Tools::getValue('clerk_language', 'auto')
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_SEARCH_ENABLED', array(
                    $this->language_id => Tools::getValue('clerk_search_enabled', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_SEARCH_TEMPLATE', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_search_template', ''))
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

                Configuration::updateValue('CLERK_DISABLE_ORDER_SYNC', array(
                    $this->language_id => Tools::getValue('clerk_datasync_disable_order_synchronization', 1)
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
        $LoggingView = '';

        $modules = scandir(_PS_MODULE_DIR_);

        $modules_array = [];
        $show_warnings = false;

        foreach ($modules as $module) {

            $exclude = ['.', '..', '.htaccess', 'index.php'];

            $modules_for_warn = [
                //'clerk' => ['message' => 'This module can interfear with how we inject our instant search.', 'link' => 'https://clerk.io'],
                //'statssales' => ['message' => 'This module can interfear with how we inject our instant search.', 'link' => 'https://clerk.io'],
                //'statssearch' => ['message' => 'This module can interfear with how we inject our instant search.', 'link' => 'https://clerk.io']
            ];

            if (!in_array($module, $exclude )) {

                $modules_array[] = Module::getInstanceByName($module)->name;

            }

        }
        $ClerkWarn_html = '';
        $count = 0;
        foreach ($modules_array as $module) {

            if (array_key_exists($module, $modules_for_warn )) {

                $count++;

                if ($count == 1){
                    echo '<script>function RemoveWarn(module) {'.
                        'var element = document.getElementById("module_warn_"+module);'.
                        'element.classList.add("remove");'.
                        'setTimeout(() => {  element.classList.add("remove-next"); }, 500);'.
                        'setTimeout(() => {  element.parentNode.removeChild(element); }, 1000);'.
                        '}</script>';
                    echo '<style>#fieldset_0 > div:nth-child(2) > div:nth-child(1) > div:nth-child(1){margin-left: 0px; width: 100%;}.remove-next{transform: translateX(-150%)!important;-webkit-transform: translateX(-150%)!important; transition: all 2s ease-in-out!important;}.remove{transform: scale(1.05); transition: all .3s ease-in-out!important;}#close:hover{cursor: pointer;}#close{position: absolute;background:#fbfbfb;color:#555;top: -5px;right: -5px;border-radius: 20px;display: inline-block;border: 2px solid #555;height: 20px;width: 20px;text-align: center;font-weight: 600;}.warning_header {font-size: 13px; margin: 0px!important; color:#555;}.clerk-alert-warning:hover{transform: scale(1.01);}.clerk-alert-warning{display: inline-block; margin-bottom: 15px; position: relative; transition: all .1s ease-in-out; padding: 10px; display: inline-block; width: 100%; background-color: #fff!important; border-radius: 6px; box-shadow: 0px .2em .8em 0 rgba(0,0,0,.09); border-left: 5px solid #e74c3c;}.clerk-btn{float:right; padding: 2px 3px!important;}</style>';
                }

                $show_warnings = true;
                $modules_info = Module::getInstanceByName($module);

                $ClerkWarn_html .= '<div id="module_warn_'.$module.'" class="clerk-alert-warning">'.
                    '<div onclick="RemoveWarn(\''.$module.'\');" id="close">'.
                    'X'.
                    '</div>'.
                    '<b>'.
                    '<p class="warning_header">'.$modules_info->displayName. 'v '.$modules_info->version.' is installed</p>'.
                        '</b>'.
                    '<p style="display: inline-block;" class="">'.$modules_for_warn[$module]['message'].'</p>'.
                    '<a target="_blank" class="btn btn-primary clerk-btn" href="'.$modules_for_warn[$module]['link'].'">Read more here</a>'.
                    '</div>';

            }

        }
        $ClerkWarn = array(
            'type' => 'html',
            'name' => 'Warnings',
            'html_content' => $ClerkWarn_html
        );
        if ($show_warnings) {
            //Warning box
            $this->fields_form[] = array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Warnings'),
                        'icon' => 'icon-cogs'
                    ),
                    'input' => array(
                        $ClerkWarn
                    )
                )
            );
        }

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
                                    ['name' => 'Auto ('.$this->language.')','Value' => 'auto'],
                                    ['name' => 'Danish','Value' => 'danish'],
                                    ['name' => 'Dutch','Value' => 'dutch'],
                                    ['name' => 'English','Value' => 'english'],
                                    ['name' => 'Finnish','Value' => 'finnish'],
                                    ['name' => 'French','Value' => 'french'],
                                    ['name' => 'German','Value' => 'german'],
                                    ['name' => 'Hungarian','Value' => 'hungarian'],
                                    ['name' => 'Italian','Value' => 'italian'],
                                    ['name' => 'Norwegian','Value' => 'norwegian'],
                                    ['name' => 'Portuguese','Value' => 'portuguese'],
                                    ['name' => 'Romanian','Value' => 'romanian'],
                                    ['name' => 'Russian','Value' => 'russian'],
                                    ['name' => 'Spanish','Value' => 'spanish'],
                                    ['name' => 'Swedish','Value' => 'swedish'],
                                    ['name' => 'Turkish','Value' => 'turkish']
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
                        'type' => 'text',
                        'label' => $this->l('Additional Fields'),
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
                        'type' => 'text',
                        'label' => $this->l('Template'),
                        'name' => 'clerk_search_template',
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
                        'name' => 'clerk_livesearch_template',
                    ),
                ),
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
                                )
                            ),
                            'id' => 'value',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Templates'),
                        'name' => 'clerk_powerstep_templates',
                        'desc' => $this->l('A comma separated list of clerk templates to render')
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
                        'name' => 'clerk_exit_intent_template',
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
                        'name' => 'clerk_cart_template',
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
                        'name' => 'clerk_product_template',
                    ),
                )
            ),
        );

        if (Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id) == 'file') {

            $LoggingView = array(
                'type' => 'html',
                'label' => $this->l('Logging View'),
                'name' => 'LoggingViewer',
                'html_content' => '<script src="https://code.jquery.com/jquery-3.4.1.min.js"' .
                    'integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>' .
                    '<script type="text/javascript">' .
                    '$(document).ready(function() {' .
                    'document.getElementById(\'logger_view\').scrollTop = document.getElementById(\'logger_view\').scrollHeight;' .
                    '});' .
                    '(function () {' .
                    '$.ajax({' .
                    'url: "/modules/clerk/clerk_log.log", success: function (data) {' .
                    'document.getElementById(\'clerk_logging_viewer\').innerHTML = data;' .
                    '}, dataType: "html"' .
                    '});' .
                    'setTimeout(arguments.callee, 5000);' .
                    '})();' .
                    '</script><div style="height: 300px; white-space:pre-wrap; background: black; color: white; overflow: scroll;" id="clerk_logging_viewer"></div>',
            );

        }

        $ClerkConfirm = <<<CLERKJS

        <script>
        jQuery(document).ready(function () {

        var before_logging_level;
        $('#clerk_logging_level').focus(function () {

        before_logging_level = $('#clerk_logging_level').val();

        }).change(function () {

        if ($('#clerk_logging_level').val() !== 'all') {

        before_logging_level = $('#clerk_logging_level').val();

        } else {

        $.confirm({
        boxWidth: '30%',
        useBootstrap: false,
        title: 'Changing Logging Level',
        content: 'Debug Mode should not be used in production! Are you sure you want to change logging level to Debug Mode ?',
        buttons: {
        Cancel: {
        text: 'Cancel',
        btnClass: 'btn-red',
        keys: ['enter', 'shift'],
        action: function () {
        document.querySelector('#clerk_logging_level [value="' + before_logging_level + '"]').selected = true;
        }
        },
        Confirm: {
        text: 'I\'m sure',
        btnClass: 'btn-blue',
        keys: ['enter', 'shift'],
        action: function () {

        }
        }
        }
        });

        }

        });
        });
        </script>
CLERKJS;

        $Fancybox = array(
            'type' => 'html',
            'name' => 'Fancybox',
            'html_content' => '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">' .
                '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>' . $ClerkConfirm
        );

        if (!_PS_MODE_DEV_) {

            $Debug_message = array(
                'type' => 'html',
                'label' => $this->l(''),
                'name' => 'Debug message',
                'html_content' => '<hr><strong>PrestaShop Debug Mode is disabled</strong>'.
                    '<p>When debug mode is disabled, PrestaShop hides a lot of errors and making it impossible for Clerk logger to detect and catch these errors.</p>'.
                    '<p>To make it possibel for Clerk logger to catch all errors you have to enable debug mode.</p>'.
                    '<p>Debug is not recommended in production in a longer period of time.</p>'.
                    '</br><p><strong>When you store is in debug mode</strong></p>'.
                    '<ul>'.
                    '<li>Caching is disabled.</li>'.
                    '<li>Errors will be visible.</li>'.
                    '<li>Clerk logger can catch all errors.</li>'.
                    '</ul>'.
                    '</br><p><strong>Step By Step Guide to enable debug mode</strong></p>'.
                    '<ol>'.
                    '<li>Please enable PrestaShop Debug Mode.</li>'.
                    '<li>Enable Clerk Logging.</li>'.
                    '<li>Set the logging level to "ERROR + WARN + DEBUG".</li>'.
                    '<li>Set Logging to "my.clerk.io".</li>'.
                    '</ol>'.
                    '<p>Thanks, that will make it a lot easier for our customer support to help you.</p>'.
                    '</br><p><strong>HOW TO ENABLE DEBUG MODE:</strong></p>'.
                    '<p>Open config/defines.inc.php and usually at line 29 you will find</p>'.
                    '<p>define(\'_PS_MODE_DEV_\', false);</p>'.
                    '<p>change it to:</p>'.
                    '<p>define(\'_PS_MODE_DEV_\', true);</p>'.
                    '<hr>'
            );

            if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {

                $Debug_message = array(
                    'type' => 'html',
                    'label' => $this->l(''),
                    'name' => 'Debug message',
                    'html_content' => '<hr><strong>PrestaShop Debug Mode is disabled</strong>'.
                        '<p>When debug mode is disabled, PrestaShop hides a lot of errors and making it impossible for Clerk logger to detect and catch these errors.</p>'.
                        '<p>To make it possibel for Clerk logger to catch all errors you have to enable debug mode.</p>'.
                        '<p>Debug is not recommended in production in a longer period of time.</p>'.
                        '</br><p><strong>When you store is in debug mode</strong></p>'.
                        '<ul>'.
                        '<li>Caching is disabled.</li>'.
                        '<li>Errors will be visible.</li>'.
                        '<li>Clerk logger can catch all errors.</li>'.
                        '</ul>'.
                        '</br><p><strong>Step By Step Guide to enable debug mode</strong></p>'.
                        '<ol>'.
                        '<li>Please enable PrestaShop Debug Mode.</li>'.
                        '<li>Enable Clerk Logging.</li>'.
                        '<li>Set the logging level to "ERROR + WARN + DEBUG".</li>'.
                        '<li>Set Logging to "my.clerk.io".</li>'.
                        '</ol>'.
                        '<p>Thanks, that will make it a lot easier for our customer support to help you.</p>'.
                        '</br><p><strong>HOW TO ENABLE DEBUG MODE:</strong></p>'.
                        '<p>Advanced Parameters > Performance > DEBUG MODE PANEL > Set it to YES</p><hr>'
                );

            }

        } else {

            $Debug_message = array(
                'type' => 'html',
                'label' => $this->l(''),
                'name' => 'Debug message',
                'html_content' => '<hr><p style="color: red;"><strong>PrestaShop Debug Mode is enabled</strong></p>'.
                    '<ul>'.
                    '<li style="color: red;">Caching is disabled.</li>'.
                    '<li style="color: red;">Errors will be visible.</li>'.
                    '<li style="color: red;">Clerk logger can catch all errors.</li>'.
                    '<li style="color: red;">Remember to disable it again after use!</li>'.
                    '<li style="color: red;">It\'s not best practice to have it enabled in production.</li>>'.
                    '<li style="color: red;">it\'s only recommended for at very short period af time for debug use.</li>'.
                    '</ul>'.
                    '</br><p><strong>Step By Step Guide to disable debug mode</strong></p>'.
                    '<ol>'.
                    '<li>Please disable PrestaShop Debug Mode.</li>'.
                    '<li>Keep Clerk Logging enabled.</li>'.
                    '<li>Set the logging level to "ERROR + WARN".</li>'.
                    '<li>Keep Logging to "my.clerk.io".</li>'.
                    '</ol>'.
                    '</br><p><strong>HOW TO DISABLE DEBUG MODE:</strong></p>'.
                    '<p>Open config/defines.inc.php and usually at line 29 you will find</p>'.
                    '<p>define(\'_PS_MODE_DEV_\', true);</p>'.
                    '<p>change it to:</p>'.
                    '<p>define(\'_PS_MODE_DEV_\', false);</p>'.
                    '<hr>'
            );

            if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {

                $Debug_message = array(
                    'type' => 'html',
                    'label' => $this->l(''),
                    'name' => 'Debug message',
                    'html_content' => '<hr><p style="color: red;"><strong>PrestaShop Debug Mode is enabled</strong></p>'.
                        '<ul>'.
                        '<li style="color: red;">Caching is disabled.</li>'.
                        '<li style="color: red;">Errors will be visible.</li>'.
                        '<li style="color: red;">It will be possible for Clerk logger to catch errors.</li>'.
                        '<li style="color: red;">Remember to disable it again after use!</li>'.
                        '<li style="color: red;">It\'s not best practice to have it enabled in production.</li>'.
                        '<li style="color: red;">it\'s only recommended for at very short period af time for debug use.</li>'.
                        '</ul>'.
                        '</br><p><strong>Step By Step Guide to disable debug mode</strong></p>'.
                        '<ol>'.
                        '<li>Please disable PrestaShop Debug Mode.</li>'.
                        '<li>Keep Clerk Logging enabled.</li>'.
                        '<li>Set the logging level to "ERROR + WARN".</li>'.
                        '<li>Keep Logging to "my.clerk.io".</li>'.
                        '</ol>'.
                        '</br><p><strong>HOW TO DISABLE DEBUG MODE:</strong></p>'.
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
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

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
        return array(
            'clerk_public_key' => Configuration::get('CLERK_PUBLIC_KEY', $this->language_id, null, $this->shop_id),
            'clerk_private_key' => Configuration::get('CLERK_PRIVATE_KEY', $this->language_id, null, $this->shop_id),
            'clerk_language' => Configuration::get('CLERK_LANGUAGE', $this->language_id, null, $this->shop_id),
            'clerk_import_url' => _PS_BASE_URL_,
            'clerk_search_enabled' => Configuration::get('CLERK_SEARCH_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_search_template' => Configuration::get('CLERK_SEARCH_TEMPLATE', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_enabled' => Configuration::get('CLERK_LIVESEARCH_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_categories' => Configuration::get('CLERK_LIVESEARCH_CATEGORIES', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_template' => Configuration::get('CLERK_LIVESEARCH_TEMPLATE', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_number_suggestions' => Configuration::get('CLERK_LIVESEARCH_NUMBER_SUGGESTIONS', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_number_categories' => Configuration::get('CLERK_LIVESEARCH_NUMBER_CATEGORIES', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_number_pages' => Configuration::get('CLERK_LIVESEARCH_NUMBER_PAGES', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_pages_type' => Configuration::get('CLERK_LIVESEARCH_PAGES_TYPE', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_dropdown_position' => Configuration::get('CLERK_LIVESEARCH_DROPDOWN_POSITION', $this->language_id, null, $this->shop_id),
            'clerk_powerstep_enabled' => Configuration::get('CLERK_POWERSTEP_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_powerstep_type' => Configuration::get('CLERK_POWERSTEP_TYPE', $this->language_id, null, $this->shop_id),
            'clerk_powerstep_templates' => Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->language_id, null, $this->shop_id),
            'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $this->language_id, null, $this->shop_id),
            'clerk_datasync_use_real_time_updates' => Configuration::get('CLERK_DATASYNC_USE_REAL_TIME_UPDATES', $this->context->language->id, null, $this->shop_id),
            'clerk_datasync_include_pages' => Configuration::get('CLERK_DATASYNC_INCLUDE_PAGES', $this->context->language->id, null, $this->shop_id),
            'clerk_datasync_page_fields' => Configuration::get('CLERK_DATASYNC_PAGE_FIELDS', $this->context->language->id, null, $this->shop_id),
            'clerk_datasync_include_out_of_stock_products' => Configuration::get('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', $this->context->language->id, null, $this->shop_id),
            'clerk_datasync_disable_order_synchronization' => Configuration::get('CLERK_DISABLE_ORDER_SYNC', $this->language_id, null, $this->shop_id),
            'clerk_datasync_fields' => Configuration::get('CLERK_DATASYNC_FIELDS', $this->language_id, null, $this->shop_id),
            'clerk_exit_intent_enabled' => Configuration::get('CLERK_EXIT_INTENT_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_exit_intent_template' => Configuration::get('CLERK_EXIT_INTENT_TEMPLATE', $this->language_id, null, $this->shop_id),
            'clerk_product_enabled' => Configuration::get('CLERK_PRODUCT_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_product_template' => Configuration::get('CLERK_PRODUCT_TEMPLATE', $this->language_id, null, $this->shop_id),
            'clerk_cart_enabled' => Configuration::get('CLERK_CART_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_cart_template' => Configuration::get('CLERK_CART_TEMPLATE', $this->language_id, null, $this->shop_id),
            'clerk_logging_enabled' => Configuration::get('CLERK_LOGGING_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_logging_level' => Configuration::get('CLERK_LOGGING_LEVEL', $this->language_id, null, $this->shop_id),
            'clerk_logging_to' => Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id),
        );
    }

    public function hookTop($params)
    {

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

        $this->context->smarty->assign(array(
            'clerk_public_key' => Configuration::get('CLERK_PUBLIC_KEY', $this->context->language->id, null, $this->context->shop->id),
            'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $this->context->language->id, null, $this->context->shop->id),
            'language' => $this->language
        ));
        $View =  $this->display(__FILE__, 'clerk_js.tpl');

        if (Configuration::get('CLERK_SEARCH_ENABLED', $this->context->language->id, null, $this->context->shop->id)) {
            $key = $this->getCacheId('clerksearch-top' . ((!isset($params['hook_mobile']) || !$params['hook_mobile']) ? '' : '-hook_mobile'));
            $this->smarty->assign(array(
                'clerksearch_type' => 'top',
                'search_query' => (string)Tools::getValue('search_query', ''),
                'livesearch_enabled' => (bool)Configuration::get('CLERK_LIVESEARCH_ENABLED', $this->context->language->id, null, $this->context->shop->id),
                'livesearch_categories' => (int)Configuration::get('CLERK_LIVESEARCH_CATEGORIES', $this->context->language->id, null, $this->context->shop->id),
                'livesearch_number_categories' => (int)Configuration::get('CLERK_LIVESEARCH_NUMBER_CATEGORIES', $this->context->language->id, null, $this->context->shop->id),
                'livesearch_number_suggestions' => (int)Configuration::get('CLERK_LIVESEARCH_NUMBER_SUGGESTIONS', $this->context->language->id, null, $this->context->shop->id),
                'livesearch_number_pages' => (int)Configuration::get('CLERK_LIVESEARCH_NUMBER_PAGES', $this->context->language->id, null, $this->context->shop->id),
                'livesearch_pages_type' => (string)Configuration::get('CLERK_LIVESEARCH_PAGES_TYPE', $this->context->language->id, null, $this->context->shop->id),
                'livesearch_dropdown_position' => (string)Configuration::get('CLERK_LIVESEARCH_DROPDOWN_POSITION', $this->context->language->id, null, $this->context->shop->id),
                'livesearch_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_LIVESEARCH_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),));

            $View .= $this->display(__FILE__, 'search-top.tpl', $key);
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
        //Determine if we should redirect to powerstep
        $controller = $this->context->controller;
        $cookie = $this->context->cookie;

        $popup = '';

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

                    $categories = $product->getCategories();
                    $category = reset($categories);

                    $this->context->smarty->assign(array(
                        'templates' => $templates,
                        'product' => $product,
                        'category' => $category,
                        'image' => $image,
                        'order_process' => Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order',
                    ));

                    //Clear cookies
                    unset($cookie->clerk_show_powerstep);
                    unset($cookie->clerk_last_product);

                    $popup .= $this->display(__FILE__, 'powerstep_modal.tpl');
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
        $category = reset($categories);

        //Assign template variables
        $this->context->smarty->assign(array(
            'clerk_public_key' => Configuration::get('CLERK_PUBLIC_KEY', $this->context->language->id, null, $this->context->shop->id),
            'clerk_datasync_use_real_time_updates' => Configuration::get('CLERK_DATASYNC_USE_REAL_TIME_UPDATES', $this->context->language->id, null, $this->context->shop->id),
            'clerk_datasync_include_out_of_stock_products' => Configuration::get('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', $this->context->language->id, null, $this->context->shop->id),
            'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $this->context->language->id, null, $this->context->shop->id),
            'exit_intent_enabled' => (bool)Configuration::get('CLERK_EXIT_INTENT_ENABLED', $this->context->language->id, null, $this->context->shop->id),
            'exit_intent_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_EXIT_INTENT_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),
            'product_enabled' => (bool)Configuration::get('CLERK_PRODUCT_ENABLED', $this->context->language->id, null, $this->context->shop->id),
            'product_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_PRODUCT_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),
            'cart_enabled' => (bool)Configuration::get('CLERK_CART_ENABLED', $this->context->language->id, null, $this->context->shop->id),
            'cart_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_CART_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),
            'powerstep_enabled' => Configuration::get('CLERK_POWERSTEP_ENABLED', $this->context->language->id, null, $this->context->shop->id),
            'powerstep_type' => Configuration::get('CLERK_POWERSTEP_TYPE', $this->context->language->id, null, $this->context->shop->id),
            'clerk_logging_level' => Configuration::get('CLERK_LOGGING_LEVEL', $this->context->language->id, null, $this->context->shop->id),
            'clerk_logging_enabled' => Configuration::get('CLERK_LOGGING_ENABLED', $this->context->language->id, null, $this->context->shop->id),
            'clerk_logging_to' => Configuration::get('CLERK_LOGGING_TO', $this->context->language->id, null, $this->context->shop->id),
            'templates' => $templates,
            'product' => $product,
            'category' => $category,
            'unix' => time(),
            'isv17' => $is_v16
        ));

        $output = $this->display(__FILE__, 'visitor_tracking.tpl');
        $output .= $popup;

        return $output;
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

            $PreProductIds = $params['cart']->getProducts(true);

            foreach ($PreProductIds as $PreProductId) {

                $ProductsIds[] = $PreProductId['id_product'];

            }
            $ProductsIds = implode(",", $ProductsIds);

            $this->context->smarty->assign(
                array(

                    'Contents' => $Contents,
                    'ProductId' => $ProductsIds

                )
            );

            return $this->display(__FILE__, 'related-products.tpl');

        }

    }

    /**
     * @param $params
     * @return string
     */
    public function hookDisplayFooterProduct($params)
    {

        if (Configuration::get('CLERK_PRODUCT_ENABLED', $this->context->language->id, null, $this->context->shop->id)) {

            $Contents = explode(',', Configuration::get('CLERK_PRODUCT_TEMPLATE', $this->context->language->id, null, $this->context->shop->id));

            $this->context->smarty->assign(
                array(

                    'Contents' => $Contents,
                    'ProductId' => $params['product']['id']

                )
            );

            return $this->display(__FILE__, 'related-products.tpl');

        }

    }

    /**
     * Hook cart save action
     */
    public function hookActionCartSave()
    {
        if (Tools::getValue('add')) {
            $this->context->cookie->clerk_show_powerstep = true;
            $this->context->cookie->clerk_last_product = Tools::getValue('id_product');
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
            $discount_per_product = $discounts / count($products);

            foreach ($products as $product) {
                $productArray[] = array(
                    'id' => $product['id_product'],
                    'quantity' => $product['product_quantity'],
                    'price' => $product['product_price_wt'] - $discount_per_product,
                );
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
        $data = (new CartPresenter)->present($cart);
        $product = null;

        foreach ($data['products'] as $p) {
            if ($p['id_product'] == $id_product && $p['id_product_attribute'] == $id_product_attribute) {
                $product = $p;
                break;
            }
        }

        $contentConfig = Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->context->language->id, null, $this->context->shop->id);
        $contents = array_filter(explode(',', $contentConfig));

        foreach ($contents as $key => $content) {

            $contents[$key] = str_replace(' ', '', $content);

        }

        $category = $product['id_category_default'];

        $this->smarty->assign(array(
            'product' => $product,
            'category' => $category,
            'cart' => $data,
            'cart_url' => $this->getCartSummaryURL(),
            'contents' => $contents
        ));

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

        $this->api->addProduct($product, $product_id);

    }

    public function hookActionUpdateQuantity($params)
    {
        if (Configuration::get('CLERK_DATASYNC_USE_REAL_TIME_UPDATES', $this->language_id, null, $this->shop_id) != '0') {

            if (Configuration::get('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', $this->language_id, null, $this->shop_id) != '1') {
                if ($params['quantity'] <= 0) {

                    $this->api->removeProduct($params['id_product']);

                } else {

                    $this->api->addProduct(0, $params['id_product'], $params['quantity']);

                }
            }
        }
    }

}
