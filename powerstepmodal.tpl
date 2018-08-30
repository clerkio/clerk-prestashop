<div id="clerk_powerstep" style="display: none;">
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