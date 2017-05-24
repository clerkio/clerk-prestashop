<span class="clerk"
      data-api="log/sale"
      data-sale="{$clerk_order_id}"
      {if $clerk_datasync_collect_emails}
      data-email="{$clerk_customer_email}"
      {/if}
      data-products='{$clerk_products}'>
</span>