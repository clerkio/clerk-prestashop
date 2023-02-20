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
const clerkCategoryInjection = () => {
    const category_contents = {$Contents|json_encode};
    const category_id = {$CategoryId|json_encode};
    const category_heading = document.querySelector('#center_column');
    const exclude_duplicates_category = '{$ExcludeDuplicates}';
    let exclude_string_category = '';
    if(category_heading){
        const div_wrapper = document.createElement('div');
        div_wrapper.id = 'clerk_category_wrapper';
        category_heading.prepend(div_wrapper);
        category_contents.forEach((template, index)=>{
            const span = document.createElement('span');
            span.classList.add('clerk-manual');
            span.setAttribute('data-template', '@'+template);
            span.setAttribute('data-category', category_id);
            if(exclude_duplicates_category){
                if(index > 0){
                    span.dataset.excludeFrom = exclude_string_category;
                    exclude_string_category += ', ';
                }
                exclude_string_category += '.clerk_category_'+index;
                span.className += ' clerk_category_'+index;
            }
            document.querySelector('#clerk_category_wrapper').append(span);
        });
        Clerk('content', '.clerk-manual');
    }
}
document.addEventListener('DOMContentLoaded', clerkCategoryInjection);
</script>