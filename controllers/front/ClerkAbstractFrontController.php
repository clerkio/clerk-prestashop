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
     * @var array
     */
    protected $fieldHandlers = array();

    /**
     * @var array
     */
    protected $fieldMap = array();

    public function __construct()
    {
        parent::__construct();
        $this->ajax = true;
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

        if ($public_key === Configuration::get('CLERK_PUBLIC_KEY') && $private_key === Configuration::get('CLERK_PRIVATE_KEY')) {
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
        $this->fields = array_merge(['id'], $this->fields);
    }

    /**
     * Add fieldhandler
     *
     * @param $field
     *
     * @param callable $handler
     */
    protected function addFieldHandler($field, callable $handler)
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
}