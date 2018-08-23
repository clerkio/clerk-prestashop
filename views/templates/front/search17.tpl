{extends file='page.tpl'}

{block name='page_content'}
<h1 class="page-heading">
    {l s='Search'}&nbsp;
    {if isset($search_query) && $search_query}
    <span class="lighter">
        "{if isset($search_query) && $search_query}{$search_query|escape:'html':'UTF-8'}{/if}"
    </span>
    {/if}
</h1>

<span
        id="clerk-search"
        class="clerk"
        data-template="@{$search_template|escape:'html':'UTF-8'}"
        data-limit="40"
        data-offset="0"
        data-target="#clerk-search-results"
        data-after-render="_clerk_after_load_event"
        data-query="{$search_query|escape:'html':'UTF-8'}">
</span>

<ul id="clerk-search-results"></ul>
<div id="clerk-search-no-results" style="display: none;"></div>

<button id="clerk-search-load-more-button" class="btn btn-default">{l s='Load More Results' mod='clerk'}</button>

<script type="text/javascript">
    // this code assumes that you have jQuery v. 1.7
    // if not replace jQuery with Clerk.ui.$

    function _clerk_after_load_event(data) {
        jQuery('#clerk-search-load-more-button').on('click', function() {
            var e = jQuery('#clerk-search');

            e.data('offset', e.data('offset') + e.data('limit'));

            Clerk.renderBlocks('#clerk-search');
        });

        if(data.response.result.length == 0) {
            jQuery('#clerk-search-load-more-button').hide();

            if(jQuery('#clerk-search-results').is(':empty')) {
                jQuery('#clerk-search-no-results').show();
            }
        }
    }
</script>
{/block}