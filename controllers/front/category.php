<?php
require "ClerkAbstractFrontController.php";

class ClerkCategoryModuleFrontController extends ClerkAbstractFrontController
{
    /**
     * Mapped fields
     *
     * @var array
     */
    protected $fieldMap = array(
        'id_category' => 'id',
    );

    /**
     * Localized attributes
     *
     * @var array
     */
    protected $localizedAttributes = array(
        'name',
        'url',
    );

    public function __construct()
    {
        parent::__construct();

        //Needed for PHP 5.3 support
        $context = $this->context;

        $this->addFieldHandler('url', function($category, $id_lang) use($context) {
            return $context->link->getCategoryLink($category['id_category'], null, $id_lang);
        });

        $this->addFieldHandler('subcategories', function($category, $id_lang) use($context) {
            //Get all subcategories and append
            $categoryObj = new Category($category['id_category']);
            $subCategories = $categoryObj->getSubCategories($id_lang);

            $items = array();

            foreach ($subCategories as $subCategory) {
                $items[] = (int)$subCategory['id_category'];
            }

            return $items;
        });

        $this->addFieldHandler('parent', function($category, $id_lang) {
            if ($category['id_parent'] !== Configuration::get('PS_ROOT_CATEGORY')) {
                return $category['id_parent'];
            }
        });
    }


    /**
     * Get response
     *
     * @return array
     */
    public function getJsonResponse()
    {
        $root_category = Configuration::get('PS_ROOT_CATEGORY');

        $categories = $this->getAllCategories($root_category);

        $response = $this->filterCategories($categories);

        return $response;
    }

    /**
     * Filters categories and build localized array structure
     *
     * @todo refactor this
     * @param $categories
     */
    protected function filterCategories($categories)
    {
        $response = array();

        foreach ($categories as $isoCode => $languageCategories) {
            $allIds = array_column($languageCategories, 'id');

            foreach ($allIds as $key_id => $id_category) {
                if (!isset($response[$id_category])) {
                    $response[$id_category] = array();
                }

                foreach ($languageCategories[$key_id] as $attribute => $value) {

                    if (in_array($attribute, $this->localizedAttributes)) {
                        if (! isset($response[$id_category][$attribute])) {
                            $response[$id_category][$attribute] = [];
                        }

                        $response[$id_category][$attribute][$isoCode] = $value;
                    } else {
                        $response[$id_category][$attribute] = $value;
                    }
                }
            }
        }

        return $response;
    }

    protected function getAllCategories($root_category)
    {
        $limit = '';

        if ($this->limit > 0) {
            $limit = sprintf('LIMIT %s', $this->limit);
        }

        if ($this->offset > 0) {
            $limit .= sprintf(' OFFSET %s', $this->offset);
        }

        $filter = "AND c.id_category != " . $root_category;

        //Get all categories for all lanuages
        $languages = Language::getLanguages(true, $this->context->shop->id);

        $items = array();

        foreach ($languages as $language) {
            $categories = Category::getCategories($language['id_lang'], true, false, $filter, '', $limit);

            $items[$language['iso_code']] = array();

            //Get data for each category
            foreach ($categories as $category) {
                $item = $this->getCategoryData($category, $language['id_lang']);

                $items[$language['iso_code']][] = $item;
            }

        }

        return $items;
    }

    /**
     * Get default fields for categories
     *
     * @return array
     */
    protected function getDefaultFields()
    {
        return array('id', 'name', 'url', 'parent', 'subcategories');
    }


    protected function getCategoryData($category, $id_lang)
    {
        $item = array();
        $fields = array_flip($this->fieldMap);

        foreach ($this->fields as $field) {
            if (array_key_exists($field, array_flip($this->fieldMap))) {
                $item[$field] = $category[$fields[$field]];
            } elseif (isset($category[$field])) {
                $item[$field] = $category[$field];
            }

            //Check if there's a fieldHandler assigned for this field
            if (isset($this->fieldHandlers[$field])) {
                $value = $this->fieldHandlers[$field]($category, $id_lang);

                if ($value) {
                    $item[$field] = $value;
                }
            }
        }

        return $item;
    }
}