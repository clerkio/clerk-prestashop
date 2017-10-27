<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_2_0($object)
{
    $clerk = Module::getInstanceByName('clerk');

    $tab = new Tab();
    $tab->active = 1;
    $tab->name = array();
    $tab->class_name = 'AdminClerkDashboard';

    foreach (Language::getLanguages(true) as $lang) {
        $tab->name[$lang['id_lang']] = 'Clerk';
    }

    $tab->id_parent = 0;
    $tab->module = $clerk->name;

    return $tab->add();
}