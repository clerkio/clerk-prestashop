{foreach $Contents as $Content}

    {if $Content !== ''}

    <span class="clerk" data-template="@{$Content}" data-products="[{$ProductId}]"></span>

    {/if}

{/foreach}
