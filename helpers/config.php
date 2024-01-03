<?php

use ConfigurationCore as Configuration;
class ClerkConfigHelper {
    public function default(string $key, array $value, int $shop_id = null): void
    {
        Configuration::updateValue($key, $value, false, null, $shop_id);
    }
}