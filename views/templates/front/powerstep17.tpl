{extends file='page.tpl'}

{block name='page_content'}
<div class="clerk-powerstep">
    <div class="clerk-continue">
        <a href="{$continue|escape:'html'}" class="btn btn-secondary clerk-pull-left">{l s='Continue Shopping' mod='clerk'}</a>
    </div>
    <div class="clerk-powerstep-content">
        <img class="pull-left" src="{$link->getImageLink($product->link_rewrite, $image.id_image, 'small_default')|escape:'html':'UTF-8'}" alt="{$product->name|escape:'html':'UTF-8'}"/>
        <h3><i class="icon icon-check-circle"></i>{$product->name|escape:'html':'UTF-8'} {l s=' added to cart' mod='clerk'}</h3>
    </div>
    <div class="clerk-cart">
        <a href="{$link->getPageLink("$order_process", true)|escape:'html'}" class="clerk-pull-right btn btn-primary">{l s='Go to cart' mod='clerk'}</a>
    </div>
</div>
{if ! empty($templates)}
<div class="clerk-powerstep-templates">
    {foreach from=$templates item=template}
    <span class="clerk"
          data-template="@{$template}"
          data-products="[{$product->id}]"
          data-category="{$category}"
    ></span>
    {/foreach}
</div>
{/if}
{/block}