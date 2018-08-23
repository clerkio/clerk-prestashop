<div id="clerk_powerstep" style="display: none;">
    <div class="clerk_powerstep_header">
        <h2>{$product->name|escape:'html':'UTF-8'} {l s=' added to cart' mod='clerk'}</h2>
    </div>
    <div class="clerk_powerstep_image">
        <img src="{$link->getImageLink($product->link_rewrite, $image.id_image, 'small_default')|escape:'html':'UTF-8'}" alt="{$product->name|escape:'html':'UTF-8'}"/>
    </div>
    <div class="clerk_powerstep_clear actions">
        <button class="action primary clerk_powerstep_button clerk_powerstep_continue" onClick="location.href='{$link->getPageLink("$order_process", true)|escape:'html'}';">Continue to Checkout</button>
        <button class="action clerk_powerstep_button clerk_powerstep_close">{l s='Continue Shopping' mod='clerk'}</button>
    </div>
    <div class="clerk_powerstep_templates">
        {foreach from=$templates item=template}
            <span class="clerk"
                  data-template="@{$template}"
                  data-products="[{$product->id}]"
                  data-category="{$category}"
            ></span>
        {/foreach}
    </div>
</div>
<script>
    var clerkInit = window.clerkAsyncInit;

    //Append powerstep poup logic to Clerk init
    window.clerkAsyncInit = function() {
        clerkInit();

        var popup = Clerk.ui.popup("#clerk_powerstep");

        $(".clerk_powerstep_close").on("click", function() {
            popup.close();
        });

        popup.show();
    };
</script>