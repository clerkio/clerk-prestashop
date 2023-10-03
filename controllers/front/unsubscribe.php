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
                'message' => 'Subscriber SYNC is disabled, enalbe it to handle unsubscribers with Clerk.io'
            );
        }
        $email = Tools::getValue('email');
        // strip and lowercase email
        $email = strtolower(trim($email));
        if (empty($email)) {
            // return error
            return array(
                'success' => false,
                'message' => 'No email provided'
            );
        }
        
        if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {

            // query database to see if email exisista and/or is subscribed
            $dbquery = new DbQuery();
            $dbquery->select('*');
            $dbquery->from('emailsubscription','e');
            $dbquery->where('e.email = \'' . pSQL($email) . '\'');
            $dbquery->leftJoin('lang', 'l', 'l.id_lang = e.id_lang');
            $dbquery->where('e.id_shop = ' . $this->getShopId() . ' AND e.id_lang = ' . $this->getLanguageId());
            $result = Db::getInstance()->executeS($dbquery);
        }elseif (version_compare(_PS_VERSION_, '1.6.2', '>=')) {
            $dbquery = new DbQuery();
            $dbquery->select('*');
            $dbquery->from('newsletter');
            $dbquery->where('email = \'' . pSQL($email) . '\'');
            $dbquery->where('n.id_shop = ' . $this->getShopId());
            $result = Db::getInstance()->executeS($dbquery);
        }
        // return if no result
        if (empty($result)) {
            // return error
            http_response_code(404);
            return array(
                'success' => false,
                'message' => 'Email not found'
            );
        }
        // check if email is subscribed
        if ($result[0]['active'] == '0') {
            // return error
            return array(
                'success' => false,
                'message' => 'Email already unsubscribed'
            );
        }

        // unsubscribe email
        if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
            $dbquery = new DbQuery();
            $dbquery->update('emailsubscription');
            $dbquery->set('active = 0');
            $dbquery->where('email = \'' . pSQL($email) . '\'');
            $dbquery->where('id_shop = ' . $this->getShopId() . ' AND id_lang = ' . $this->getLanguageId());
            $result = Db::getInstance()->execute($dbquery);
        }elseif (version_compare(_PS_VERSION_,'1.6.2','>=')){
            $dbquery = new DbQuery();
            $dbquery->update('newsletter');
            $dbquery->set('active = 0');
            $dbquery->where('email = \'' . pSQL($email) . '\'');
            $dbquery->where('id_shop = ' . $this->getShopId());
            $result = Db::getInstance()->execute($dbquery);
        }

        // send unsubscribe request to clerk https://api.clerk.io/v2/subscriber/unsubscribe
        $url = 'https://api.clerk.io/v2/subscriber/unsubscribe';
        $data = array(
            'email' => $email,
            'key' => Configuration::get('CLERK_PUBLIC_KEY', $this->getLanguageId(), null, $this->getShopId())
        );
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'GET',
                'content' => http_build_query($data)
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        // return success
        return array(
            'success' => true,
            'message' => 'Unsubscribed'
        );



        // get body elements
        // $jsonRawPostData = file_get_contents('php://input');

        // get query elements



        $data = array();
        
        $data['email'] = $email;

        return $data;

    }


}