<?php

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