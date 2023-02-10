<?php
/**
 *  @author Clerk.io
 *  @copyright Copyright (c) 2017 Clerk.io
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

class ClerkSearchModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->display_column_left = false;

        parent::initContent();

        $query = Tools::getValue('search_query', '');

        $this->context->smarty->assign(array(
            'search_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_SEARCH_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),
            'search_query' => $query,
            'lang_iso' => $this->context->language->iso_code,
            'faceted_navigation' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_FACETED_NAVIGATION_ENABLED', $this->context->language->id, null, $this->context->shop->id))),
            'facets_enabled' => Configuration::get('CLERK_FACETS_ATTRIBUTES', $this->context->language->id, null, $this->context->shop->id),
            'facets_title' => Configuration::get('CLERK_FACETS_TITLE', $this->context->language->id, null, $this->context->shop->id),
            'facets_design' => Configuration::get('CLERK_FACETS_DESIGN', $this->context->language->id, null, $this->context->shop->id),
            'search_categories' => Configuration::get('CLERK_SEARCH_CATEGORIES', $this->context->language->id, null, $this->context->shop->id),
            'search_number_categories' => Configuration::get('CLERK_SEARCH_NUMBER_CATEGORIES', $this->context->language->id, null, $this->context->shop->id),
            'search_number_pages' => Configuration::get('CLERK_SEARCH_NUMBER_PAGES', $this->context->language->id, null, $this->context->shop->id),
            'search_pages_type' => Configuration::get('CLERK_SEARCH_PAGES_TYPE', $this->context->language->id, null, $this->context->shop->id),
        ));

        if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
            $this->setTemplate('module:clerk/views/templates/front/search17.tpl');
        } else {
            $this->setTemplate('search.tpl');
        }
    }
}
