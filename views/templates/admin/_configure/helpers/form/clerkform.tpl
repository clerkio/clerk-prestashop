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
    {if isset($input.type) && $input.type == 'languageselector'}
        <div class="col-md-3 row" style="background-color: transparent;">
            <div class="top-logo">
                {if isset($input.logoImg)}
                <img src="{$input.logoImg}" alt="Clerk.io" style="float:left;max-width:64px;">
                {/if}
            </div>
            <div class="col-md-8 top-module-description">
                {if isset($input.moduleName)}
                <h1 class="top-module-title" style="margin-top:0;">{$input.moduleName}</h1>
                {/if}
                {if isset($input.moduleVersion) && isset($input.prestashopVersion)}
                <div class="top-module-my-name"><p>Version <strong>{$input.moduleVersion}</strong></p><p>PrestaShop Version <strong>{$input.prestashopVersion}</strong></p></div>
                {/if}
            </div>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="input"}

    {if isset($input.type) && $input.type == 'languageselector'}

        <div class="row" style="background-color: transparent;" >
            <div class="col-md-4">
                <span><strong>{l s='Shop:' mod='clerk'}</strong></span>
                <select id="clerk_shop_select" name="clerk_shop_select">
                    {if isset($input.shops)}
                    {foreach $input.shops as $shop}
                        {if isset($shop['id_shop']) && isset($input.current_shop) && isset($shop['name'])}
                        <option id="id_{$shop['id_shop']|escape}" value="{$shop['id_shop']|escape}"
                            {if ( $input.current_shop == $shop['id_shop'] )}
                            selected
                            {/if}
                            >
                            {$shop['name']|escape}
                        </option>
                        {/if}
                    {/foreach}
                    {/if}
                </select>
            </div>
            <div class="col-md-4">
                <span><strong>{l s='Language:' mod='clerk'}</strong></span>
                <select id="clerk_language_select" name="clerk_language_select">
                    {if isset($input.languages)}
                    {foreach $input.languages as $language}
                        {if isset($language['id_lang']) && isset($input.current_language) && isset($language['name'])}
                        <option id="id_{$language['id_lang']|escape}" value="{$language['id_lang']|escape}"
                            {if ( $input.current_language == $language['id_lang'] )}
                            selected
                            {/if}
                            >
                            {$language['name']|escape}
                        </option>
                        {/if}
                    {/foreach}
                    {/if}
                </select>
            </div>
            <div class="col-md-3">
                <div >&nbsp;</div>
                <input type="submit" id="clerk_language_switch" onclick="Setignore();" value="{l s='Switch' mod='clerk'}" class="btn btn-primary">
                <input type="hidden" name="ignore_changes" id="ignore_changes" value="">
            </div>
        </div>
        <script>

            function Setignore() {
                document.getElementById("ignore_changes").value = "1";
            }

        </script>
    {else}
        {$smarty.block.parent}
    {/if}

{/block}