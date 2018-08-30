<!-- Start of Clerk.io E-commerce Personalisation tool - www.clerk.io -->
<script type="text/javascript">
    window.clerkAsyncInit = function() {
        Clerk.config({
            key: '{$clerk_public_key}',
            collect_email: {$clerk_datasync_collect_emails}
        });

        {if ($powerstep_enabled)}
        //Handle powerstep
        prestashop.on("updateCart", function(e) {
            if (e.resp.success) {
                var product_id = e.resp.id_product;
                var product_id_attribute = e.resp.id_product_attribute;

                {if ($powerstep_type === 'page')}
                window.location.replace('{$link->getModuleLink('clerk', 'added') nofilter}' + "&id_product=" + encodeURIComponent(product_id));
                {else}
                $('#clerk_powerstep, #__clerk_overlay').remove();

                $.ajax({
                    url: "{$link->getModuleLink('clerk', 'powerstep') nofilter}",
                    method: "POST",
                    data: {
                        id_product: product_id,
                        id_product_attribute: product_id_attribute
                    },
                    success: function(res) {
                        $('body').append(res.data);
                        var popup = Clerk.ui.popup("#clerk_powerstep");

                        $(".clerk_powerstep_close").on("click", function() {
                            popup.close();
                        });

                        popup.show();

                        Clerk.renderBlocks(".clerk_powerstep_templates .clerk");
                    }
                });
                {/if}
            }
        });
        {/if}
    };

    (function(){
        var e = document.createElement('script'); e.type='text/javascript'; e.async = true;
        e.src = document.location.protocol + '//api.clerk.io/static/clerk.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(e, s);
    })();
</script>
<!-- End of Clerk.io E-commerce Personalisation tool - www.clerk.io -->
{if ($exit_intent_enabled)}
<span class="clerk"
      data-template="@{$exit_intent_template}"
      data-exit-intent="true">
</span>
{/if}