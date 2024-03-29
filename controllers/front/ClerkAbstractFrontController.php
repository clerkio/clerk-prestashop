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


abstract class ClerkAbstractFrontController extends ModuleFrontController
{
    protected $logger;
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
    protected $fieldHandlers = [];

    /**
     * @var array
     */
    protected $fieldMap = [];

    /**
     * @var class
     */
    protected $api;
    /**
     * @var mixed
     */
    protected $context;

    /**
     * ClerkAbstractFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->ajax = true;
        require_once(sprintf("%s%s/controllers/admin/ClerkLogger.php", _PS_MODULE_DIR_, $this->module->name));
        $this->logger = new ClerkLogger();
        require_once(sprintf("%s%s/controllers/admin/ClerkApi.php", _PS_MODULE_DIR_, $this->module->name));
        $this->api = new Clerk_Api();
    }

    /**
     * Display output
     */
    public function displayAjax()
    {
        try {

            header('Content-type: application/json;charset=utf-8');

            if (!$this->validateRequest()) {
                $this->jsonUnauthorized();
            }

            $this->getArguments();

            $response = $this->getJsonResponse();

            $this->ajaxDie(json_encode($response));

        } catch (Exception $e) {

            $this->logger->error('ERROR displayAjax', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Validate token from request.
	 * @param string|null $token_string Request.
     * @return boolean
     */
    private function validateJwt( $token_string = null )
    {

        if( ! $token_string || ! is_string( $token_string ) ) {
            return false;
        }

        $query_params = array(
            'token' => $token_string
        );

        $rsp_array = $this->api->verifyToken($query_params);

        if( ! $rsp_array ) {
            return false;
        }

        try {
            if( isset($rsp_array['status']) && $rsp_array['status'] == 'ok') {
                return true;
            }

            return false;

        } catch ( Exception $e ) {

            $this->logger->error( 'validateJwt ERROR', [ 'error' => $e->getMessage() ] );
            return false;
        }

    }

    /**
     * Get Token from Request Header
     * @return string
     */
    protected function getHeaderToken() {
        try {

            $token = '';
            $auth_header = null;
            $headers = $this->getRequestHeaders();
            if( array_key_exists('X-Clerk-Authorization', $headers) ) {
                $auth_header = $headers['X-Clerk-Authorization'];
            }

            if(is_string( $auth_header )) {
                $auth_header_array = explode(' ', $auth_header );
                if (count( $auth_header_array ) > 1) {
                    $token_prefix= $auth_header_array[0];
                    $token = $auth_header_array[1];

                    if ($token_prefix !='Bearer'){
                        return "";
                    }
                }
            }

            return $token;

        } catch ( Exception $e ) {

            $this->logger->error( 'ERROR getHeaderToken', array( 'error' => $e->getMessage() ) );

        }
    }

    /**
     * Get All Headers
     * @return array
     */
    function getRequestHeaders() {

        $headers = [];

        foreach($_SERVER as $key => $value) {
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }

        return $headers;
    }

    /**
     * Validate request
     *
     * @return bool
     */
    protected function validateRequest()
    {
        try {

            $public_controllers = ['unsubscribe'];

            $invoked_controller = Tools::getValue('controller');

            if(in_array($invoked_controller, $public_controllers)){
              return true;
            }

            // Exit if not POST
            if ('POST' !== $_SERVER['REQUEST_METHOD']) {
                return false;
            }

            $use_legacy_auth = (Configuration::get('CLERK_LEGACY_AUTH', $this->getLanguageId(), null, $this->getShopId()) == '1') ? true : false;

            $request_body = json_decode(file_get_contents('php://input'), true);

            // Exit if Auth cannot be parsed
            if (!is_array($request_body)) {
                return false;
            }

            $request_public_key = array_key_exists('key', $request_body) ? $request_body['key'] : '';
            $request_private_key = array_key_exists('private_key', $request_body) ? $request_body['private_key'] : '';
            $scope_public_key = Configuration::get('CLERK_PUBLIC_KEY', $this->getLanguageId(), null, $this->getShopId());
            $scope_private_key = Configuration::get('CLERK_PRIVATE_KEY', $this->getLanguageId(), null, $this->getShopId());

            $request_token = $this->getHeaderToken();

            if(!$use_legacy_auth){
              if ($this->timingSafeEquals($scope_public_key, $request_public_key) && $this->validateJwt($request_token) ) {
                return true;
              }
            } else {
              if ($this->timingSafeEquals($scope_public_key, $request_public_key) && $this->timingSafeEquals($scope_private_key, $request_private_key)){
                return true;
              }
            }


            $this->logger->warn('API key was not validated', ['response' => false]);
            return false;

        } catch ( Exception $e ) {

            $this->logger->error('ERROR validateRequest', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Compare keys from request
     *
     * @return bool
     */
    protected function timingSafeEquals($safe, $user)
    {

        $safe_value_length = strlen($safe);
        $user_value_length = strlen($user);

        if ($user_value_length !== $safe_value_length) {
            return false;
        }

        $result = 0;

        for ($i = 0; $i < $user_value_length; $i++) {
            $result |= (ord($safe[$i]) ^ ord($user[$i]));
        }

        // They are only identical strings if $result is exactly 0...
        return 0 === $result;
    }

    /**
     * Display unauthorized response
     */
    public function jsonUnauthorized()
    {

        try {

            header('HTTP/1.1 403');

            $response = array(
                'code' => 403,
                'message' => 'Invalid keys supplied',
                'description' => $this->module->l('The supplied public or private key is invalid'),
                'how_to_fix' => $this->module->l('Ensure that the proper keys are set up in the configuration'),
            );

            $this->logger->warn('Invalid API keys supplied', ['response' => $response]);
            $this->ajaxDie(json_encode($response));

            return;

        } catch (Exception $e) {

            $this->logger->error('ERROR jsonUnauthorized', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Parse request arguments
     */
    protected function getArguments()
    {
        try {

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

        } catch (Exception $e) {

            $this->logger->error('ERROR getArguments', ['error' => $e->getMessage()]);

        }
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
        try {

            $this->fieldHandlers[$field] = $handler;

        } catch (Exception $e) {

            $this->logger->error('ERROR addFieldHandler', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Get default fields
     *
     * @return array
     */
    protected function getDefaultFields()
    {
        return [];
    }

    /**
     * Dies and echoes output value
     *
     * @param string|null $value
     * @param string|null $controller
     * @param string|null $method
     * @return void
     */
    protected function ajaxDie($value = null, $controller = null, $method = null)
    {
        try {

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
            Hook::exec('actionBeforeAjaxDie' . $controller . $method, array('value' => $value));

            $this->logger->log('AJAX Killed', ['response' => '']);

            die($value);

        } catch (Exception $e) {

            $this->logger->error('ERROR ajaxDie', ['error' => $e->getMessage()]);

        }

    }

    /**
     * Get language id
     *
     * @return int
     */
    protected function getLanguageId()
    {
        if (!$this->getLanguages()) {
            return $this->context->language->id;
        } else {
            return $this->getLanguages();
        }
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

    /**
     * Get languages
     *
     * @return mixed
     */
    protected function getLanguages()
    {

        try {

            $lang_info = Language::getLanguages(true, $this->context->shop->id);
            $lang_path = filter_input(INPUT_SERVER, 'REDIRECT_URL', FILTER_SANITIZE_SPECIAL_CHARS);

            if (!is_string($lang_path)) {
                return false;
            }

            $lang_iso = false;
            if (strlen($lang_path) > 0) {
                if (strpos($lang_path, '/module') > -1) {
                    if (substr($lang_path, 0, strlen('/module')) !== '/module') {
                        $lang_iso = explode('/module', $lang_path)[1];
                        $lang_iso = str_replace("/", "", $lang_iso);
                    }
                } else {
                    if (strpos($lang_path, '/') > -1) {
                        $lang_iso = explode('/', $lang_path)[1];
                    }

                    if (strpos($lang_iso, '?') > -1) {
                        $lang_iso = explode('?', $lang_iso)[0];
                    }

                    if (strlen($lang_iso) !== 2) {
                        $lang_iso = false;
                    }
                }
            }

            if (!$lang_iso) {
                return false;
            }

            foreach ($lang_info as $lang) {
                if ($lang['iso_code'] == $lang_iso) {
                    return $lang['id_lang'];
                }
            }

            return false;

        } catch (Exception $e) {

            $this->logger->error('ERROR getLanguages', ['error' => $e->getMessage()]);

        }

    }

}
