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

    <div class="clerk-powerstep clerk-popup">
        <div class="clerk-continue">
            <a href="{$continue|escape:'html'}"
               class="btn btn-secondary clerk-pull-left">{l s='Continue Shopping' mod='clerk'}</a>
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
    {if ! empty($templates)}
        <div class="clerk-powerstep-templates">
            <div class="clerk-powerstep">
                <div class="clerk-continue">
                    <a href="{$continue|escape:'html'}"
                       class="btn btn-secondary clerk-pull-left">{l s='Continue Shopping' mod='clerk'}</a>
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
            {$count = 0}
            {$Issetdataexclude = false}
            {$dataexcludestring = ''}
            {foreach from=$templates item=template}
                {$count = $count+1}
                {assign var=id value="clerk_`$unix`$count"}
                <span class="clerk"
                      id="{$id}"
                {if $Issetdataexclude}
                     data-exclude-from="{$dataexcludestring}"
                {/if}
                      data-template="@{$template}"
                      data-products="[{$product->id}]"
                      data-category="{$category}"
                ></span>
                {if $count == 1}
                    {$dataexcludestring = "`$dataexcludestring`#`$id`:limit(4)"}
                {else}
                    {$dataexcludestring = ",`$dataexcludestring`#`$id`:limit(4)"}
                {/if}
                {$Issetdataexclude = true}
            {/foreach}
        </div>
    {/if}
{/block}