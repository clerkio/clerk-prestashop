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
        $this->shop_id = (Tools::getValue('clerk_shop_select')) ? (int)Tools::getValue('clerk_shop_select') : $context->shop->id;

        //Set language id
        $this->language_id = (Tools::getValue('clerk_language_select')) ? (int)Tools::getValue('clerk_language_select') : $context->language->id;
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
        header('User-Agent: ClerkExtensionBot Prestashop/v' ._PS_VERSION_. ' Clerk/v'.Module::getInstanceByName('clerk')->version. ' PHP/v'.phpversion());
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')

            $Metadata['uri'] = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        else {

            $Metadata['uri'] = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        }

        if ($_GET) {

            $Metadata['params'] = $_GET;

        }elseif ($_POST) {

            $Metadata['params'] = $_POST;

        }

        $Type = 'log';

        if (Configuration::get('CLERK_LOGGING_ENABLED', $this->language_id, null, $this->shop_id) !== '1') {


        } else {

            if (Configuration::get('CLERK_LOGGING_LEVEL', $this->language_id, null, $this->shop_id) !== 'all') {


            } else {

                if (Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id) == 'collect') {

                    $Endpoint = 'https://api.clerk.io/v2/log/debug';

                    $data_string = json_encode([
                        'key' =>$this->Key,
                        'source' => $this->Platform,
                        'time' => $this->Time,
                        'type' => $Type,
                        'message' => $Message,
                        'metadata' => $Metadata]);

                    $curl = curl_init();

                    curl_setopt($curl, CURLOPT_URL, $Endpoint);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

                    $response = json_decode(curl_exec($curl));

                    if ($response->status == 'error') {

                        $this->LogToFile($Message,$Metadata);

                    }

                    curl_close($curl);

                } elseif (Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id) == 'file') {

                    $this->LogToFile($Message, $Metadata);

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
        header('User-Agent: ClerkExtensionBot Prestashop/v' ._PS_VERSION_. ' Clerk/v'.Module::getInstanceByName('clerk')->version. ' PHP/v'.phpversion());
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')

            $Metadata['uri'] = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        else {

            $Metadata['uri'] = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        }

        if ($_GET) {

            $Metadata['params'] = $_GET;

        }elseif ($_POST) {

            $Metadata['params'] = $_POST;

        }

        $Type = 'error';

        if (Configuration::get('CLERK_LOGGING_ENABLED', $this->language_id, null, $this->shop_id) !== '1') {


        } else {

            if (Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id) == 'collect') {

                $Endpoint = 'https://api.clerk.io/v2/log/debug';

                $data_string = json_encode([
                    'debug' => '1',
                    'key' =>$this->Key,
                    'source' => $this->Platform,
                    'time' => $this->Time,
                    'type' => $Type,
                    'message' => $Message,
                    'metadata' => $Metadata]);

                $curl = curl_init();

                curl_setopt($curl, CURLOPT_URL, $Endpoint);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

                $response = json_decode(curl_exec($curl));

                if ($response->status == 'error') {

                    $this->LogToFile($Message,$Metadata);

                }

                curl_close($curl);

            } elseif (Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id) == 'file') {

                $this->LogToFile($Message, $Metadata);

            }
        }
    }

    /**
     * @param $Message
     * @param $Metadata
     */
    public function warn($Message, $Metadata)
    {
        header('User-Agent: ClerkExtensionBot Prestashop/v' ._PS_VERSION_. ' Clerk/v'.Module::getInstanceByName('clerk')->version. ' PHP/v'.phpversion());
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')

            $Metadata['uri'] = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        else {

            $Metadata['uri'] = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        }

        if ($_GET) {

            $Metadata['params'] = $_GET;

        }elseif ($_POST) {

            $Metadata['params'] = $_POST;

        }

        $Type = 'warn';

        if (Configuration::get('CLERK_LOGGING_ENABLED', $this->language_id, null, $this->shop_id) !== '1') {


        } else {

            if (Configuration::get('CLERK_LOGGING_LEVEL', $this->language_id, null, $this->shop_id) == 'error') {


            } else {

                if (Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id) == 'collect') {

                    $Endpoint = 'https://api.clerk.io/v2/log/debug';

                    $data_string = json_encode([
                        'debug' => '1',
                        'key' =>$this->Key,
                        'source' => $this->Platform,
                        'time' => $this->Time,
                        'type' => $Type,
                        'message' => $Message,
                        'metadata' => $Metadata]);

                    $curl = curl_init();

                    curl_setopt($curl, CURLOPT_URL, $Endpoint);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

                    $response = json_decode(curl_exec($curl));

                    if ($response->status == 'error') {

                        $this->LogToFile($Message,$Metadata);

                    }

                    curl_close($curl);

                } elseif (Configuration::get('CLERK_LOGGING_TO', $this->language_id, null, $this->shop_id) == 'file') {

                    $this->LogToFile($Message, $Metadata);

                }
            }
        }
    }

    public function LogToFile($Message,$Metadata)
    {

        $log = $this->Date->format('Y-m-d H:i:s') . ' MESSAGE: ' . $Message . ' METADATA: ' . json_encode($Metadata) . PHP_EOL .
            '-------------------------' . PHP_EOL;
        $path = _PS_MODULE_DIR_ . '/clerk/clerk_log.log';

        fopen($path, "a+");
        file_put_contents($path, $log, FILE_APPEND);

    }

}