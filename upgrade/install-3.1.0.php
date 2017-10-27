<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_1_0($object)
{
    $shops = array();
    $allShops = Shop::getShops();

    foreach ($allShops as $shop) {
        $shops[] = array(
            'id_shop' => $shop['id_shop'],
            'name' => $shop['name']
        );
    }

    //Initialize empty settings for all shops and languages
    foreach ($shops as $shop) {
        $emptyValues = array();
        $trueValues = array();
        $falseValues = array();
        $searchTemplateValues = array();
        $liveSearchTemplateValues = array();
        $powerstepTemplateValues = array();

        $languages = array();
        $allLanguages = Language::getLanguages(false, $shop['id_shop']);

        foreach ($allLanguages as $lang) {
            $languages[] = array(
                'id_lang' => $lang['id_lang'],
                'name' => $lang['name']
            );
        }

        foreach ($languages as $language) {
            //Clean up from 3.0.0
            $suffix = sprintf('_%s_%s', $shop['id_shop'], $language['id_lang']);

            Configuration::deleteByName('CLERK_PUBLIC_KEY' . $suffix);
            Configuration::deleteByName('CLERK_PRIVATE_KEY' . $suffix);
            Configuration::deleteByName('CLERK_SEARCH_ENABLED' . $suffix);
            Configuration::deleteByName('CLERK_SEARCH_TEMPLATE' . $suffix);
            Configuration::deleteByName('CLERK_LIVESEARCH_ENABLED' . $suffix);
            Configuration::deleteByName('CLERK_LIVESEARCH_CATEGORIES' . $suffix);
            Configuration::deleteByName('CLERK_LIVESEARCH_TEMPLATE' . $suffix);
            Configuration::deleteByName('CLERK_POWERSTEP_ENABLED' . $suffix);
            Configuration::deleteByName('CLERK_POWERSTEP_TEMPLATES' . $suffix);
            Configuration::deleteByName('CLERK_DATASYNC_COLLECT_EMAILS' . $suffix);
            Configuration::deleteByName('CLERK_DATASYNC_FIELDS' . $suffix);

            $emptyValues[$language['id_lang']] = '';
            $trueValues[$language['id_lang']] = 1;
            $falseValues[$language['id_lang']] = 0;
            $searchTemplateValues[$language['id_lang']] = 'search-page';
            $liveSearchTemplateValues[$language['id_lang']] = 'live-search';
            $powerstepTemplateValues[$language['id_lang']] = 'power-step-others-also-bought,power-step-visitor-complementary,power-step-popular';
        }

        Configuration::updateValue('CLERK_PUBLIC_KEY', $emptyValues, false, null, $shop['id_shop']);
        Configuration::updateValue('CLERK_PRIVATE_KEY', $emptyValues, false, null, $shop['id_shop']);

        Configuration::updateValue('CLERK_SEARCH_ENABLED', $falseValues, false, null, $shop['id_shop']);
        Configuration::updateValue('CLERK_SEARCH_TEMPLATE', $searchTemplateValues, false, null, $shop['id_shop']);

        Configuration::updateValue('CLERK_LIVESEARCH_ENABLED', $falseValues, false, null, $shop['id_shop']);
        Configuration::updateValue('CLERK_LIVESEARCH_CATEGORIES', $falseValues, false, null, $shop['id_shop']);
        Configuration::updateValue('CLERK_LIVESEARCH_TEMPLATE', $liveSearchTemplateValues, false, null, $shop['id_shop']);

        Configuration::updateValue('CLERK_POWERSTEP_ENABLED', $falseValues, false, null, $shop['id_shop']);
        Configuration::updateValue('CLERK_POWERSTEP_TEMPLATES', $powerstepTemplateValues, false, null, $shop['id_shop']);

        Configuration::updateValue('CLERK_DATASYNC_COLLECT_EMAILS', $trueValues, false, null, $shop['id_shop']);
        Configuration::updateValue('CLERK_DATASYNC_FIELDS', $emptyValues, false, null, $shop['id_shop']);
    }

    return true;
}