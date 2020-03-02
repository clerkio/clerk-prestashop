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

{*<div id="search_block_top" class="col-sm-4 clearfix">
    <form id="searchbox" method="get" action="{$link->getModuleLink('clerk', 'search')|escape:'html'}" >
        <input type="hidden" name="fc" value="module">
        <input type="hidden" name="module" value="clerk">
        <input type="hidden" name="controller" value="search">
        <input class="search_query form-control" type="text" id="search_query_top" name="search_query" placeholder="{l s='Search' mod='clerk'}" value="{$search_query|escape:'htmlall':'UTF-8'|stripslashes}" />
        <button type="submit" class="btn btn-default button-search">
            <span>{l s='Search' mod='clerk'}</span>
        </button>
    </form>
</div>*}
<script type="text/javascript">

    function htmlDecode(input){
        var e = document.createElement('div');
        e.innerHTML = input;
        return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
    }

</script>

{if ($search_enabled)}

    <script type="text/javascript">

        ClerkSearchPage = function(){

            var form_selector = htmlDecode('{$livesearch_form_selector}');
            var search_field_selector = htmlDecode('{$livesearch_selector}');

            $(search_field_selector).each(function() {
                $(this).attr('name', 'search_query');
            });

            $(form_selector).each(function (){
                $(this).attr('action', '{$baseUrl}/module/clerk/search');
                module_hidden = document.createElement("input");
                module_hidden.setAttribute("type", "hidden");
                module_hidden.setAttribute("name", "fc");
                module_hidden.setAttribute("value", "module");
                clerk_hidden = document.createElement("input");
                clerk_hidden.setAttribute("type", "hidden");
                clerk_hidden.setAttribute("name", "module");
                clerk_hidden.setAttribute("value", "clerk");
                $(this).append(module_hidden,clerk_hidden)
            });

        };

        if(window.jQuery) $( document ).ready(function() { ClerkSearchPage()  });
        else{
            var script = document.createElement('script');
            document.head.appendChild(script);
            script.type = 'text/javascript';
            script.src = "https://code.jquery.com/jquery-3.4.1.min.js";
            script.integrity = "sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=";
            script.crossorigin = "anonymous";

            script.onload = ClerkSearchPage;
        }

    </script>

{/if}
{if ($livesearch_enabled)}

    <script type="text/javascript">

        var form_selector = htmlDecode('{$livesearch_form_selector}');
        var search_field_selector = htmlDecode('{$livesearch_selector}');

        ClerkLiveSearch = function(){

            $(search_field_selector).each(function() {
                $(this).removeAttr("autocomplete");
            });

            StockAutoComplete = $(".ui-autocomplete");

            if (StockAutoComplete) {

                StockAutoComplete.each(function() {
                    $(this).remove();
                });

            }

        };

        if(window.jQuery) $( document ).ready(function() { ClerkSearchPage()  });
        else{
            var script = document.createElement('script');
            document.head.appendChild(script);
            script.type = 'text/javascript';
            script.src = "https://code.jquery.com/jquery-3.4.1.min.js";
            script.integrity = "sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=";
            script.crossorigin = "anonymous";

            script.onload = ClerkLiveSearch;
        }

    </script>

    <span
            class="clerk"
            data-template="@{$livesearch_template|escape:'html':'UTF-8'}"
            data-instant-search-suggestions="{$livesearch_number_suggestions}"
            data-instant-search-categories="{$livesearch_number_categories}"
            data-instant-search-pages="{$livesearch_number_pages}"
            data-instant-search-pages-type="{$livesearch_pages_type}"
            data-instant-search-positioning="{$livesearch_dropdown_position}"
            data-instant-search="{$livesearch_selector}">
    </span>

{/if}