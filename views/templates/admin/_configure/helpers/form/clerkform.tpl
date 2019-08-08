{*
*  @author Clerk.io
*  @copyright Copyright (c) 2017 Clerk.io
*
*  @license MIT License
*
*  Permission is hereby granted, free of charge, to any person obtaining a copy
*  of this software and associated documentation files (the "Software"), to deal
*  in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*}



{extends file="helpers/form/form.tpl"}

{block name="label"}
    {if $input.type == 'languageselector'}
         <div class="col-md-3 row" style="background-color: transparent;">
            <div class="top-logo">
                <img src="{$input.logoImg}" alt="Clerk.io" style="float:left;max-width:64px;">
            </div>
            <div class="col-md-8 top-module-description">
                <h1 class="top-module-title" style="margin-top:0;">{$input.moduleName}</h1>
                <div class="top-module-my-name">Version <strong>{$input.moduleVersion}</strong></div>
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