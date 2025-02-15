<?php

class Clerk_Api
{
    /**
     * @var string
     */
    protected $baseurl = 'https://api.clerk.io/v2/';
    /**
     * @var ClerkLogger
     */
    protected $logger;

    /**
     * @var int
     */
    private $language_id;

    /**
     * @var int
     */
    private $shop_id;
    /**
     * @var array[]
     */
    protected $all_contexts;

    public function __construct()
    {
        require_once(_PS_MODULE_DIR_ . '/clerk/helpers/Context.php');
        require_once(_PS_MODULE_DIR_ . '/clerk/helpers/Product.php');
        $context = Context::getContext();
        $this->shop_id = $context->shop->id;
        $this->language_id = $context->language->id;
        $this->logger = new ClerkLogger();
        $this->all_contexts = ContextHelper::getAllContexts();
    }

    /**
     * @param $product
     * @param $product_id
     * @param int $qty
     */
    public function updateProduct($product, $product_id, $force_delete = false)
    {
        try {

            $context = Context::getContext();

            foreach ($this->all_contexts as $ctx) {
                $shop_id = $ctx['shop_id'];
                $language_id = $ctx['lang_id'];
                if (!ProductHelper::shouldLiveUpdate($shop_id, $language_id)) {
                    continue;
                }
                if ($product_id) {
                    $product = new Product($product_id, true, $language_id, $shop_id);
                }

                if (!$product_id && $product) {
                    $product_id = $product['id_product'];
                }
                $product_data = ProductHelper::buildData($context, $shop_id, $language_id, $product_id, $product);

                if (!$product_data || $force_delete) {
                    $this->removeProduct($product_id, $shop_id, $language_id);
                } else {
                    $params = [
                        'key' => Configuration::get('CLERK_PUBLIC_KEY', $language_id, null, $shop_id),
                        'private_key' => Configuration::get('CLERK_PRIVATE_KEY', $language_id, null, $shop_id),
                        'products' => [$product_data],
                    ];
                    $this->post('products', $params);
                    $this->logger->log('Updated product ' . $product_data['name'], ['params' => $params['products']]);
                }

            }

        } catch (Exception $e) {
            $this->logger->error('ERROR updateProduct', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove product
     *
     * @param $product_id
     */
    public function removeProduct($product_id, $shop_id = null, $language_id = null)
    {
        if(!$shop_id){
            $shop_id = $this->shop_id;
        }
        if(!$language_id){
            $language_id = $this->language_id;
        }
        try {
            $params = [
                'key' => Configuration::get('CLERK_PUBLIC_KEY', $language_id, null, $shop_id),
                'private_key' => Configuration::get('CLERK_PRIVATE_KEY', $language_id, null, $shop_id),
                'products' => json_encode([(string) $product_id]),
            ];

            $this->delete('products', $params);
            $this->logger->log('Removed product ', ['params' => $params['products']]);
        } catch (Exception $e) {
            $this->logger->error('ERROR removeProduct', ['error' => $e->getMessage()]);
        }
    }


    /**
     * Perform a POST request
     *
     * @param string $endpoint
     * @param array $params
     */
    private function post($endpoint, $params = [])
    {
        try {
            $url = $this->baseurl . $endpoint;
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));

            $response = json_decode(curl_exec($curl));

            curl_close($curl);

            $this->logger->log('POST request', ['endpoint' => $endpoint, 'params' => $params, 'response' => $response]);
        } catch (Exception $e) {
            $this->logger->error('POST request failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param string $endpoint
     * @param array $params
     * @return object|void
     */
    public function get($endpoint, $params = [])
    {
        try {
            $url = $this->baseurl . $endpoint . '?' . http_build_query($params);
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $response = json_decode(curl_exec($curl));

            curl_close($curl);

            $this->logger->log('GET request', ['endpoint' => $endpoint, 'params' => $params, 'response' => $response]);

            return $response;
        } catch (Exception $e) {
            $this->logger->error('GET request failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Perform a DELETE request
     *
     * @param string $endpoint
     * @param array $params
     */
    private function delete($endpoint, $params = [])
    {
        try {
            $url = $this->baseurl . $endpoint . '?' . http_build_query($params);
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($curl);

            curl_close($curl);

            $this->logger->log('DELETE request', ['endpoint' => $endpoint, 'params' => $params, 'response' => $response]);
            return $response;

        } catch (Exception $e) {
            $this->logger->error('DELETE request failed', ['error' => $e->getMessage()]);
        }
    }


    /**
     * Perform a PATCH request
     *
     * @param string $endpoint
     * @param array $params
     * @return object|void
     */
    private function patch($endpoint, $params = [])
    {
        try {
            $url = $this->baseurl . $endpoint;
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));

            $response = json_decode(curl_exec($curl));

            curl_close($curl);

            $this->logger->log('PATCH request', ['endpoint' => $endpoint, 'params' => $params, 'response' => $response]);

            return $response;

        } catch (Exception $e) {
            $this->logger->error('PATCH request failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Post Received Token for Verification
     *
     * @param array|void $data
     * @return array
     */
    public function verifyToken($data = null)
    {

        if (!$data) {
            return [];
        }

        try {

            $endpoint = 'token/verify';

            $data['key'] = Configuration::get('CLERK_PUBLIC_KEY', $this->language_id, null, $this->shop_id);

            $response = $this->get($endpoint, $data);

            if (!$response) {
                return [];
            } else {
                return (array)$response;
            }

        } catch (Exception $e) {
            $this->logger->error('ERROR verify_token', array('error' => $e->getMessage()));
            return [];
        }

    }
}
