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

class ClerkSetConfigModuleFrontController extends ClerkAbstractFrontController
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
     * Set configuration field values
     */
    public function setConfigFieldsValues($settings)
    {
        $update = [];

        foreach ($settings as $key => $value) {

            $update[$key] = $value;

            // GENERAL (1)
            if ($key == "clerk_language") {
                Configuration::updateValue('CLERK_LANGUAGE', array($this->language_id => $value), false, null, $this->shop_id);
            }

            // JS TRACKING HOOK POSITIONS
            if ($key == "clerk_tracking_hook_position") {
                Configuration::updateValue('CLERK_TRACKING_HOOK_POSITION', array($this->language_id => $value), false, null, $this->shop_id);
            }

            // DATA-SYNC SETTINGS (10)
            if ($key == "clerk_datasync_use_real_time_updates") {
                Configuration::updateValue('CLERK_DATASYNC_USE_REAL_TIME_UPDATES', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_datasync_include_pages") {
                Configuration::updateValue('CLERK_DATASYNC_INCLUDE_PAGES', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_datasync_page_fields") {
                Configuration::updateValue('CLERK_DATASYNC_PAGE_FIELDS', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_datasync_include_out_of_stock_products") {
                Configuration::updateValue('CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_datasync_include_only_local_stock") {
                Configuration::updateValue('CLERK_DATASYNC_INCLUDE_ONLY_LOCAL_STOCK', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_datasync_collect_emails") {
                Configuration::updateValue('CLERK_DATASYNC_COLLECT_EMAILS', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_datasync_collect_baskets") {
                Configuration::updateValue('CLERK_DATASYNC_COLLECT_BASKETS', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_datasync_sync_subscribers") {
                Configuration::updateValue('CLERK_DATASYNC_SYNC_SUBSCRIBERS', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_datasync_disable_customer_sync") {
                Configuration::updateValue('CLERK_DATASYNC_DISABLE_CUSTOMER_SYNC', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_datasync_fields") {
                Configuration::updateValue('CLERK_DATASYNC_FIELDS', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_datasync_disable_order_synchronization") {
                Configuration::updateValue('CLERK_DISABLE_ORDER_SYNC', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_datasync_include_variant_references") {
                Configuration::updateValue('CLERK_INCLUDE_VARIANT_REFERENCES', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_datasync_product_features") {
                Configuration::updateValue('CLERK_DATASYNC_PRODUCT_FEATURES', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_image_size") {
                Configuration::updateValue('CLERK_IMAGE_SIZE', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_datasync_query_by_stock") {
                Configuration::updateValue('CLERK_DATASYNC_QUERY_BY_STOCK', array($this->language_id => $value), false, null, $this->shop_id);
            }

            // SEARCH SETTINGS (6)
            if ($key == "clerk_search_enabled") {
                Configuration::updateValue('CLERK_SEARCH_ENABLED', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_search_categories") {
                Configuration::updateValue('CLERK_SEARCH_CATEGORIES', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_search_number_categories") {
                Configuration::updateValue('CLERK_SEARCH_NUMBER_CATEGORIES', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_search_number_pages") {
                Configuration::updateValue('CLERK_SEARCH_NUMBER_PAGES', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_search_pages_type") {
                Configuration::updateValue('CLERK_SEARCH_PAGES_TYPE', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_search_template") {
                Configuration::updateValue('CLERK_SEARCH_TEMPLATE', array($this->language_id => $value), false, null, $this->shop_id);
            }

            // FACETED NAVIGATION (5)
            if ($key == "clerk_faceted_navigation_enabled") {
                Configuration::updateValue('CLERK_FACETED_NAVIGATION_ENABLED', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_facets_attributes") {
                Configuration::updateValue('CLERK_FACETS_ATTRIBUTES', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_facets_design") {
                Configuration::updateValue('CLERK_FACETS_DESIGN', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_facets_position") {
                Configuration::updateValue('CLERK_FACETS_POSITION', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_facets_title") {
                Configuration::updateValue('CLERK_FACETS_TITLE', array($this->language_id => $value), false, null, $this->shop_id);
            }

            // LIVE SEARCH SETTINGS (10)
            if ($key == "clerk_livesearch_enabled") {
                Configuration::updateValue('CLERK_LIVESEARCH_ENABLED', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_livesearch_categories") {
                Configuration::updateValue('CLERK_LIVESEARCH_CATEGORIES', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_livesearch_template") {
                Configuration::updateValue('CLERK_LIVESEARCH_TEMPLATE', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_livesearch_selector") {
                Configuration::updateValue('CLERK_LIVESEARCH_SELECTOR', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_livesearch_form_selector") {
                Configuration::updateValue('CLERK_LIVESEARCH_FORM_SELECTOR', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_livesearch_number_suggestions") {
                Configuration::updateValue('CLERK_LIVESEARCH_NUMBER_SUGGESTIONS', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_livesearch_number_categories") {
                Configuration::updateValue('CLERK_LIVESEARCH_NUMBER_CATEGORIES', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_livesearch_number_pages") {
                Configuration::updateValue('CLERK_LIVESEARCH_NUMBER_PAGES', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_livesearch_pages_type") {
                Configuration::updateValue('CLERK_LIVESEARCH_PAGES_TYPE', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_livesearch_dropdown_position") {
                Configuration::updateValue('CLERK_LIVESEARCH_DROPDOWN_POSITION', array($this->language_id => $value), false, null, $this->shop_id);
            }

            // POWERSTEP SETTINGS (3)
            if ($key == "clerk_powerstep_enabled") {
                Configuration::updateValue('CLERK_POWERSTEP_ENABLED', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_powerstep_type") {
                Configuration::updateValue('CLERK_POWERSTEP_TYPE', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_powerstep_templates") {
                Configuration::updateValue('CLERK_POWERSTEP_TEMPLATES', array($this->language_id => $value), false, null, $this->shop_id);
            }

            // EXIT INTENT SETTINGS (2)
            if ($key == "clerk_exit_intent_enabled") {
                Configuration::updateValue('CLERK_EXIT_INTENT_ENABLED', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_exit_intent_template") {
                Configuration::updateValue('CLERK_EXIT_INTENT_TEMPLATE', array($this->language_id => $value), false, null, $this->shop_id);
            }

            // CART SETTINGS (2)
            if ($key == "clerk_cart_enabled") {
                Configuration::updateValue('CLERK_CART_ENABLED', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_cart_template") {
                Configuration::updateValue('CLERK_CART_TEMPLATE', array($this->language_id => $value), false, null, $this->shop_id);
            }

            // PRODUCT SETTINGS (2)
            if ($key == "clerk_product_enabled") {
                Configuration::updateValue('CLERK_PRODUCT_ENABLED', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_product_template") {
                Configuration::updateValue('CLERK_PRODUCT_TEMPLATE', array($this->language_id => $value), false, null, $this->shop_id);
            }

            // PRODUCT SETTINGS (2)
            if ($key == "clerk_category_enabled") {
                Configuration::updateValue('CLERK_CATEGORY_ENABLED', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_category_template") {
                Configuration::updateValue('CLERK_CATEGORY_TEMPLATE', array($this->language_id => $value), false, null, $this->shop_id);
            }

            // LOGGING SETTINGS (3)
            if ($key == "clerk_logging_enabled") {
                Configuration::updateValue('CLERK_LOGGING_ENABLED', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_logging_level") {
                Configuration::updateValue('CLERK_LOGGING_LEVEL', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_logging_to") {
                Configuration::updateValue('CLERK_LOGGING_TO', array($this->language_id => $value), false, null, $this->shop_id);
            }

            if ($key == "clerk_cart_exclude_duplicates") {
                Configuration::updateValue('CLERK_CART_EXCLUDE_DUPLICATES', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_powerstep_exclude_duplicates") {
                Configuration::updateValue('CLERK_POWERSTEP_EXCLUDE_DUPLICATES', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_product_exclude_duplicates") {
                Configuration::updateValue('CLERK_PRODUCT_EXCLUDE_DUPLICATES', array($this->language_id => $value), false, null, $this->shop_id);
            }
            if ($key == "clerk_category_exclude_duplicates") {
                Configuration::updateValue('CLERK_CATEGORY_EXCLUDE_DUPLICATES', array($this->language_id => $value), false, null, $this->shop_id);
            }
        }

        // return array of all updates (succesfull or not )
        return $update;
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
            header('Content-type: application/json;charset=utf-8');

            // grab ALL the data - only thing that works here for some reason
            $jsonRawPostData = file_get_contents('php://input');

            $body = [];

            $body = json_decode($jsonRawPostData, true); // Array

            if ($body) {

                $settings = $this->setConfigFieldsValues($body);

                $this->logger->log('Clerk settings updated', $body);
            } else {
                $settings = ["status" => "No request body sent!"];
            }

            return $settings;
        } catch (Exception $e) {

            $this->logger->error('ERROR setconfig getJsonResponse', ['error' => $e->getMessage()]);
        }
    }
}
