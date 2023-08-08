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

class ClerkRotateKeyModuleFrontController extends ClerkAbstractFrontController
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
    public function rotateKeyValue($settings)
    {
        $update = [];

        if( array_key_exists('clerk_private_key', $settings ) ) {
            $update['clerk_private_key'] = $settings['clerk_private_key'];
        }

        Configuration::updateValue('CLERK_PRIVATE_KEY', array($this->language_id => $value), false, null, $this->shop_id);

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

            $jsonRawPostData = file_get_contents('php://input');

            $body = [];

            $body = json_decode($jsonRawPostData, true);

            if ($body) {

                $settings = $this->rotateKeyValue($body);

                $this->logger->log('Clerk private key updated', $body);
            } else {
                $settings = ["status" => "No request body sent!"];
            }

            return $settings;

        } catch (Exception $e) {

            $this->logger->error('ERROR rotatekey getJsonResponse', ['error' => $e->getMessage()]);
        }
    }
}
