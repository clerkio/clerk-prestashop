<?php
/**
 * @author Clerk.io
 * @copyright Copyright (c) 2017 Clerk.io
 *
 * @license MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
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

class ClerkLogger extends ModuleAdminController
{
    private $Platform;
    private $Key;
    private $Date;
    private $Time;
    /**
     * @var int
     */
    private $language_id;

    /**
     * @var int
     */
    private $shop_id;

    /**
     * ClerkLogger constructor.
     * @throws Exception
     */
    function __construct()
    {

        $context = Context::getContext();

        //Set shop id
        $this->shop_id = (!empty(Tools::getValue('clerk_shop_select'))) ? (int)Tools::getValue('clerk_shop_select') : $context->shop->id;

        //Set language id
        $this->language_id = (!empty(Tools::getValue('clerk_language_select'))) ? (int)Tools::getValue('clerk_language_select') : $context->language->id;
        $this->Platform = 'Prestashop';
        $this->Key = Configuration::get('CLERK_PUBLIC_KEY', $this->language_id, null, $this->shop_id);
        $this->Date = new DateTime();
        $this->Time = $this->Date->getTimestamp();

    }

    /**
     * @param $Message
     * @param $Metadata
     */
    public function log($Message, $Metadata)
    {

        //Customize $Platform and the function for getting the public key.
        $JSON_Metadata_Encode = json_encode($Metadata);
        $Type = 'log';

        if (Configuration::get('CLERK_LOGGING_ENABLED', $this->language_id, null, $this->shop_id) !== '1') {


        } else {

            if (Configuration::get('CLERK_LOGGING_LEVEL', $this->language_id, null, $this->shop_id) !== 'all') {


            } else {

                if (Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id) == 'collect') {

                    if (Configuration::get('CLERK_LOGGING_LEVEL', $this->language_id, null, $this->shop_id) == 'all') {

                        $Endpoint = 'api.clerk.io/v2/log/debug?&key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                    } else {

                        $Endpoint = 'api.clerk.io/v2/log/debug?key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                    }

                    $curl = curl_init();

                    curl_setopt($curl, CURLOPT_URL, $Endpoint);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($curl);
                    curl_close($curl);

                } elseif (Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id) == 'file') {

                    $log = $this->Date->format('Y-m-d H:i:s') . ' MESSAGE: ' . $Message . ' METADATA: ' . $JSON_Metadata_Encode . PHP_EOL .
                        '-------------------------' . PHP_EOL;
                    $path = _PS_MODULE_DIR_ . '/clerk/clerk_log.log';

                    fopen($path, "a+");
                    file_put_contents($path, $log, FILE_APPEND);

                }
            }
        }
    }

    /**
     * @param $Message
     * @param $Metadata
     */
    public function error($Message, $Metadata)
    {

        //Customize $Platform and the function for getting the public key.
        $JSON_Metadata_Encode = json_encode($Metadata);
        $Type = 'error';

        if (Configuration::get('CLERK_LOGGING_ENABLED', $this->language_id, null, $this->shop_id) !== '1') {


        } else {

            if (Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id) == 'collect') {

                if (Configuration::get('CLERK_LOGGING_LEVEL', $this->language_id, null, $this->shop_id) == 'all') {

                    $Endpoint = 'api.clerk.io/v2/log/debug?debug=1&key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                } else {

                    $Endpoint = 'api.clerk.io/v2/log/debug?key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                }

                $curl = curl_init();

                curl_setopt($curl, CURLOPT_URL, $Endpoint);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_exec($curl);
                curl_close($curl);

            } elseif (Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id) == 'file') {

                $log = $this->Date->format('Y-m-d H:i:s') . ' MESSAGE: ' . $Message . ' METADATA: ' . $JSON_Metadata_Encode . PHP_EOL .
                    '-------------------------' . PHP_EOL;
                $path = _PS_MODULE_DIR_ . '/clerk/clerk_log.log';

                fopen($path, "a+");
                file_put_contents($path, $log, FILE_APPEND);

            }
        }
    }

    /**
     * @param $Message
     * @param $Metadata
     */
    public function warn($Message, $Metadata)
    {

        //Customize $Platform and the function for getting the public key.
        $JSON_Metadata_Encode = json_encode($Metadata);
        $Type = 'warn';

        if (Configuration::get('CLERK_LOGGING_ENABLED', $this->language_id, null, $this->shop_id) !== '1') {


        } else {

            if (Configuration::get('CLERK_LOGGING_LEVEL', $this->language_id, null, $this->shop_id) == 'error') {


            } else {

                if (Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id) == 'collect') {

                    if (Configuration::get('CLERK_LOGGING_LEVEL', $this->language_id, null, $this->shop_id) == 'all') {

                        $Endpoint = 'api.clerk.io/v2/log/debug?debug=1&key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                    } else {

                        $Endpoint = 'api.clerk.io/v2/log/debug?key=' . $this->Key . '&source=' . $this->Platform . '&time=' . $this->Time . '&type=' . $Type . '&message=' . $Message . '&metadata=' . urlencode($JSON_Metadata_Encode);

                    }

                    $curl = curl_init();

                    curl_setopt($curl, CURLOPT_URL, $Endpoint);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($curl);
                    curl_close($curl);

                } elseif (Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id) == 'file') {

                    $log = $this->Date->format('Y-m-d H:i:s') . ' MESSAGE: ' . $Message . ' METADATA: ' . $JSON_Metadata_Encode . PHP_EOL .
                        '-------------------------' . PHP_EOL;
                    $path = _PS_MODULE_DIR_ . '/clerk/clerk_log.log';

                    fopen($path, "a+");
                    file_put_contents($path, $log, FILE_APPEND);

                }
            }
        }
    }
}