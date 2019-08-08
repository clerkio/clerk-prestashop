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

class AdminClerkDashboardController extends ModuleAdminController
{
    /**
     * @var int
     */
    protected $language_id;

    /**
     * @var int
     */
    protected $shop_id;

    /**
     * @var string
     */
    protected $mode = 'dashboard';

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        parent::__construct();
        $this->meta_title = $this->l('Clerk Dashboard');
        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }

        //Set shop id
        $this->shop_id = (! empty(Tools::getValue('clerk_shop_select'))) ? (int)Tools::getValue('clerk_shop_select') : $this->context->shop->id;

        //Set language id
        $this->language_id = (! empty(Tools::getValue('clerk_language_select'))) ? (int)Tools::getValue('clerk_language_select') : $this->context->language->id;

        if (Tools::getIsset('submitDashboard')) {
            $this->mode = 'dashboard';
        } elseif (Tools::getIsset('submitSearchInsights')) {
            $this->mode = 'search';
        } elseif (Tools::getIsset('submitRecommendationsInsights')) {
            $this->mode = 'recommendations';
        } elseif (Tools::getIsset('submitEmailInsights')) {
            $this->mode = 'email';
        } elseif (Tools::getIsset('submitAudienceInsights')) {
            $this->mode = 'audience';
        }

        $this->tpl_view_vars = array(
            'shops' => $this->getAllShops(),
            'languages' => $this->getAllLanguages($this->shop_id),
            'id_language' => $this->language_id,
            'id_shop' => $this->shop_id,
            'mode' => $this->mode,
            'logoImg' => $this->module->getPathUri().'views/img/logo.png',
            'moduleName' => $this->module->displayName,
            'moduleVersion' => $this->module->version,
        );

        if ($this->getEmbedUrl()) {
            $this->tpl_view_vars['embed_url'] = $this->getEmbedurl();
        }
    }

    public function initContent()
    {
        parent::initContent();
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->l('Dashboard');
    }

    public function renderView()
    {
//        $content = $this->renderForm();
        $content = $this->renderDashboard();

        return $content;
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
     * Render dashboard template
     *
     * @return mixed
     */
    public function renderDashboard()
    {
        $helper = new HelperView($this);
        $this->setHelperDisplay($helper);
        $helper->tpl_vars = $this->getTemplateViewVars();

        $helper->base_tpl = 'dashboard.tpl';

        $jsPath = $this->module->getPathUri() . ' /views/js/clerk.js';
        if (isset($this->context) && isset($this->context->controller)) {
            $this->context->controller->addJs($jsPath);
        } else {
            Tools::addJs($jsPath);
        }

        $view = $helper->generateView();

        return $view;
    }

    /**
     * Get dashboard iframe url
     *
     * @return bool|string
     */
    private function getEmbedUrl()
    {
        $publicKey = Configuration::get('CLERK_PUBLIC_KEY', $this->language_id, null, $this->shop_id);
        $privateKey = Configuration::get('CLERK_PRIVATE_KEY', $this->language_id, null, $this->shop_id);

        if (!$publicKey || !$privateKey) {
            return false;
        }

        $storePart = $this->getStorePart($publicKey);
        return sprintf('https://my.clerk.io/#/store/%s/analytics/%s?key=%s&private_key=%s&embed=yes', $storePart, $this->mode, $publicKey, $privateKey);
    }

    /**
     * Get first 8 characters of public key
     *
     * @param $publicKey
     * @return string
     */
    protected function getStorePart($publicKey)
    {
        return Tools::substr($publicKey, 0, 8);
    }
}
