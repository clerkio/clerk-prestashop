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
            e.src=(d.location.protocol=='https:'?'https':'http')+'://cdn.clerk.io/clerk.js';
            var s=d.getElementsByTagName('script')[0];s.parentNode.insertBefore(e,s);
            w.__clerk_q=w.__clerk_q||[];w.Clerk=w.Clerk|| function(){ w.__clerk_q.push(arguments) };
        })(window,document);
    })();

    Clerk('config', {
        key: '{$clerk_public_key}',
        collect_email: {$clerk_datasync_collect_emails},
        language: '{$language}'

    });
   

</script>

<!-- End of Clerk.io E-commerce Personalisation tool - www.clerk.io -->
