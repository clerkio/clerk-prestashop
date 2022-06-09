<?php
/**
 *  @author Clerk.io
 *  @copyright Copyright (c) 2017 Clerk.io
 *
 *  @license MIT License
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

require "ClerkAbstractFrontController.php";

class ClerkPluginModuleFrontController extends ClerkAbstractFrontController
{

    /**
     * @var
     */
    protected $logger;

    /**
     * ClerkProductModuleFrontController constructor.
     */
    public function __construct()
    {

        parent::__construct();

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

            $modules = scandir(_PS_MODULE_DIR_);

            $modules_array = [];

            foreach ($modules as $module) {

                $exclude = ['.', '..', '.htaccess', 'index.php'];

                if (!in_array($module, $exclude)) {

                    $module_info = Module::getInstanceByName($module);

                    $fields_to_keep = [
                        'id',
                        'version',
                        'ps_versions_compliancy',
                        'name',
                        'displayName',
                        'description',
                        'author',
                        'active',
                        'trusted',
                        'enable_device'
                    ];

                    $draft_module = $this->BuildModuleInfo($module_info, $fields_to_keep);

                    $modules_array[] = $draft_module;

                }

            }

            return $modules_array;

        } catch (Exception $e) {

            $this->logger->error('ERROR Plugin getJsonResponse', ['error' => $e->getMessage()]);

        }

    }

    /**
     * Get default fields for products
     *
     * @return array
     */
    public function BuildModuleInfo($module, $fields) {

        $module_info = [];

        foreach ($fields as $field) {

            if (isset($module->{$field})) {

                $module_info[$field] = $module->{$field};

            }

        }

        return $module_info;

    }
}
