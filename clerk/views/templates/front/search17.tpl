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

{extends file='page.tpl'}

{block name='page_content'}
    <h1 class="page-heading">
        {l s='Search' mod='clerk'}&nbsp;
        {if isset($search_query) && $search_query}
            <span class="lighter">
        "{if isset($search_query) && $search_query}{$search_query|escape:'html':'UTF-8'}{/if}"
    </span>
        {/if}
    </h1>
    <div id="clerk-search-filters"></div>
    <span
            id="clerk-search"
            class="clerk"
            data-template="@{$search_template|escape:'html':'UTF-8'}"
            data-limit="40"
            data-offset="0"
            data-target="#clerk-search-results"

            {if $faceted_navigation}
           
            data-facets-target="#clerk-search-filters" 
            data-facets-attributes='{$facets_enabled}'
            data-facets-titles='{$facets_title}'
            data-facets-design='{$facets_design}'

            {/if}

            {if $search_categories}
           
            data-search-categories = '{$search_number_categories}'
            data-search-pages = '{$search_number_pages}'
            data-search-pages-type = '{$search_pages_type}'

            {/if}

            data-query="{$search_query|escape:'html':'UTF-8'}">
</span>
    <ul id="clerk-search-results" style="overflow: hidden;"></ul>
    <div id="clerk-search-no-results" style="display: none;"></div>

{/block}