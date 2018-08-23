<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_4_1_0($object)
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
        $languages = array();
        $allLanguages = Language::getLanguages(false, $shop_id);

        foreach ($allLanguages as $lang) {
            $languages[] = array(
                'id_lang' => $lang['id_lang'],
                'name' => $lang['name']
            );
        }

        foreach ($allLanguages as $language) {
            $powerstepTypeValues[$language['id_lang']] = 'page';
        }

        Configuration::updateValue('CLERK_POWERSTEP_TYPE', $powerstepTypeValues, false, null, $shop['id_shop']);
    }

    return true;
}

