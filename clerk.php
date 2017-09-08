<?php
if (!defined('_PS_VERSION_')) {
	exit;
}

class Clerk extends Module
{
    /**
     * @var bool
     */
    protected $settings_updated = false;

    /**
     * @var int
     */
    protected $language_id;

    /**
     * @var int
     */
    protected $shop_id;

	/**
	 * Clerk constructor.
	 */
	public function __construct()
	{
		$this->name = 'clerk';
		$this->tab = 'advertising_marketing';
		$this->version = '3.0.0';
		$this->author = 'Clerk';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
		$this->bootstrap = true;
		$this->controllers = array('added', 'search');

		parent::__construct();

		$this->displayName = $this->l('Clerk');
		$this->description = $this->l('Clerk.io Turns More Browsers Into Buyers');

		//Set store id
        $this->shop_id = (! empty(Tools::getValue('clerk_shop_select'))) ? (int)Tools::getValue('clerk_shop_select') : $this->context->shop->id;

		//Set language id
        $this->language_id = (! empty(Tools::getValue('clerk_language_select'))) ? (int)Tools::getValue('clerk_language_select') : $this->context->language->id;
	}

	/**
	 * Register hooks & create configuration
	 *
	 * @return bool
	 */
	public function install()
	{
		if (Shop::isFeatureActive()) {
			Shop::setContext(Shop::CONTEXT_ALL);
		}

        //Initialize empty settings for all shops and languages
		foreach ($this->getAllShops() as $shop) {
		    foreach ($this->getAllLanguages($shop['id_shop']) as $language) {
                $suffix = $this->getSuffix($shop['id_shop'], $language['id_lang']);

                Configuration::updateValue('CLERK_PUBLIC_KEY' . $suffix, '');
                Configuration::updateValue('CLERK_PRIVATE_KEY' . $suffix, '');

                Configuration::updateValue('CLERK_SEARCH_ENABLED' . $suffix, 0);
                Configuration::updateValue('CLERK_SEARCH_TEMPLATE' . $suffix, 'search-page');

                Configuration::updateValue('CLERK_LIVESEARCH_ENABLED' . $suffix, 0);
                Configuration::updateValue('CLERK_LIVESEARCH_CATEGORIES' . $suffix, 0);
                Configuration::updateValue('CLERK_LIVESEARCH_TEMPLATE' . $suffix, 'live-search');

                Configuration::updateValue('CLERK_POWERSTEP_ENABLED' . $suffix, 0);
                Configuration::updateValue('CLERK_POWERSTEP_TEMPLATES' . $suffix, 'power-step-others-also-bought,power-step-visitor-complementary,power-step-popular');

                Configuration::updateValue('CLERK_DATASYNC_COLLECT_EMAILS' . $suffix, 1);
                Configuration::updateValue('CLERK_DATASYNC_FIELDS' . $suffix, '');
            }
        }

		return parent::install() &&
            $this->registerHook('top') &&
			$this->registerHook('footer') &&
			$this->registerHook('displayOrderConfirmation');
	}

	/**
	 * Delete configuration
	 *
	 * @return bool
	 */
	public function uninstall()
	{
        foreach ($this->getAllShops() as $shop) {
            foreach ($this->getAllLanguages($shop['id_shop']) as $language) {
                $suffix = $this->getSuffix($shop['id_shop'], $language['id_lang']);

                Configuration::deleteByName('CLERK_PUBLIC_KEY' . $suffix);
                Configuration::deleteByName('CLERK_PRIVATE_KEY' . $suffix);
                Configuration::deleteByName('CLERK_SEARCH_ENABLED' . $suffix);
                Configuration::deleteByName('CLERK_SEARCH_TEMPLATE' . $suffix);
                Configuration::deleteByName('CLERK_LIVESEARCH_ENABLED' . $suffix);
                Configuration::deleteByName('CLERK_LIVESEARCH_CATEGORIES' . $suffix);
                Configuration::deleteByName('CLERK_LIVESEARCH_TEMPLATE' . $suffix);
                Configuration::deleteByName('CLERK_POWERSTEP_ENABLED' . $suffix);
                Configuration::deleteByName('CLERK_POWERSTEP_TEMPLATES' . $suffix);
                Configuration::deleteByName('CLERK_DATASYNC_COLLECT_EMAILS' . $suffix);
                Configuration::deleteByName('CLERK_DATASYNC_FIELDS' . $suffix);
            }
        }

		// Delete configuration
		return parent::uninstall();
	}

	/**
	 * Save configuration and show form
	 */
	public function getContent()
	{
		$output = '';

		$this->processSubmit();

		if ($this->settings_updated) {
            $output .= $this->displayConfirmation($this->l('Settings updated.'));
        }

		return $output.$this->renderForm();
	}

    /**
     * Handle form submission
     */
	public function processSubmit()
    {
        if (Tools::isSubmit('submitClerk')) {

            //Determine if we're changing shop or language
            if (! empty(Tools::getValue('ignore_changes'))) {
                return true;
            }

            if ((Tools::getValue('clerk_language_select') !== false && (int)Tools::getValue('clerk_language_select') === $this->language_id)
                || (Tools::getValue('clerk_language_select') === false
                && (int)Configuration::get('PS_LANG_DEFAULT') === $this->language_id )) {

                $suffix = $this->getSuffix($this->shop_id, $this->language_id);

                Configuration::updateValue('CLERK_PUBLIC_KEY' . $suffix, trim(Tools::getValue('clerk_public_key' . $suffix, '')));
                Configuration::updateValue('CLERK_PRIVATE_KEY' . $suffix, trim(Tools::getValue('clerk_private_key' . $suffix, '')));
                Configuration::updateValue('CLERK_SEARCH_ENABLED' . $suffix, Tools::getValue('clerk_search_enabled' . $suffix, 0));
                Configuration::updateValue('CLERK_SEARCH_TEMPLATE' . $suffix,
                    str_replace(' ', '', Tools::getValue('clerk_search_template' . $suffix, '')));
                Configuration::updateValue('CLERK_LIVESEARCH_ENABLED' . $suffix, Tools::getValue('clerk_livesearch_enabled' . $suffix, 0));
                Configuration::updateValue('CLERK_LIVESEARCH_CATEGORIES' . $suffix,
                    Tools::getValue('clerk_livesearch_categories' . $suffix, ''));
                Configuration::updateValue('CLERK_LIVESEARCH_TEMPLATE' . $suffix,
                    str_replace(' ', '', Tools::getValue('clerk_livesearch_template' . $suffix, '')));
                Configuration::updateValue('CLERK_POWERSTEP_ENABLED' . $suffix, Tools::getValue('clerk_powerstep_enabled' . $suffix, 0));
                Configuration::updateValue('CLERK_POWERSTEP_TEMPLATES' . $suffix,
                    str_replace(' ', '', Tools::getValue('clerk_powerstep_templates' . $suffix, '')));
                Configuration::updateValue('CLERK_DATASYNC_COLLECT_EMAILS' . $suffix,
                    Tools::getValue('clerk_datasync_collect_emails' . $suffix, 1));
                Configuration::updateValue('CLERK_DATASYNC_FIELDS' . $suffix,
                    str_replace(' ', '', Tools::getValue('clerk_datasync_fields' . $suffix, '')));
            }

            $this->settings_updated = true;
        }
    }

	/**
	 * Render configuration form
	 *
	 * @return mixed
	 */
	public function renderForm()
	{
	    $booleanType = 'radio';

	    //Use switch if available, looks better
        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true) {
            $booleanType = 'switch';
        }

        $shops = $this->getAllShops();
        $languages = $this->getAllLanguages();

        $suffix = $this->getSuffix($this->shop_id, $this->language_id);

        //Language selector
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Shop & Language'),
                    'icon' => 'icon-globe'
                ),
                'input' => array(
                    array(
                        'type' => 'languageselector',
                        'label' => $this->l('Select shop & language'),
                        'name' => 'clerk_language_selector',
                        'shops' => $shops,
                        'current_shop' => $this->shop_id,
                        'languages' => $languages,
                        'current_language' => $this->language_id,
                        'logoImg' => $this->_path.'img/logo.png',
                        'moduleName' => $this->displayName,
                        'moduleVersion' => $this->version,
                    )
                )
            )
        );


		//General settings
		$this->fields_form[] = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('General'),
					'icon' => 'icon-cogs'
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Public Key'),
						'name' => 'clerk_public_key' . $suffix,
					),
					array(
						'type' => 'text',
						'label' => $this->l('Private Key'),
						'name' => 'clerk_private_key' . $suffix,
					),
					array(
						'type' => 'text',
						'label' => $this->l('Import Url'),
						'name' => 'clerk_import_url' . $suffix,
						'readonly' => true,
					),
				),
			),
		);

        //Data-sync settings
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Data-sync settings'),
                    'icon' => 'icon-cloud-upload'
                ),
                'input' => array(
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Collect Emails'),
                        'name' => 'clerk_datasync_collect_emails' . $suffix,
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_collect_emails_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_collect_emails_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Additional Fields'),
                        'name' => 'clerk_datasync_fields' . $suffix,
                    ),
                ),
            ),
        );

		//Search settings
		$this->fields_form[] = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Search Settings'),
					'icon' => 'icon-search'
				),
				'input' => array(
					array(
						'type' => $booleanType,
						'label' => $this->l('Enabled'),
						'name' => 'clerk_search_enabled' . $suffix,
                        'is_bool' => true,
                        'class' => 't',
						'values' => array(
							array(
								'id' => 'clerk_search_enabled_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'clerk_search_enabled_off',
								'value' => 0,
								'label' => $this->l('Disabled')
							)
						)
					),
					array(
						'type' => 'text',
						'label' => $this->l('Template'),
						'name' => 'clerk_search_template' . $suffix,
					),
				),
			),
		);

		//Livesearch settings
		$this->fields_form[] = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Live search Settings'),
					'icon' => 'icon-search'
				),
				'input' => array(
					array(
						'type' => $booleanType,
						'label' => $this->l('Enabled'),
						'name' => 'clerk_livesearch_enabled' . $suffix,
                        'is_bool' => true,
                        'class' => 't',
						'values' => array(
							array(
								'id' => 'clerk_livesearch_enabled_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'clerk_livesearch_enabled_off',
								'value' => 0,
								'label' => $this->l('Disabled')
							)
						)
					),
					array(
						'type' => $booleanType,
						'label' => $this->l('Include Categories'),
						'name' => 'clerk_livesearch_categories' . $suffix,
                        'is_bool' => true,
                        'class' => 't',
						'values' => array(
							array(
								'id' => 'clerk_include_categories_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'clerk_include_categories_off',
								'value' => 0,
								'label' => $this->l('Disabled')
							)
						)
					),
					array(
						'type' => 'text',
						'label' => $this->l('Template'),
						'name' => 'clerk_livesearch_template' . $suffix,
					),
				),
			),
		);

		//Powerstep settings
		$this->fields_form[] = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Powerstep Settings'),
					'icon' => 'icon-shopping-cart'
				),
				'input' => array(
					array(
						'type' => $booleanType,
						'label' => $this->l('Enabled'),
						'name' => 'clerk_powerstep_enabled' . $suffix,
                        'is_bool' => true,
                        'class' => 't',
						'values' => array(
							array(
								'id' => 'clerk_powerstep_enabled_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'clerk_powerstep_enabled_off',
								'value' => 0,
								'label' => $this->l('Disabled')
							)
						)
					),
					array(
						'type' => 'text',
						'label' => $this->l('Templates'),
						'name' => 'clerk_powerstep_templates' . $suffix,
						'desc' => $this->l('A comma separated list of clerk templates to render')
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);

		$helper = new HelperForm();

		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = $lang->id;

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitClerk';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		$helper->module = $this;
        $helper->base_tpl = 'clerkform.tpl';

        if (isset($this->context) && isset($this->context->controller)) {
            $this->context->controller->addJs($this->_path.'/js/clerk.js');
        } else {
            Tools::addJs($this->_path.'/js/clerk.js');
        }

		return $helper->generateForm($this->fields_form);
	}

	/**
	 * Get configuration field values
	 * @return array
	 */
	public function getConfigFieldsValues()
	{
	    $suffix = $this->getSuffix($this->shop_id, $this->language_id);

		return array(
			'clerk_public_key' . $suffix => Tools::getValue('clerk_public_key' . $suffix, Configuration::get('CLERK_PUBLIC_KEY' . $suffix)),
			'clerk_private_key' . $suffix => Tools::getValue('clerk_private_key' . $suffix, Configuration::get('CLERK_PRIVATE_KEY' . $suffix)),
			'clerk_import_url' . $suffix => _PS_BASE_URL_,
			'clerk_search_enabled' . $suffix => Tools::getValue('clerk_search_enabled' . $suffix, Configuration::get('CLERK_SEARCH_ENABLED' . $suffix)),
			'clerk_search_template' . $suffix => Tools::getValue('clerk_search_template' . $suffix, Configuration::get('CLERK_SEARCH_TEMPLATE' . $suffix)),
			'clerk_livesearch_enabled' . $suffix => Tools::getValue('clerk_livesearch_enabled' . $suffix, Configuration::get('CLERK_LIVESEARCH_ENABLED' . $suffix)),
			'clerk_livesearch_categories' . $suffix => Tools::getValue('clerk_livesearch_categories' . $suffix, Configuration::get('CLERK_LIVESEARCH_CATEGORIES' . $suffix)),
			'clerk_livesearch_template' . $suffix => Tools::getValue('clerk_livesearch_template' . $suffix, Configuration::get('CLERK_LIVESEARCH_TEMPLATE' . $suffix)),
			'clerk_powerstep_enabled' . $suffix => Tools::getValue('clerk_powerstep_enabled' . $suffix, Configuration::get('CLERK_POWERSTEP_ENABLED' . $suffix)),
			'clerk_powerstep_templates' . $suffix => Tools::getValue('clerk_powerstep_templates' . $suffix, Configuration::get('CLERK_POWERSTEP_TEMPLATES' . $suffix)),
            'clerk_datasync_collect_emails' . $suffix => Tools::getValue('clerk_datasync_collect_emails' . $suffix, Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS' . $suffix)),
            'clerk_datasync_fields' . $suffix => Tools::getValue('clerk_datasync_fields' . $suffix, Configuration::get('CLERK_DATASYNC_FIELDS' . $suffix)),
		);
	}

    public function hookTop($params)
    {
        $suffix = $this->getSuffix($this->context->shop->id, $this->context->language->id);

        if (Configuration::get('CLERK_SEARCH_ENABLED' . $suffix)) {
            $key = $this->getCacheId('clerksearch-top' . (( ! isset($params['hook_mobile']) || ! $params['hook_mobile']) ? '' : '-hook_mobile'));
            if (Tools::getValue('search_query') || ! $this->isCached('search-top.tpl', $key)) {
                //            $this->calculHookCommon($params);
                $this->smarty->assign(array(
                        'clerksearch_type' => 'top',
                        'search_query'     => (string)Tools::getValue('search_query'),
                        'livesearch_enabled' => (bool)Configuration::get('CLERK_LIVESEARCH_ENABLED' . $suffix),
                        'livesearch_categories' => (int)Configuration::get('CLERK_LIVESEARCH_CATEGORIES' . $suffix),
                        'livesearch_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_LIVESEARCH_TEMPLATE' . $suffix))),
                    )
                );
            }

            return $this->display(__FILE__, 'search-top.tpl', Tools::getValue('search_query') ? null : $key);
        }
    }

	/**
	 * Add visitor tracking to footer
	 *
	 * @return mixed
	 */
	public function hookFooter()
	{
        $suffix = $this->getSuffix($this->context->shop->id, $this->context->language->id);

	    //Determine if we should redirect to powerstep
        if ($this->context->controller instanceof OrderController) {
            if (Tools::getValue('ipa') && Configuration::get('CLERK_POWERSTEP_ENABLED' . $suffix)) {
                $url = $this->context->link->getModuleLink('clerk', 'added', array('id_product' => Tools::getValue('ipa')));
                Tools::redirect($url);
            }
        }

		//Assign template variables
		$this->context->smarty->assign(
			array(
				'clerk_public_key' => Configuration::get('CLERK_PUBLIC_KEY' . $suffix),
                'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS' . $suffix)
			)
		);

		return $this->display(__FILE__, 'visitor_tracking.tpl', $this->getCacheId(BlockCMSModel::FOOTER));
	}

	/**
	 * Add sales tracking to order confirmation
	 *
	 * @param $params
	 *
	 * @return mixed
	 */
	public function hookDisplayOrderConfirmation($params)
	{
        $suffix = $this->getSuffix($this->context->shop->id, $this->context->language->id);

		$order = $params['objOrder'];
		$products = $order->getProducts();

		$productArray = array();

		foreach ($products as $product) {
			$productArray[] = array(
				'id' => $product['id_product'],
				'quantity' => $product['product_quantity'],
				'price' => $product['product_price_wt'],
			);
		}

		$this->context->smarty->assign(
			array(
				'clerk_order_id' => $order->id,
				'clerk_customer_email' => $this->context->customer->email,
				'clerk_products' => json_encode($productArray),
                'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS' . $suffix),
			)
		);

		return $this->display(__FILE__, 'sales_tracking.tpl');
	}

    /**
     * Get all shops
     *
     * @return array
     */
    private function getAllShops()
    {
        $shops = array();
        $allShops = Shop::getShops();

        foreach ($allShops as $shop) {
            $shops[] = array(
                'id_shop' => $shop['id_shop'],
                'name' => $shop['name']
            );
        }

        return $shops;
    }

    /**
     * Get all languages
     *
     * @param $shop_id
     * @return array
     */
    private function getAllLanguages($shop_id)
    {
        if (is_null($shop_id)) {
            $shop_id = $this->shop_id;
        }

        $languages = array();
        $allLanguages = Language::getLanguages(false, $shop_id);

        foreach ($allLanguages as $lang) {
            $languages[] = array(
                'id_lang' => $lang['id_lang'],
                'name' => $lang['name']
            );
        }

        return $languages;
    }

    /**
     * Generate suffix
     *
     * @param $shop_id
     * @param $language_id
     * @return string
     */
    private function getSuffix($shop_id, $language_id)
    {
        return sprintf('_%s_%s', $shop_id, $language_id);
    }
}