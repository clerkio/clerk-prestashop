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

<!-- Start of Clerk.io E-commerce Personalisation tool - www.clerk.io -->
<script>
    (function(){
        (function(w,d){
            var e=d.createElement('script');e.type='text/javascript';e.async=true;
            e.src=(d.location.protocol=='https:'?'https':'http')+{if isset($custom_clerk_js)}'{$custom_clerk_js}'{else}'://cdn.clerk.io/clerk.js'{/if};
            var s=d.getElementsByTagName('script')[0];s.parentNode.insertBefore(e,s);
            w.__clerk_q=w.__clerk_q||[];w.Clerk=w.Clerk|| function(){ w.__clerk_q.push(arguments) };
        })(window,document);
    })();

    Clerk('config', {
{if isset($clerk_public_key)}key: '{$clerk_public_key}',{/if}
{if isset($clerk_datasync_collect_emails)}collect_email: {$clerk_datasync_collect_emails},{/if}
{if isset($clerk_language)}language: '{$clerk_language}',{/if}
globals: {
{if isset($customer_logged_in)}customer_logged_in: '{$customer_logged_in}',{/if}
{if isset($customer_group_id)}customer_group_id: '{$customer_group_id}',{/if}
{if isset($currency_symbol)}currency_symbol: '{$currency_symbol}',{/if}
{if isset($currency_iso)}currency_iso: '{$currency_iso}',{/if}
},
formatters: {
  currency_converter: function(price) {
    let conversion_rate = parseFloat({$currency_conversion_rate});
    return price * conversion_rate;
   }
}
});

{if isset($clerk_additional_scripts)}
  {$clerk_additional_scripts nofilter}
{/if}

// Clerk.js Context - Track current page context (product, category, or page)
Clerk('context', {
  product: {if isset($clerk_context_product) && $clerk_context_product}{$clerk_context_product}{else}null{/if},
  category: {if isset($clerk_context_category) && $clerk_context_category}{$clerk_context_category}{else}null{/if},
  page: {if isset($clerk_context_page) && $clerk_context_page}{if $clerk_context_page_is_string == 1}'{$clerk_context_page}'{else}{$clerk_context_page}{/if}{else}null{/if}
});

{if isset($customer_email)}
if(typeof window.Clerk == 'function'){
  Clerk('call', 'log/email', {
    email: '{$customer_email}'
  });
}
{/if}
</script>

<!-- End of Clerk.io E-commerce Personalisation tool - www.clerk.io -->
