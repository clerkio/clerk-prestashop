<?php

/**
 *  @author Clerk.io
 *  @copyright Copyright (c) 202222 Clerk.io
 *
 *  @license MIT License
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

require "ClerkAbstractFrontController.php";

class ClerkGetConfigModuleFrontController extends ClerkAbstractFrontController
{

    protected $logger;

    public function __construct()
    {
        parent::__construct();

        require_once(_PS_MODULE_DIR_ . $this->module->name . '/controllers/admin/ClerkLogger.php');

        $context = Context::getContext();

        $this->shop_id = (Tools::getValue('clerk_shop_select')) ? (int)Tools::getValue('clerk_shop_select') : $context->shop->id;
        $this->language_id = (Tools::getValue('clerk_language_select')) ? (int)Tools::getValue('clerk_language_select') : $context->language->id;

        $this->logger = new ClerkLogger();

        //Needed for PHP 5.3 support
        $context = $this->context;
    }

    /**
     * Get configuration field values
     * @return array
     */
    public function getConfigFieldsValues()
    {
        $sync_url =  explode("module/clerk/version", (string)Context::getContext()->link->getModuleLink('clerk', 'version', [], null, $this->language_id, $this->shop_id, false))[0];

        $clerk_configuration =  array(
            // GENERAL (2)
            'clerk_language' => Configuration::get('CLERK_LANGUAGE', $this->language_id, null, $this->shop_id),
            'clerk_import_url' => $sync_url,

            // JS TRACKING HOOK POSTITION
            'clerk_tracking_hook_position' => Configuration::get('CLERK_TRACKING_HOOK_POSITION', $this->context->language->id, null, $this->shop_id),

            // DATA-SYNC SETTINGS (10)
            'clerk_datasync_use_real_time_updates' => Configuration::get('CLERK_DATASYNC_USE_REAL_TIME_UPDATES', $this->context->language->id, null, $this->shop_id),
            'clerk_datasync_include_pages' => Configuration::get('CLERK_DATASYNC_INCLUDE_PAGES', $this->context->language->id, null, $this->shop_id),
            'clerk_datasync_page_fields' => Configuration::get('CLERK_DATASYNC_PAGE_FIELDS', $this->context->language->id, null, $this->shop_id),
            'clerk_datasync_include_out_of_stock_products' => Configuration::get('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', $this->context->language->id, null, $this->shop_id),
            'clerk_datasync_include_only_local_stock' => Configuration::get('CLERK_DATASYNC_INCLUDE_ONLY_LOCAL_STOCK', $this->context->language->id, null, $this->shop_id),
            'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $this->language_id, null, $this->shop_id),
            'clerk_datasync_collect_baskets' => Configuration::get('CLERK_DATASYNC_COLLECT_BASKETS', $this->language_id, null, $this->shop_id),
            'clerk_datasync_sync_subscribers' => Configuration::get('CLERK_DATASYNC_SYNC_SUBSCRIBERS', $this->language_id, null, $this->shop_id),
            'clerk_datasync_fields' => Configuration::get('CLERK_DATASYNC_FIELDS', $this->language_id, null, $this->shop_id),
            'clerk_datasync_disable_order_synchronization' => Configuration::get('CLERK_DISABLE_ORDER_SYNC', $this->language_id, null, $this->shop_id),
            'clerk_datasync_include_variant_references' => Configuration::get('CLERK_INCLUDE_VARIANT_REFERENCES', $this->language_id, null, $this->shop_id),
            'clerk_datasync_product_features' => Configuration::get('CLERK_DATASYNC_PRODUCT_FEATURES', $this->language_id, null, $this->shop_id),
            'clerk_image_size' => Configuration::get('CLERK_IMAGE_SIZE', $this->language_id, null, $this->shop_id),
            'clerk_datasync_query_by_stock' => Configuration::get('CLERK_DATASYNC_QUERY_BY_STOCK', $this->language_id, null, $this->shop_id),
            'clerk_datasync_disable_customer_sync' => Configuration::get('CLERK_DATASYNC_DISABLE_CUSTOMER_SYNC', $this->language_id, null, $this->shop_id),

            // SEARCH SETTINGS (6)
            'clerk_search_enabled' => Configuration::get('CLERK_SEARCH_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_search_categories' => Configuration::get('CLERK_SEARCH_CATEGORIES', $this->language_id, null, $this->shop_id),
            'clerk_search_number_categories' => Configuration::get('CLERK_SEARCH_NUMBER_CATEGORIES', $this->language_id, null, $this->shop_id),
            'clerk_search_number_pages' => Configuration::get('CLERK_SEARCH_NUMBER_PAGES', $this->language_id, null, $this->shop_id),
            'clerk_search_pages_type' => Configuration::get('CLERK_SEARCH_PAGES_TYPE', $this->language_id, null, $this->shop_id),
            'clerk_search_template' => Configuration::get('CLERK_SEARCH_TEMPLATE', $this->language_id, null, $this->shop_id),

            // FACETED NAVIGATION (5)
            'clerk_faceted_navigation_enabled' => Configuration::get('CLERK_FACETED_NAVIGATION_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_facets_attributes' => Configuration::get('CLERK_FACETS_ATTRIBUTES', $this->language_id, null, $this->shop_id),
            'clerk_facets_design' => Configuration::get('CLERK_FACETS_DESIGN', $this->language_id, null, $this->shop_id),
            'clerk_facets_position' => Configuration::get('CLERK_FACETS_POSITION', $this->language_id, null, $this->shop_id),
            'clerk_facets_title' => Configuration::get('CLERK_FACETS_TITLE', $this->language_id, null, $this->shop_id),

            // LIVE SEARCH SETTINGS (10)
            'clerk_livesearch_enabled' => Configuration::get('CLERK_LIVESEARCH_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_categories' => Configuration::get('CLERK_LIVESEARCH_CATEGORIES', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_template' => Configuration::get('CLERK_LIVESEARCH_TEMPLATE', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_selector' => Configuration::get('CLERK_LIVESEARCH_SELECTOR', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_form_selector' => Configuration::get('CLERK_LIVESEARCH_FORM_SELECTOR', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_number_suggestions' => Configuration::get('CLERK_LIVESEARCH_NUMBER_SUGGESTIONS', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_number_categories' => Configuration::get('CLERK_LIVESEARCH_NUMBER_CATEGORIES', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_number_pages' => Configuration::get('CLERK_LIVESEARCH_NUMBER_PAGES', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_pages_type' => Configuration::get('CLERK_LIVESEARCH_PAGES_TYPE', $this->language_id, null, $this->shop_id),
            'clerk_livesearch_dropdown_position' => Configuration::get('CLERK_LIVESEARCH_DROPDOWN_POSITION', $this->language_id, null, $this->shop_id),

            // POWERSTEP SETTINGS (3)
            'clerk_powerstep_enabled' => Configuration::get('CLERK_POWERSTEP_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_powerstep_type' => Configuration::get('CLERK_POWERSTEP_TYPE', $this->language_id, null, $this->shop_id),
            'clerk_powerstep_templates' => Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->language_id, null, $this->shop_id),

            // EXIT INTENT SETTINGS (2)
            'clerk_exit_intent_enabled' => Configuration::get('CLERK_EXIT_INTENT_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_exit_intent_template' => Configuration::get('CLERK_EXIT_INTENT_TEMPLATE', $this->language_id, null, $this->shop_id),

            // CART SETTINGS (2)
            'clerk_category_enabled' => Configuration::get('CLERK_CATEGORY_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_category_template' => Configuration::get('CLERK_CATEGORY_TEMPLATE', $this->language_id, null, $this->shop_id),

            // CART SETTINGS (2)
            'clerk_cart_enabled' => Configuration::get('CLERK_CART_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_cart_template' => Configuration::get('CLERK_CART_TEMPLATE', $this->language_id, null, $this->shop_id),

            // PRODUCT SETTINGS (2)
            'clerk_product_enabled' => Configuration::get('CLERK_PRODUCT_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_product_template' => Configuration::get('CLERK_PRODUCT_TEMPLATE', $this->language_id, null, $this->shop_id),

            // LOGGING SETTINGS (3)
            'clerk_logging_enabled' => Configuration::get('CLERK_LOGGING_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_logging_level' => Configuration::get('CLERK_LOGGING_LEVEL', $this->language_id, null, $this->shop_id),
            'clerk_logging_to' => Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id),


            'clerk_cart_exclude_duplicates' => Configuration::get('CLERK_CART_EXCLUDE_DUPLICATES', $this->language_id, null, $this->shop_id),
            'clerk_powerstep_exclude_duplicates' => Configuration::get('CLERK_POWERSTEP_EXCLUDE_DUPLICATES', $this->language_id, null, $this->shop_id),
            'clerk_product_exclude_duplicates' => Configuration::get('CLERK_PRODUCT_EXCLUDE_DUPLICATES', $this->language_id, null, $this->shop_id),
            'clerk_category_exclude_duplicates' => Configuration::get('CLERK_CATEGORY_EXCLUDE_DUPLICATES', $this->language_id, null, $this->shop_id),

        );

        return $clerk_configuration;
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

            $options = [];

            $options = $this->getConfigFieldsValues(); // Array

            $this->logger->log('Fetched settings', ['response' => $options]);

            return $options; // settings

        } catch (Exception $e) {

            $this->logger->error('ERROR getconfig getJsonResponse', ['error' => $e->getMessage()]);
        }
    }
}
