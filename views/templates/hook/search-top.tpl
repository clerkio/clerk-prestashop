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

<div id="search_block_top" class="col-sm-4 clearfix">
    <form id="searchbox" method="get" action="{$link->getModuleLink('clerk', 'search')|escape:'html'}" >
        <input type="hidden" name="fc" value="module">
        <input type="hidden" name="module" value="clerk">
        <input type="hidden" name="controller" value="search">
        <input class="search_query form-control" type="text" id="search_query_top" name="search_query" placeholder="{l s='Search' mod='clerk'}" value="{$search_query|escape:'htmlall':'UTF-8'|stripslashes}" />
        <button type="submit" class="btn btn-default button-search">
            <span>{l s='Search' mod='clerk'}</span>
        </button>
    </form>
</div>
{if ($livesearch_enabled)}
    <span
            class="clerk"
            data-template="@{$livesearch_template|escape:'html':'UTF-8'}"
            data-instant-search-suggestions="{$livesearch_number_suggestions}"
            {if ($livesearch_enabled)}
            data-instant-search-categories="{$livesearch_number_categories}"
            {/if}
            data-instant-search-pages="{$livesearch_number_pages}"
            data-instant-search-pages-type="{$livesearch_pages_type}"
            data-instant-search="#search_query_top">
    </span>
{/if}
