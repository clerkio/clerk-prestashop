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
<style>
    .clerk-popup {
        position: fixed;

        top: 10%;

        z-index: 16777271;

        display: none;

        padding: 20px;

        margin: 0 5%;

        background-color: white;

        border: 1px solid #eee;

        border-radius: 5px;

        box-shadow: 0px 8px 40px 0px rgba(0, 0, 60, 0.15);
    }
</style>

<div id="clerk_powerstep" class="clerk-popup">
<div class="clerk_powerstep_header">
    <h2>{$product->name|escape:'html':'UTF-8'} {l s=' added to cart' mod='clerk'}</h2>
</div>
<div class="clerk_powerstep_image">
    <img src="{$link->getImageLink($product->link_rewrite, $image.id_image, 'small_default')|escape:'html':'UTF-8'}"
         alt="{$product->name|escape:'html':'UTF-8'}"/>
</div>
<div class="clerk_powerstep_clear actions">
    <button class="action primary clerk_powerstep_button clerk_powerstep_continue"
            onClick="location.href='{$link->getPageLink("$order_process", true)|escape:'html'}';">Continue to Checkout
    </button>
    <button class="action clerk_powerstep_button clerk_powerstep_close">{l s='Continue Shopping' mod='clerk'}</button>
</div>
{assign var=_i value=0}
{assign var=_exclude_string value=""}
{assign var=default_class value=".clerk_"}
{assign var=exc_sep value=", "}
<div class="clerk_powerstep_templates">
    {foreach from=$templates item=template}
        <span class="clerk {if $ExcludeDuplicates}clerk_{$_i}{/if}"
            {if $ExcludeDuplicates && $_i > 0}
            data-exclude-from="{$_exclude_string}"
            {/if}
            data-template="@{$template}"
            data-products="[{$product->id}]"
            data-category="{$category}"
        ></span>
    {if $_i > 0}
        {assign var=_exclude_string value="$_exclude_string$exc_sep"}
    {/if}
    {assign var=_exclude_string value="$_exclude_string$default_class$_i"}
    {assign var=_i value=$_i+1}
    {/foreach}
</div>
</div>
<script>

        var popup = document.getElementById("clerk_powerstep");

        document.querySelector('.clerk_powerstep_close').addEventListener("click", () => {
                  popup.style.display = 'none';
        });

        popup.style.display = 'block';

</script>
