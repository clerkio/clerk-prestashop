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
{assign var=_i value=0}
{assign var=_exclude_string value=""}
{assign var=default_class value=".clerk_"}
{assign var=exc_sep value=", "}
<div class="clerk_powerstep_templates">
{foreach $Contents as $Content}

    {if $Content !== ''}

    <span class="clerk_powerstep {if $ExcludeDuplicates}clerk_powerstep_{$_i}{/if}"
    {if $ExcludeDuplicates && $_i > 0}
        data-exclude-from="{$_exclude_string}"
    {/if}
    data-template="@{$Content}" data-products="[{$ProductId}]"></span>
    {if $_i > 0}
        {assign var=_exclude_string value="$_exclude_string$exc_sep"}
    {/if}
    {assign var=_exclude_string value="$_exclude_string$default_class$_i"}
    {assign var=_i value=$_i+1}

    {/if}

{/foreach}
</div>
<script>
Clerk('content', '.clerk_powerstep');
</script>
