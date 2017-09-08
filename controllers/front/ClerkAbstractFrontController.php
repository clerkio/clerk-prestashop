<?php

abstract class ClerkAbstractFrontController extends ModuleFrontController
{
    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $page;

    /**
     * @var string
     */
    protected $order;

    /**
     * @var string
     */
    protected $order_by;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var string
     */
    protected $suffix;

    /**
     * @var array
     */
    protected $fieldHandlers = array();

    /**
     * @var array
     */
    protected $fieldMap = array();

    /**
     * ClerkAbstractFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->ajax = true;
        $this->suffix = $this->getSuffix();
    }

    /**
     * Display output
     */
    public function displayAjax()
    {
        header('Content-type: application/json;charset=utf-8');

        if (! $this->validateRequest()) {
            $this->jsonUnauthorized();
        }

        $this->getArguments();

        $response = $this->getJsonResponse();

        $this->ajaxDie(Tools::jsonEncode($response));
    }

    /**
     * Validate request
     *
     * @param $request
     */
    protected function validateRequest()
    {
        $public_key  = Tools::getValue('key', '');
        $private_key = Tools::getValue('private_key', '');

        if ($public_key === Configuration::get('CLERK_PUBLIC_KEY' . $this->getSuffix()) && $private_key === Configuration::get('CLERK_PRIVATE_KEY' . $this->getSuffix())) {
            return true;
        }

        return false;
    }

    /**
     * Display unauthorized response
     */
    public function jsonUnauthorized()
    {
        header('HTTP/1.1 403');

        $response = array(
            'code' => 403,
            'message'     => 'Invalid keys supplied',
            'description' => $this->module->l('The supplied public or private key is invalid'),
            'how_to_fix'  => $this->module->l('Ensure that the proper keys are set up in the configuration'),
        );

        $this->ajaxDie(Tools::jsonEncode($response));
        return;
    }

    /**
     * Get mapped field name
     *
     * @param $field
     *
     * @return mixed
     */
    protected function getFieldName($field)
    {
        if (isset($this->fieldMap[$field])) {
            return $this->fieldMap[$field];
        }

        return $field;
    }

    /**
     * Parse request arguments
     */
    protected function getArguments()
    {
        $this->debug = (bool) Tools::getValue('debug', false);
        $this->limit = (int) Tools::getValue('limit', 0);
        $this->page = (int) Tools::getValue('page', 0);
        $this->order_by = Tools::getValue('orderby', 'id_product');
        $this->order = Tools::getValue('order', 'desc');

        $this->offset = 0;

        if ($this->page > 0) {
            $this->offset = $this->page * $this->limit;
        }

        /**
         * Explode fields on , and filter out "empty" entries
         */
        $fields = (string) Tools::getValue('fields');
        if ($fields) {
            $this->fields = array_filter(explode(',', $fields), 'strlen');
        } else {
            $this->fields = $this->getDefaultFields();
        }
        $this->fields = array_merge(array('id'), $this->fields);
    }

    /**
     * Add fieldhandler
     *
     * @param $field
     *
     * @param callable $handler
     */
    protected function addFieldHandler($field, $handler)
    {
        $this->fieldHandlers[$field] = $handler;
    }

    /**
     * Get default fields
     *
     * @return array
     */
    protected function getDefaultFields()
    {
        return array();
    }

    /**
     * Dies and echoes output value
     *
     * @param string|null $value
     * @param string|null $controller
     * @param string|null $method
     */
    protected function ajaxDie($value = null, $controller = null, $method = null)
    {
        //Call parent ajaxDie if available
        if (is_callable('parent::ajaxDie')) {
            return parent::ajaxDie($value, $controller, $method);
        }

        //Replicate functionality if not
        if ($controller === null) {
            $controller = get_class($this);
        }

        if ($method === null) {
            $bt = debug_backtrace();
            $method = $bt[1]['function'];
        }

        Hook::exec('actionBeforeAjaxDie', array('controller' => $controller, 'method' => $method, 'value' => $value));
        Hook::exec('actionBeforeAjaxDie'.$controller.$method, array('value' => $value));

        die($value);
    }

    /**
     * Get configuration suffix
     *
     * @return string
     */
    protected function getSuffix()
    {
        return sprintf('_%s_%s', $this->context->shop->id, $this->context->language->id);
    }

    /**
     * Get language id
     *
     * @return int
     */
    protected function getLanguageId()
    {
        return $this->context->language->id;
    }

    /**
     * Get shop id
     *
     * @return int
     */
    protected function getShopId()
    {
        return $this->context->shop->id;
    }
}