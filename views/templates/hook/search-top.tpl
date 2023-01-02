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

<script>

    function htmlDecode(input){
        var e = document.createElement('div');
        e.innerHTML = input;
        return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
    }

</script>

{if ($search_enabled)}

    <script>

        ClerkSearchPage = function(){
            var form_selector = htmlDecode('{$livesearch_form_selector}');
            var search_field_selector = htmlDecode('{$livesearch_selector}');
            var forms = document.querySelectorAll(form_selector);
            forms.forEach(function(el, index, array){
                el.setAttribute('action', '{$baseUrl}module/clerk/search');
                module_hidden = document.createElement("input");
                module_hidden.setAttribute("type", "hidden");
                module_hidden.setAttribute("name", "fc");
                module_hidden.setAttribute("value", "module");
                clerk_hidden = document.createElement("input");
                clerk_hidden.setAttribute("type", "hidden");
                clerk_hidden.setAttribute("name", "module");
                clerk_hidden.setAttribute("value", "clerk");
                el.append(module_hidden,clerk_hidden)

            });

            setTimeout(function(){ //dont know why but its needed

                var fields = document.querySelectorAll(search_field_selector);
                fields.forEach(function(el, index, array){
                    el.setAttribute('name', 'search_query');
                    el.classList.add('clerk-ios-mobile-zoom-fix');
                });

            }, 100);

        };

        function DOMready(fn) {
            if (document.readyState != 'loading') {
                fn();
            } else if (document.addEventListener) {
                document.addEventListener('DOMContentLoaded', fn);
            } else {
                document.attachEvent('onreadystatechange', function() {
                if (document.readyState != 'loading')
                    fn();
                });
            }
        }

        window.DOMready(function() {
                ClerkSearchPage();
        });

    </script>

{/if}
{if ($livesearch_enabled)}

    <script>

        ClerkLiveSearch = function(){

        var live_form_selector = htmlDecode('{$livesearch_form_selector}');
        var live_search_field_selector = htmlDecode('{$livesearch_selector}');
        
        setTimeout(function(){ //dont know why but its needed

            var live_fields = document.querySelectorAll(live_search_field_selector);
                live_fields.forEach(function(el, index, array){
                    el.removeAttribute('autocomplete');
                    el.classList.add('clerk-ios-mobile-zoom-fix');
                });

                var live_fields_StockAutoComplete = document.querySelectorAll(".ui-autocomplete");

                if(live_fields_StockAutoComplete){
                    live_fields_StockAutoComplete.forEach(function(el, index, array){
                        el.remove();
                    });
                }

            }, 100);

        };

        function liveDOMready(fn) {
            if (document.readyState != 'loading') {
                fn();
            } else if (document.addEventListener) {
                document.addEventListener('DOMContentLoaded', fn);
            } else {
                document.attachEvent('onreadystatechange', function() {
                if (document.readyState != 'loading')
                    fn();
                });
            }
        }

        window.liveDOMready(function() {
                ClerkLiveSearch(); 
        });

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
    <style>
    @media screen and (max-width: 600px){
        .clerk-ios-mobile-zoom-fix{
            font-size: 18px !important;
        }
    }
    </style>
{/if}