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

<div id="clerk_powerstep" class="clerk-popup">
    <div class="clerk_powerstep_header">
        <h2>{l s='You added %s to your shopping cart.' sprintf=[$product.name] mod='clerk'}</h2>
    </div>
    <div class="clerk_powerstep_image text-xs-center">
        <img class="product-image" src="{$product.cover.medium.url}" alt="{$product.cover.legend}" title="{$product.cover.legend}" itemprop="image">
    </div>
    <div class="clerk_powerstep_clear clearfix">
        <button class="btn btn-primary powerstep-cart float-xs-right" onclick="location.href = '{$cart_url}';" type="button" title="{l s='Cart'|escape:'html' mod='clerk'}">{l s='Cart'|escape:'html' mod='clerk'}</button>
        <button class="btn btn-secondary clerk_powerstep_button clerk_powerstep_close">{l s='Continue Shopping' mod='clerk'}</button>
    </div>
    <div class="clerk_powerstep_templates mt-1">
        {if ! empty($contents)}
            <div class="clerk-powerstep-templates">
                {foreach from=$contents item=content}
                    <span class="clerk"
                          data-template="@{$content}"
                          data-products="[{$product.id}]"
                          data-category="{$category}"
                    ></span>
                {/foreach}
            </div>
        {/if}
    </div>
</div>