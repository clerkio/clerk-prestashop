{if isset($Contents) && isset($CategoryId)}
{assign var=_i value=0}
{assign var=_exclude_string value=""}
{assign var=default_class value=".clerk_"}
{assign var=exc_sep value=", "}
{foreach $Contents as $Content}

    {if $Content !== ''}

    <span class="clerk {if $ExcludeDuplicates}clerk_{$_i}{/if}"
    {if $ExcludeDuplicates && $_i > 0}
    data-exclude-from="{$_exclude_string}"
    {/if}
    data-template="@{$Content}" 
    data-category="{$CategoryId}"></span>
    {if $_i > 0}
        {assign var=_exclude_string value="$_exclude_string$exc_sep"}
    {/if}
    {assign var=_exclude_string value="$_exclude_string$default_class$_i"}
    {assign var=_i value=$_i+1}
    {/if}

{/foreach}
{/if}
