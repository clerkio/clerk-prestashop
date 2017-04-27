{if isset($hook_mobile)}
    <div class="input_search" data-role="fieldcontain">
        <form method="get" action="{$link->getPageLink('search', true)|escape:'html'}" id="searchbox">
            <input type="hidden" name="controller" value="search" />
            <input type="hidden" name="orderby" value="position" />
            <input type="hidden" name="orderway" value="desc" />
            <input class="search_query" type="search" id="search_query_top" name="search_query" placeholder="{l s='Search' mod='clerk'}" value="{$search_query|escape:'html':'UTF-8'|stripslashes}" />
        </form>
    </div>
{else}
    <div id="clerk_search_block_top">
        <form method="get" action="{$link->getModuleLink('clerk', 'search')|escape:'html'}" id="clerk_searchbox">
            <p>
                <label for="clerk_search_query_top"><!-- image on background --></label>
                <input class="search_query" type="text" id="clerk_search_query_top" name="search_query" value="{$search_query|escape:'html':'UTF-8'|stripslashes}" />
                <input type="submit" name="submit_search" value="{l s='Search' mod='clerk'}" class="button" />
            </p>
        </form>
    </div>
{/if}

{if ($livesearch_enabled)}
    <span
            class="clerk"
            data-template="@{$livesearch_template|escape:'html':'UTF-8'}"
            data-live-search-categories="{$livesearch_include_categories}"
            data-live-search-categories-title="{l s='Categories' mod='clerk'}"
            data-live-search-products-title="{l s='Products' mod='clerk'}"
            data-bind-live-search="#clerk_search_query_top">
    </span>
{/if}