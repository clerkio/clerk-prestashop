{if isset($Contents) && isset($CategoryId)}
{foreach $Contents as $Content}

    {if $Content !== ''}

    <span class="clerk" data-template="@{$Content}" data-category="[{$CategoryId}]"></span>

    {/if}

{/foreach}
{/if}