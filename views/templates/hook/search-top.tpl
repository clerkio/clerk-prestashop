<div id="search_block_top" class="col-sm-4 clearfix">
    <form id="searchbox" method="get" action="{$link->getModuleLink('clerk', 'search')|escape:'html'}" >
        <input class="search_query form-control" type="text" id="search_query_top" name="search_query" placeholder="{l s='Search' mod='blocksearch'}" value="{$search_query|escape:'htmlall':'UTF-8'|stripslashes}" />
        <button type="submit" class="btn btn-default button-search">
            <span>{l s='Search' mod='clerk'}</span>
        </button>
    </form>
</div>
{if ($livesearch_enabled)}
    <span
            class="clerk"
            data-template="@{$livesearch_template|escape:'html':'UTF-8'}"
            data-live-search-categories="{$livesearch_categories}"
            data-live-search-categories-title="{l s='Categories' mod='clerk'}"
            data-live-search-products-title="{l s='Products' mod='clerk'}"
            data-bind-live-search="#search_query_top">
    </span>
{/if}