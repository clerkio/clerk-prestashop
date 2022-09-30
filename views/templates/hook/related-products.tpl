{assign var=_i value=0}
{assign var=_exclude_string value=""}
{foreach $Contents as $Content}
    {if $Content !== ''}
    <span class="clerk 
    {if $ExcludeDuplicates}clerk_{$_i}{/if}"
        {if $ExcludeDuplicates && $_i > 0}
        data-exclude-from="{$_exclude_string}"
        {/if}
        data-template="@{$Content}" 
        data-products="[{$ProductId}]"
        ></span>
    
    {if $_i > 0}
        {assign var=_exclude_string value="$_exclude_string`, `"}
    {/if}
    {assign var=_exclude_string value="$_exclude_string`.clerk_`$_i"}
    {assign var=_i value=_i+1}
    {/if}
{/foreach}
