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

<script>

async function checkcart(){
    const cartRes = await fetch("${clerk_basket_link}");
    let clerk_productids = await cartRes.json();
    clerk_productids = clerk_productids.map(Number);
    var clerk_last_productids = [];
    if( localStorage.getItem('clerk_productids') !== null ){
        clerk_last_productids = await localStorage.getItem('clerk_productids');
        clerk_last_productids = clerk_last_productids.split(",").map(Number);
    }
    clerk_productids = clerk_productids.sort((a, b) => a - b);
    clerk_last_productids = clerk_last_productids.sort((a, b) => a - b);
    if(JSON.stringify(clerk_productids) !== JSON.stringify(clerk_last_productids)){
        Clerk('cart', 'set', clerk_productids);
    }
    localStorage.setItem("clerk_productids", clerk_productids);
}

    {if ($clerk_collect_cart ==true && $isv16) }
        let open = XMLHttpRequest.prototype.open; 
        XMLHttpRequest.prototype.open = async function() {
            this.addEventListener("load", async function(){
              if( this.responseURL.includes("cart") ){
                  if (this.readyState === 4 && this.status === 200) {
                      await checkcart();
                  }
              }
            }, false);
            open.apply(this, arguments);
        };
    {/if}


    window.onload = async function() {
        {if ($clerk_collect_cart == true) }
            {if ($clerk_cart_update == true && $isv16)}
                await checkcart();
            {/if}
            {if (!$isv16) }
                prestashop.on("updateCart", async function (e) {
                    await checkcart();
                });
            {/if}
        {/if}

        {if ($powerstep_enabled && $isv16)}
        //Handle powerstep
        prestashop.on("updateCart", function (e) {
            if (e.resp.success) {
                var product_id = e.resp.id_product;
                var product_id_attribute = e.resp.id_product_attribute;

                {if ($powerstep_type === 'page')}
                    window.location.replace("{$clerk_added_link}" + "?id_product=" + encodeURIComponent(product_id));
                {else}

                var clerkgetpower = new XMLHttpRequest();

                var data = new FormData();
                data.append('id_product', product_id);
                data.append('id_product_attribute', product_id_attribute);
                data.append('popup', '1');

                clerkgetpower.onreadystatechange = function() {
                    if (clerkgetpower.readyState == XMLHttpRequest.DONE) {   // XMLHttpRequest.DONE == 4
                        if (clerkgetpower.status == 200) {

                            res = clerkgetpower.responseText;
                            var count = 0;

                            setTimeout(function(){ //dont know why but its needed

                                var modals = document.querySelectorAll(".modal-body");
                                modals.forEach(function(el, index, array){
                                count = count+1;
                                    if (count === 1) {
                                        var modal = el;
                                        modal.innerHTML = modal.innerHTML + res;
                                    }
                                });

                                Clerk("content",".clerk-powerstep-templates > span");

                            }, 500);

                        }
                        else if (clerkgetpower.status == 400) {
                            console.log('There was an error 400');
                        }
                        else {
                            console.log('something else other than 200 was returned');
                        }
                    }
                };

                clerkgetpower.open("POST", "{$clerk_powerstep_link}", true);
                clerkgetpower.send(data);
                {/if}
            }
        });
        {/if}
    }
</script>
<!-- End of Clerk.io E-commerce Personalisation tool - www.clerk.io -->
{if ($exit_intent_enabled)}
    <style>
        .exit-intent {

            top: 10% !important;
            width: 80% !important;
            left: 5%;

        }
    </style>
{foreach $exit_intent_template as  $template}
    <span class="clerk exit-intent"
          data-template="@{$template}"
          data-exit-intent="true">
    </span>
{/foreach}
{/if}
