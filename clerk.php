<?php
if (!defined('_PS_VERSION_')) {
	exit;
}


class Clerk extends Module
{
    const TYPE_PAGE = 'page';
    const TYPE_POPUP = 'popup';

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
		$this->version = '4.1.2';
		$this->author = 'Clerk';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
		$this->bootstrap = true;
		$this->controllers = array('added', 'search');

		parent::__construct();

		$this->displayName = $this->l('Clerk');
		$this->description = $this->l('Clerk.io Turns More Browsers Into Buyers');

		//Set shop id
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

		//Install tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->name = array();
        $tab->class_name = 'AdminClerkDashboard';

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Clerk';
        }

        $tab->id_parent = 0;
        $tab->module = $this->name;

        //Initialize empty settings for all shops and languages
		foreach ($this->getAllShops() as $shop) {
		    $emptyValues = array();
		    $trueValues = array();
		    $falseValues = array();
		    $searchTemplateValues = array();
            $liveSearchTemplateValues = array();
            $powerstepTemplateValues = array();

		    foreach ($this->getAllLanguages($shop['id_shop']) as $language) {
                $emptyValues[$language['id_lang']] = '';
                $trueValues[$language['id_lang']] = 1;
                $falseValues[$language['id_lang']] = 0;
                $searchTemplateValues[$language['id_lang']] = 'search-page';
                $liveSearchTemplateValues[$language['id_lang']] = 'live-search';
                $powerstepTemplateValues[$language['id_lang']] = 'power-step-others-also-bought,power-step-visitor-complementary,power-step-popular';
                $powerstepTypeValues[$language['id_lang']] = self::TYPE_PAGE;
                $exitIntentTemplateValues[$language['id_lang']] = 'exit-intent';
            }

            Configuration::updateValue('CLERK_PUBLIC_KEY', $emptyValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_PRIVATE_KEY', $emptyValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_SEARCH_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_SEARCH_TEMPLATE', $searchTemplateValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_LIVESEARCH_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_CATEGORIES', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_LIVESEARCH_TEMPLATE', $liveSearchTemplateValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_POWERSTEP_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_POWERSTEP_TYPE', $powerstepTypeValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_POWERSTEP_TEMPLATES', $powerstepTemplateValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_DATASYNC_COLLECT_EMAILS', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DATASYNC_FIELDS', $emptyValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_DISABLE_ORDER_SYNC', $falseValues, false, null, $shop['id_shop']);

            Configuration::updateValue('CLERK_EXIT_INTENT_ENABLED', $falseValues, false, null, $shop['id_shop']);
            Configuration::updateValue('CLERK_EXIT_INTENT_TEMPLATE', $exitIntentTemplateValues, false, null, $shop['id_shop']);
        }

		return parent::install() &&
            $tab->add() &&
            $this->registerHook('top') &&
			$this->registerHook('footer') &&
            $this->registerHook('actionCartSave') &&
			$this->registerHook('displayOrderConfirmation') &&
            $this->registerHook('actionAdminControllerSetMedia');
	}

	/**
	 * Delete configuration
	 *
	 * @return bool
	 */
	public function uninstall()
	{
        $id_tab = (int) Tab::getIdFromClassName('AdminClerkDashboard');

        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }

        Configuration::deleteByName('CLERK_PUBLIC_KEY');
        Configuration::deleteByName('CLERK_PRIVATE_KEY');
        Configuration::deleteByName('CLERK_SEARCH_ENABLED');
        Configuration::deleteByName('CLERK_SEARCH_TEMPLATE');
        Configuration::deleteByName('CLERK_LIVESEARCH_ENABLED');
        Configuration::deleteByName('CLERK_LIVESEARCH_CATEGORIES');
        Configuration::deleteByName('CLERK_LIVESEARCH_TEMPLATE');
        Configuration::deleteByName('CLERK_POWERSTEP_ENABLED');
        Configuration::deleteByName('CLERK_POWERSTEP_TYPE');
        Configuration::deleteByName('CLERK_POWERSTEP_TEMPLATES');
        Configuration::deleteByName('CLERK_DATASYNC_COLLECT_EMAILS');
        Configuration::deleteByName('CLERK_DISABLE_ORDER_SYNC');
        Configuration::deleteByName('CLERK_DATASYNC_FIELDS');
        Configuration::deleteByName('CLERK_EXIT_INTENT_ENABLED');
        Configuration::deleteByName('CLERK_EXIT_INTENT_TEMPLATE');

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

                Configuration::updateValue('CLERK_PUBLIC_KEY', array(
                    $this->language_id => trim(Tools::getValue('clerk_public_key', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_PRIVATE_KEY', array(
                    $this->language_id => trim(Tools::getValue('clerk_private_key', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_SEARCH_ENABLED', array(
                    $this->language_id => Tools::getValue('clerk_search_enabled', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_SEARCH_TEMPLATE', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_search_template', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LIVESEARCH_ENABLED', array(
                    $this->language_id => Tools::getValue('clerk_livesearch_enabled', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LIVESEARCH_CATEGORIES', array(
                    $this->language_id => Tools::getValue('clerk_livesearch_categories', '')
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_LIVESEARCH_TEMPLATE', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_livesearch_template', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_POWERSTEP_ENABLED', array(
                    $this->language_id => Tools::getValue('clerk_powerstep_enabled', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_POWERSTEP_TYPE', array(
                    $this->language_id => Tools::getValue('clerk_powerstep_type', self::TYPE_PAGE)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_POWERSTEP_TEMPLATES', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_powerstep_templates', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_COLLECT_EMAILS', array(
                    $this->language_id => Tools::getValue('clerk_datasync_collect_emails', 1)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DISABLE_ORDER_SYNC', array(
                    $this->language_id => Tools::getValue('clerk_datasync_disable_order_synchronization', 1)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_DATASYNC_FIELDS', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_datasync_fields', ''))
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_EXIT_INTENT_ENABLED', array(
                    $this->language_id => Tools::getValue('clerk_exit_intent_enabled', 0)
                ), false, null, $this->shop_id);

                Configuration::updateValue('CLERK_EXIT_INTENT_TEMPLATE', array(
                    $this->language_id => str_replace(' ', '', Tools::getValue('clerk_exit_intent_template', ''))
                ), false, null, $this->shop_id);
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
						'name' => 'clerk_public_key',
					),
					array(
						'type' => 'text',
						'label' => $this->l('Private Key'),
						'name' => 'clerk_private_key',
					),
					array(
						'type' => 'text',
						'label' => $this->l('Import Url'),
						'name' => 'clerk_import_url',
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
                        'name' => 'clerk_datasync_collect_emails',
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
                        'name' => 'clerk_datasync_fields',
                    ),
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Disable Order Synchronization'),
                        'name' => 'clerk_datasync_disable_order_synchronization',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_datasync_disable_order_synchronization_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_datasync_disable_order_synchronization_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
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
						'name' => 'clerk_search_enabled',
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
						'name' => 'clerk_search_template',
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
						'name' => 'clerk_livesearch_enabled',
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
						'name' => 'clerk_livesearch_categories',
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
						'name' => 'clerk_livesearch_template',
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
						'name' => 'clerk_powerstep_enabled',
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
                        'type' => 'select',
                        'label' => $this->l('Powerstep Type'),
                        'name' => 'clerk_powerstep_type',
                        'class' => 't',
                        'options' => array(
                            'query' => array(
                                array(
                                    'value' => self::TYPE_PAGE,
                                    'name' => $this->l('Page')
                                ),
                                array(
                                    'value' => self::TYPE_POPUP,
                                    'name' => $this->l('Popup')
                                )
                            ),
                            'id' => 'value',
                            'name' => 'name',
                        )
                    ),
					array(
						'type' => 'text',
						'label' => $this->l('Templates'),
						'name' => 'clerk_powerstep_templates',
						'desc' => $this->l('A comma separated list of clerk templates to render')
					),
				)
			),
		);

        //Exit intent settings
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Exit Intent Settings'),
                    'icon' => 'icon-shopping-cart'
                ),
                'input' => array(
                    array(
                        'type' => $booleanType,
                        'label' => $this->l('Enabled'),
                        'name' => 'clerk_exit_intent_enabled',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'clerk_exit_intent_enabled_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'clerk_exit_intent_enabled_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Template'),
                        'name' => 'clerk_exit_intent_template',
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
		return array(
			'clerk_public_key' => Configuration::get('CLERK_PUBLIC_KEY', $this->language_id, null, $this->shop_id),
			'clerk_private_key' => Configuration::get('CLERK_PRIVATE_KEY', $this->language_id, null, $this->shop_id),
			'clerk_import_url' => _PS_BASE_URL_,
			'clerk_search_enabled' => Configuration::get('CLERK_SEARCH_ENABLED', $this->language_id, null, $this->shop_id),
			'clerk_search_template' => Configuration::get('CLERK_SEARCH_TEMPLATE', $this->language_id, null, $this->shop_id),
			'clerk_livesearch_enabled' => Configuration::get('CLERK_LIVESEARCH_ENABLED', $this->language_id, null, $this->shop_id),
			'clerk_livesearch_categories' => Configuration::get('CLERK_LIVESEARCH_CATEGORIES', $this->language_id, null, $this->shop_id),
			'clerk_livesearch_template' => Configuration::get('CLERK_LIVESEARCH_TEMPLATE', $this->language_id, null, $this->shop_id),
			'clerk_powerstep_enabled' => Configuration::get('CLERK_POWERSTEP_ENABLED', $this->language_id, null, $this->shop_id),
			'clerk_powerstep_type' => Configuration::get('CLERK_POWERSTEP_TYPE', $this->language_id, null, $this->shop_id),
			'clerk_powerstep_templates' => Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->language_id, null, $this->shop_id),
            'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $this->language_id, null, $this->shop_id),
            'clerk_datasync_disable_order_synchronization' => Configuration::get('CLERK_DISABLE_ORDER_SYNC', $this->language_id, null, $this->shop_id),
            'clerk_datasync_fields' => Configuration::get('CLERK_DATASYNC_FIELDS', $this->language_id, null, $this->shop_id),
            'clerk_exit_intent_enabled' => Configuration::get('CLERK_EXIT_INTENT_ENABLED', $this->language_id, null, $this->shop_id),
            'clerk_exit_intent_template' => Configuration::get('CLERK_EXIT_INTENT_TEMPLATE', $this->language_id, null, $this->shop_id),
		);
	}

    public function hookTop($params)
    {
        if (Configuration::get('CLERK_SEARCH_ENABLED', $this->context->language->id, null, $this->context->shop->id)) {
            $key = $this->getCacheId('clerksearch-top' . (( ! isset($params['hook_mobile']) || ! $params['hook_mobile']) ? '' : '-hook_mobile'));

//            if (Tools::getValue('search_query')) {
                $this->smarty->assign(array(
                        'clerksearch_type' => 'top',
                        'search_query'     => (string)Tools::getValue('search_query', ''),
                        'livesearch_enabled' => (bool)Configuration::get('CLERK_LIVESEARCH_ENABLED', $this->context->language->id, null, $this->context->shop->id),
                        'livesearch_categories' => (int)Configuration::get('CLERK_LIVESEARCH_CATEGORIES', $this->context->language->id, null, $this->context->shop->id),
                        'livesearch_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_LIVESEARCH_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),
                    )
                );

            return $this->display(__FILE__, 'search-top.tpl', $key);
        }
    }

	/**
	 * Add visitor tracking to footer
	 *
	 * @return mixed
	 */
	public function hookFooter()
	{
	    //Determine if we should redirect to powerstep
        $controller = $this->context->controller;
        $cookie = $this->context->cookie;

        $popup = '';

        //Determine if powerstep is enabled
        if (Configuration::get('CLERK_POWERSTEP_ENABLED', $this->context->language->id, null, $this->context->shop->id)) {
            if ($cookie->clerk_show_powerstep == true) {
                if (Configuration::get('CLERK_POWERSTEP_TYPE', $this->context->language->id, null, $this->context->shop->id) === self::TYPE_PAGE) {
                    $url = $this->context->link->getModuleLink('clerk', 'added', array('id_product' => $cookie->clerk_last_product));

                    //Clear cookies
                    unset($cookie->clerk_show_powerstep);
                    unset($cookie->clerk_last_product);

                    Tools::redirect($url);
                } else {
                    $id_product = $cookie->clerk_last_product;

                    $product = new Product($id_product, true, $this->context->language->id, $this->context->shop->id);

                    if (!Validate::isLoadedObject($product)) {
                        Tools::redirect('index.php');
                    }

                    $image = Image::getCover($id_product);

                    $templatesConfig = Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->context->language->id, null, $this->context->shop->id);
                    $templates = array_filter(explode(',', $templatesConfig));

                    $categories = $product->getCategories();
                    $category = reset($categories);

                    $this->context->smarty->assign(array(
                        'templates' => $templates,
                        'product' => $product,
                        'category' => $category,
                        'image' => $image,
                        'order_process' => Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order',
                    ));

                    //Clear cookies
                    unset($cookie->clerk_show_powerstep);
                    unset($cookie->clerk_last_product);

                    $popup .= $this->display(__FILE__, 'powerstep_popup.tpl');
                }
            }
        }

        $is_v16 = true;

        if (version_compare(_PS_VERSION_, '1.7.0', '>=')) {
            $is_v16 = false;
        }

		//Assign template variables
		$this->context->smarty->assign(array(
            'clerk_public_key' => Configuration::get('CLERK_PUBLIC_KEY', $this->context->language->id, null, $this->context->shop->id),
            'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $this->context->language->id, null, $this->context->shop->id),
            'exit_intent_enabled' => (bool)Configuration::get('CLERK_EXIT_INTENT_ENABLED', $this->context->language->id, null, $this->context->shop->id),
            'exit_intent_template' => Tools::strtolower(str_replace(' ', '-', Configuration::get('CLERK_EXIT_INTENT_TEMPLATE', $this->context->language->id, null, $this->context->shop->id))),
            'powerstep_enabled' => Configuration::get('CLERK_POWERSTEP_ENABLED', $this->context->language->id, null, $this->context->shop->id),
            'powerstep_type' => Configuration::get('CLERK_POWERSTEP_TYPE', $this->context->language->id, null, $this->context->shop->id),
            'isv17' => $is_v16
        ));

        $output = $this->display(__FILE__, 'visitor_tracking.tpl');
        $output .= $popup;

        return $output;
	}

    /**
     * Hook cart save action
     */
	public function hookActionCartSave()
    {
        if (Tools::getValue('add')) {
            $this->context->cookie->clerk_show_powerstep = true;
            $this->context->cookie->clerk_last_product = Tools::getValue('id_product');
        }
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
		$order = isset($params['order']) ? $params['order'] : $params['objOrder'];

		if ($order) {
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
                    'clerk_datasync_collect_emails' => Configuration::get('CLERK_DATASYNC_COLLECT_EMAILS', $this->context->language->id, null, $this->context->shop->id),
                )
            );

            return $this->display(__FILE__, 'sales_tracking.tpl');
        }
	}

    /**
     * Add clerk css to backend
     *
     * @param $arr
     */
    public function hookActionAdminControllerSetMedia($params) {
        if (isset($this->context) && isset($this->context->controller)) {
            $this->context->controller->addCss($this->_path.'views/css/clerk.css');
        } else {
            Tools::addCSS($this->_path.'/views/css/clerk.css');
        }
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
    private function getAllLanguages($shop_id = null)
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
     * Render powerstep modal for PS 1.7
     *
     * @param Cart $cart
     * @param $id_product
     * @param $id_product_attribute
     * @return mixed
     * @throws Exception
     */
    public function renderModal(Cart $cart, $id_product, $id_product_attribute)
    {
        $data = (new \PrestaShop\PrestaShop\Adapter\Cart\CartPresenter)->present($cart);
        $product = null;

        foreach ($data['products'] as $p) {
            if ($p['id_product'] == $id_product && $p['id_product_attribute'] == $id_product_attribute) {
                $product = $p;
                break;
            }
        }

        $contentConfig = Configuration::get('CLERK_POWERSTEP_TEMPLATES', $this->context->language->id, null, $this->context->shop->id);
        $contents = array_filter(explode(',', $contentConfig));

        $category = $product['id_category_default'];

        $this->smarty->assign(array(
            'product' => $product,
            'category' => $category,
            'cart' => $data,
            'cart_url' => $this->getCartSummaryURL(),
            'contents' => $contents
        ));

        return $this->fetch('module:clerk/powerstepmodal.tpl');
    }

    /**
     * Get URL for cart page
     *
     * @return string
     */
    private function getCartSummaryURL()
    {
        return $this->context->link->getPageLink(
            'cart',
            null,
            $this->context->language->id,
            array(
                'action' => 'show'
            ),
            false,
            null,
            true
        );
    }
}
