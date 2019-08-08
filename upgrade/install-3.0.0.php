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

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_0_0($object)
{
    $shops = array();
    $allShops = Shop::getShops();

    foreach ($allShops as $shop) {
        $shops[] = array(
            'id_shop' => $shop['id_shop'],
            'name' => $shop['name']
        );
    }

    foreach ($allShops as $shop) {
        $languages = array();
        $allLanguages = Language::getLanguages(false, $shop['id_shop']);

        foreach ($allLanguages as $lang) {
            $languages[] = array(
                'id_lang' => $lang['id_lang'],
                'name' => $lang['name']
            );
        }

        foreach ($languages as $language) {
            $suffix = sprintf('_%s_%s', $shop['id_shop'], $language['id_lang']);

            Configuration::updateValue('CLERK_PUBLIC_KEY' . $suffix, '');
            Configuration::updateValue('CLERK_PRIVATE_KEY' . $suffix, '');

            Configuration::updateValue('CLERK_SEARCH_ENABLED' . $suffix, 0);
            Configuration::updateValue('CLERK_SEARCH_TEMPLATE' . $suffix, 'search-page');

            Configuration::updateValue('CLERK_LIVESEARCH_ENABLED' . $suffix, 0);
            Configuration::updateValue('CLERK_LIVESEARCH_CATEGORIES' . $suffix, 0);
            Configuration::updateValue('CLERK_LIVESEARCH_TEMPLATE' . $suffix, 'live-search');

            Configuration::updateValue('CLERK_POWERSTEP_ENABLED' . $suffix, 0);
            Configuration::updateValue('CLERK_POWERSTEP_TEMPLATES' . $suffix, 'power-step-others-also-bought,power-step-visitor-complementary,power-step-popular');

            Configuration::updateValue('CLERK_DATASYNC_COLLECT_EMAILS' . $suffix, 1);
            Configuration::updateValue('CLERK_DATASYNC_FIELDS' . $suffix, '');
        }
    }

    return true;
}
