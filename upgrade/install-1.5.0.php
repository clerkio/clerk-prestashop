<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_5_0($object)
{
    return Configuration::updateValue('CLERK_DATASYNC_FIELDS', '');
}