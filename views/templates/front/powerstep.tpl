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

{if $popup != 0}
<div class="clerk-powerstep clerk-popup">
    <div class="clerk-continue">
        <a href="{$continue|escape:'html'}" class="clerk-pull-left">{l s='Continue Shopping' mod='clerk'}</a>
    </div>
    <div class="clerk-powerstep-content">
        <img class="pull-left" src="{$link->getImageLink($product->link_rewrite, $image.id_image, 'small_default')|escape:'html':'UTF-8'}" alt="{$product->name|escape:'html':'UTF-8'}"/>
        <h3><i class="icon icon-check-circle"></i>{$product->name|escape:'html':'UTF-8'} {l s=' added to cart' mod='clerk'}</h3>
    </div>
    <div class="clerk-cart">
        <a href="{$link->getPageLink("$order_process", true)|escape:'html'}" class="clerk-pull-right btn btn-default">{l s='Go to cart' mod='clerk'}</a>
    </div>
</div>
{else}
{if ! empty($templates)}
<div class="clerk-powerstep-templates">
    <div class="clerk-powerstep">
        <div class="clerk-continue">
            <a href="{$continue|escape:'html'}"
               class="btn btn-primary clerk-pull-left">{l s='Continue Shopping' mod='clerk'}</a>
        </div>
        <div class="clerk-powerstep-content">
            <img class="pull-left"
                 src="{$link->getImageLink($product->link_rewrite, $image.id_image, 'small_default')|escape:'html':'UTF-8'}"
                 alt="{$product->name|escape:'html':'UTF-8'}"/>
            <h3>
                <i class="icon icon-check-circle"></i>{$product->name|escape:'html':'UTF-8'} {l s=' added to cart' mod='clerk'}
            </h3>
        </div>
        <div class="clerk-cart">
            <a href="{$link->getPageLink("$order_process", true)|escape:'html'}"
               class="clerk-pull-right btn btn-primary">{l s='Go to cart' mod='clerk'}</a>
        </div>
    </div>
    {assign var=_i value=0}
    {assign var=_exclude_string value=""}
    {assign var=default_class value=".clerk_"}
    {assign var=exc_sep value=", "}
    {foreach from=$templates item=template}
        <span class="clerk clerk_{$_i}"
            {if $_i > 0}
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
{/if}
{/if}