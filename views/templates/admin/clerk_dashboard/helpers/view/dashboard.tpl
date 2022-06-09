{*
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
*}

<form id="dashboard_form" class="defaultForm form-horizontal" method="post" novalidate>
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-globe"></i>Select store
        </div>
        <div class="form-wrapper">
            <div class="form-group">
                <div class="col-md-3 row" style="background-color: transparent;">
                    <div class="top-logo">
                        {if isset($logoImg)}
                        <img src="{$logoImg}" alt="Clerk.io" style="float:left;max-width:64px;">
                        {/if}
                    </div>
                    <div class="col-md-8 top-module-description">
                        {if isset($moduleName)}
                        <h1 class="top-module-title" style="margin-top:0;">{$moduleName}</h1>
                        {/if}
                        {if isset($moduleVersion)}
                        <div class="top-module-my-name">Version <strong>{$moduleVersion}</strong></div>
                        {/if}
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="row" style="background-color: transparent;">
                        <div class="col-md-4">
                            <span><strong>{l s='Shop:' mod='clerk'}</strong></span>
                            <select id="clerk_shop_select" name="clerk_shop_select">
                                {if isset($shops)}
                                {foreach $shops as $shop}
                                    {if isset($shop['id_shop']) && isset($id_shop) && isset($shop['name'])}
                                    <option id="id_{$shop['id_shop']|escape}" value="{$shop['id_shop']|escape}"
                                            {if ( $id_shop == $shop['id_shop'] )}
                                                selected
                                            {/if}
                                    >
                                        {$shop['name']|escape}
                                    </option>
                                    {/if}
                                {/foreach}
                                {/if}
                            </select>
                        </div>
                            <div class="col-md-4">
                                {if isset($id_language)}
                                {$id_language}
                                {/if}
                                <span><strong>{l s='Language:' mod='clerk'}</strong></span>
                                <select id="clerk_language_select" name="clerk_language_select">
                                    {if isset($languages)}
                                    {foreach $languages as $language}
                                        {if isset($language['id_lang']) && isset($id_language) && isset($language['name'])}
                                        <option id="id_{$language['id_lang']|escape}" value="{$language['id_lang']|escape}"
                                                {if ( $id_language == $language['id_lang'] )}
                                                    selected
                                                {/if}
                                        >
                                            {$language['name']|escape}
                                        </option>
                                        {/if}
                                    {/foreach}
                                    {/if}
                                </select>
                            </div>
                        <div class="col-md-3">
                            <div>&nbsp;</div>
                            <input type="submit" id="clerk_language_switch" value="{l s='Switch' mod='clerk'}" class="btn btn-primary">
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.form-wrapper -->
        <div class="btn-group">
            {if isset($mode)}
            <button type="submit" name="submitDashboard" class="btn btn-default{if $mode === 'dashboard'} active{/if}">
            Dashboard
            </button>
            <button type="submit" name="submitSearchInsights" class="btn btn-default{if $mode === 'search'} active{/if}">
            Search Insights
            </button>
            <button type="submit" name="submitRecommendationsInsights" class="btn btn-default{if $mode === 'recommendations'} active{/if}">
            Recommendations Insights
            </button>
            <button type="submit" name="submitEmailInsights" class="btn btn-default{if $mode === 'email'} active{/if}">
            Email Insights
            </button>
            <button type="submit" name="submitAudienceInsights" class="btn btn-default{if $mode === 'audience'} active{/if}">
            Audience Insights
            </button>
            {/if}
        </div>
    </div>
    {if isset($embed_url)}
    <iframe id="clerk-embed" src="{$embed_url}" frameborder="0" width="100%" height="2400"></iframe>
    {/if}
</form>
