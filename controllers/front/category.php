<?php
/**
 * @author Clerk.io
 * @copyright Copyright (c) 2017 Clerk.io
 *
 * @license MIT License
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

class ClerkCategoryModuleFrontController extends ClerkAbstractFrontController
{
    /**
     * @var ClerkLogger
     */
    protected $logger;

    /**
     * ClerkCategoryModuleFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        require_once (_PS_MODULE_DIR_. $this->module->name . '/controllers/admin/ClerkLogger.php');
        $this->logger = new ClerkLogger();
    }

    /**
     * Get response
     *
     * @return array
     */
    public function getJsonResponse()
    {
        try {

            header('User-Agent: ClerkExtensionBot Prestashop/v' ._PS_VERSION_. ' Clerk/v'.Module::getInstanceByName('clerk')->version. ' PHP/v'.phpversion());
            $response = array();

            $limit = '';

            if ($this->limit > 0) {
                $limit = sprintf('LIMIT %s', $this->limit);
            }

            if ($this->offset > 0) {
                $limit .= sprintf(' OFFSET %s', $this->offset);
            }

            $id_lang = $this->getLanguageId();
            $root_category = Configuration::get('PS_ROOT_CATEGORY');

            $filter = "AND c.id_category != " . $root_category;
            $categories = Category::getCategories($id_lang, true, false, $filter, '', $limit);


            if ($categories) {
                foreach ($categories as $category) {
                    if ($category['id_category'] === $root_category) {
                        continue;
                    }

                    $item = array(
                        'id' => (int)$category['id_category'],
                        'name' => $category['name'],
                        'url' => $this->context->link->getCategoryLink($category['id_category'], null, $id_lang),
                    );

                    //Append parent id
                    if ($category['id_parent'] !== $root_category) {
                        $item['parent'] = $category['id_parent'];
                    }

                    //Append subcategories
                    $categoryObj = new Category($category['id_category']);
                    $subCategories = $categoryObj->getSubCategories($id_lang);

                    $item['subcategories'] = array();
                    foreach ($subCategories as $subCategory) {
                        $item['subcategories'][] = (int)$subCategory['id_category'];
                    }

                    $response[] = $item;
                }
            }

            $this->logger->log('Fetched Categories', ['response' => $response]);

            return $response;

        } catch (Exception $e) {

            $this->logger->error('ERROR Categories getJsonResponse', ['error' => $e->getMessage()]);

        }
    }
}
