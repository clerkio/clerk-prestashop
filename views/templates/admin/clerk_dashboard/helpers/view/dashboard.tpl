<form id="dashboard_form" class="defaultForm form-horizontal" method="post" novalidate>
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-globe"></i>Select store
        </div>
        <div class="form-wrapper">
            <div class="form-group">
                <div class="col-md-3 row" style="background-color: transparent;">
                    <div class="top-logo">
                        <img src="{$logoImg|escape:html}" alt="Clerk.io" style="float:left;max-width:64px;">
                    </div>
                    <div class="col-md-8 top-module-description">
                        <h1 class="top-module-title" style="margin-top:0;">{$moduleName|escape:html}</h1>
                        <div class="top-module-my-name">Version <strong>{$moduleVersion|escape:html}</strong></div>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="row" style="background-color: transparent;">
                        <div class="col-md-4">
                            <span><strong>{l s='Shop:' mod='clerk'}</strong></span>
                            <select id="clerk_shop_select" name="clerk_shop_select">
                                {foreach $shops as $shop}
                                    <option id="id_{$shop['id_shop']|escape}" value="{$shop['id_shop']|escape}"
                                            {if ( $id_shop == $shop['id_shop'] )}
                                                selected
                                            {/if}
                                    >
                                        {$shop['name']|escape}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        {if !$input.monolanguage }
                            <div class="col-md-4">
                                {$id_language}
                                <span><strong>{l s='Language:' mod='clerk'}</strong></span>
                                <select id="clerk_language_select" name="clerk_language_select">
                                    {foreach $languages as $language}
                                        <option id="id_{$language['id_lang']|escape}" value="{$language['id_lang']|escape}"
                                                {if ( $id_language == $language['id_lang'] )}
                                                    selected
                                                {/if}
                                        >
                                            {$language['name']|escape}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                        {/if}
                        <div class="col-md-3">
                            <div>&nbsp;</div>
                            <input type="submit" id="clerk_language_switch" value="{l s='Switch' mod='clerk'}" class="btn btn-primary">
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.form-wrapper -->
        <div class="btn-group">
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
        </div>
    </div>
    {if $embed_url}
    <iframe id="clerk-embed" src="{$embed_url}" frameborder="0" width="100%" height="2400"></iframe>
    {/if}
</form>
