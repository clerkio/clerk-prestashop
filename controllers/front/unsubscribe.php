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

require "ClerkAbstractFrontController.php";

class ClerkUnsubscribeModuleFrontController extends ClerkAbstractFrontController
{

    /**
     * Get response
     *
     * @return array
     */
    public function getJsonResponse()
    {
        header('User-Agent: ClerkExtensionBot Prestashop/v' . _PS_VERSION_ . ' Clerk/v' . Module::getInstanceByName('clerk')->version . ' PHP/v' . phpversion());
        header('Content-type: application/json;charset=utf-8');

        // If customer is not CLERK_DATASYNC_SYNC_SUBSCRIBERS then return error
        if (Configuration::get('CLERK_DATASYNC_SYNC_SUBSCRIBERS', $this->getLanguageId(), null, $this->getShopId()) == '0') {
            http_response_code(403);
            return array(
                'success' => false,
                'message' => 'Subscriber SYNC is disabled, enable it to handle unsubscribers with Clerk.io'
            );
        }

        $email = strtolower(trim(pSQL(Tools::getValue('email'))));
        if (empty($email)) {
            http_response_code(422);
            return array(
                'success' => false,
                'message' => 'No email provided'
            );
        }

        // unsubscribe email in Prestashop
        $set_result = $this->setActiveStatus('0', $email);

        if (!$set_result) {
            http_response_code(500);
            return array(
                'success' => false,
                'message' => 'Failed to unsubscribe from Prestashop, did not unsubscribe from Clerk.io'
            );
        }

        $clerk_response = $this->unsubscribeClerk($email);
        if ($clerk_response["status"] != "ok") {

            // resubscribe email in Prestashop
            $set_result = $this->setActiveStatus('1', $email);

            http_response_code(422);
            return array(
                'success' => false,
                'message' => 'Failed to unsubscribe from Clerk.io, reverted the unsubscribe from Prestashop',
                'clerk_response' => $clerk_response
            );
        }

        return array(
            'success' => true,
            'message' => 'Suceessfully unsubscribed from Prestashop and Clerk.io',
        );
    }

    private function unsubscribeClerk($email)
    {
        try{
            // send unsubscribe request to clerk https://api.clerk.io/v2/subscriber/unsubscribe
            $url = 'https://api.clerk.io/v2/subscriber/unsubscribe';
            $data = array(
                'email' => $email,
                'key' => Configuration::get('CLERK_PUBLIC_KEY', $this->getLanguageId(), null, $this->getShopId())
            );
    
            $url_with_params = $url . '?' . http_build_query($data);
    
            $options = array(
                'http' => array(
                    'method' => 'GET'
                )
            );
            $context = stream_context_create($options);
            $clerk_unsub_result = file_get_contents($url_with_params, false, $context);
            $clerk_response = json_decode($clerk_unsub_result, true);
            return $clerk_response;

        }catch(Exception $e){
            return array(
                'status' => 'Clerk Unsubscribe Failed',
                'message' => $e->getMessage()
            );
        }
    }

    private function setActiveStatus($status, $email)
    {
        $id_shop = (string) $this->getShopId();
        $id_lang = (string) $this->getLanguageId();
        // unsubscribe email
        if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
            $set_query = "UPDATE `" . _DB_PREFIX_ . "emailsubscription` e 
                  LEFT JOIN `" . _DB_PREFIX_ . "lang` l ON l.id_lang = e.id_lang
                  LEFT JOIN `" . _DB_PREFIX_ . "shop` s ON s.id_shop = e.id_shop
                  SET e.active = `$status`
                  WHERE LOWER(e.email) = '$email' AND e.id_shop = $id_shop AND e.id_lang = $id_lang";

            $set_result = Db::getInstance()->execute($set_query);
        } elseif (version_compare(_PS_VERSION_, '1.6.2', '>=')) {
            $set_query = "UPDATE `" . _DB_PREFIX_ . "newsletter` e
                LEFT JOIN `" . _DB_PREFIX_ . "shop` s ON s.id_shop = e.id_shop
                SET active = `$status`
                WHERE LOWER(e.email) = '$email' AND id_shop = $id_shop";

            $set_result = Db::getInstance()->execute($set_query);
        }
        return $set_result;
    }
}