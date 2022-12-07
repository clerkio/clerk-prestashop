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
let lastProductId = null;
document.addEventListener('DOMContentLoaded', function() {
    XMLHttpRequest.prototype.send = function () {
        this.addEventListener('load', function () {
            if (this.status == 200) {
                try {
                    rsp = JSON.parse(this.responseText);
                    add_event = rsp.hasOwnProperty('productTotal');
                    if (add_event) {
                        lastProductId = rsp.products[rsp.products.length - 1].id;
                        clerkPowerstepInjection(lastProductId);
                    }
                } catch (e) {
                    console.log('Could not check Response Json');
                }
            }
        });
        return send.apply(this, arguments);
    }
});
const clerkPowerstepInjection = (id) => {
    const powerstep_templates = {$Contents|json_encode};
    const powerstep_products = id;
    const modalContainer = document.querySelector('#layer_cart .crossseling');
    const exclude_duplicates_powerstep = '{$ExcludeDuplicates}';
    let exclude_string_powerstep = '';
    if(modalContainer){
        modalContainer.innerHTML = '';
        powerstep_templates.forEach((template, index)=>{
            const span = document.createElement('span');
            span.classList.add('clerk_powerstep');
            if(exclude_duplicates_powerstep){
                if(index > 0){
                    span.dataset.excludeFrom = exclude_string_powerstep;
                    exclude_string_powerstep += ', ';
                }
                exclude_string_powerstep += '.clerk_powerstep_'+index;
                span.className += ' clerk_powerstep_'+index;
            }
            span.setAttribute('data-template', '@'+template);
            span.setAttribute('data-products', '['+powerstep_products+']');
            modalContainer.append(span);

        });
        Clerk('content', '.clerk_powerstep');
    }
}
</script>
