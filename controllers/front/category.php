<?php
require "ClerkAbstractFrontController.php";

class ClerkCategoryModuleFrontController extends ClerkAbstractFrontController
{
    /**
     * Get response
     *
     * @return array
     */
    public function getJsonResponse()
    {
        $response = array();

        $limit = '';

        if ($this->limit > 0) {
            $limit = sprintf('LIMIT %s', $this->limit);
        }

        if ($this->offset > 0) {
            $limit .= sprintf(' OFFSET %s', $this->offset);
        }

        $id_lang = Configuration::get('PS_LANG_DEFAULT');
        $root_category = Configuration::get('PS_ROOT_CATEGORY');

        $filter = "AND c.id_category != " . $root_category;
        $categories = Category::getCategories($id_lang, true, false, $filter, '', $limit);


        if ($categories) {
            foreach ($categories as $category) {
                if ($category['id_category'] === $root_category) {
                    continue;
                }

                $item = [
                    'id' => $category['id_category'],
                    'name' => $category['name'],
                    'url' => $this->context->link->getCategoryLink($category['id_category'], null, $id_lang),
                ];

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

        return $response;
    }
}