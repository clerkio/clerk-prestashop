<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_1_0($object)
{
    return Configuration::updateValue('CLERK_DATASYNC_COLLECT_EMAILS', 1);
}