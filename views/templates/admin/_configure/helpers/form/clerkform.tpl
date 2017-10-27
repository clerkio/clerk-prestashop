{extends file="helpers/form/form.tpl"}

{block name="label"}
    {if $input.type == 'languageselector'}
         <div class="col-md-3 row" style="background-color: transparent;">
            <div class="top-logo">
                <img src="{$input.logoImg|escape:html}" alt="Clerk.io" style="float:left;max-width:64px;">
            </div>
            <div class="col-md-8 top-module-description">
                <h1 class="top-module-title" style="margin-top:0;">{$input.moduleName|escape:html}</h1>
                <div class="top-module-my-name">Version <strong>{$input.moduleVersion|escape:html}</strong></div>
            </div>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="input"}

    {if $input.type == 'languageselector'}

        <div class="row" style="background-color: transparent;" >
            <div class="col-md-4">
                <span><strong>{l s='Shop:' mod='clerk'}</strong></span>
                <select id="clerk_shop_select" name="clerk_shop_select">
                    {foreach $input.shops as $shop}
                        <option id="id_{$shop['id_shop']|escape}" value="{$shop['id_shop']|escape}"
                            {if ( $input.current_shop == $shop['id_shop'] )}
                            selected
                            {/if}
                            >
                            {$shop['name']|escape}
                        </option>
                    {/foreach}
                </select>
            </div>
            {if !$input.monolanguage }
            <div class="col-md-4">
                <span><strong>{l s='Language:' mod='clerk'}</strong></span>
                <select id="clerk_language_select" name="clerk_language_select">
                    {foreach $input.languages as $language}
                        <option id="id_{$language['id_lang']|escape}" value="{$language['id_lang']|escape}"
                            {if ( $input.current_language == $language['id_lang'] )}
                            selected
                            {/if}
                            >
                            {$language['name']|escape}
                        </option>
                    {/foreach}
                </select>                
            </div>
            {/if}
            <div class="col-md-3">
                <div >&nbsp;</div>
                <input type="submit" id="clerk_language_switch" value="{l s='Switch' mod='clerk'}" class="btn btn-primary">
                <input type="hidden" name="ignore_changes" id="ignore_changes" value="">
            </div>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}

{/block}