<h1 class="page-heading">
    {l s='Search'}&nbsp;
    {if isset($search_query) && $search_query}
    <span class="lighter">
        "{if isset($search_query) && $search_query}{$search_query|escape:'html':'UTF-8'}{/if}"
    </span>
    {/if}
</h1>
<span id="clerk-search"
      class="clerk"
      data-template="@{$search_template|escape:'html':'UTF-8'}"
      data-query="{$search_query|escape:'html':'UTF-8'}">
</span>