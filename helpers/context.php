<?php
/**
 *  @author Clerk.io
 *  @copyright Copyright (c) 2017 Clerk.io
 *
 *  @license MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
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
 */

class ClerkContextHelper {
    /**
     * @param $context
     * @return int
     */
    public function setShopId($context = null ): int
    {
        if($_SESSION['shop_id']){
            return (int) $_SESSION['shop_id'];
        }
        if(ToolsCore::getValue('clerk_shop_select')){
            return (int) ToolsCore::getValue('clerk_shop_select');
        }
        if(is_object($context)){
            return (int) $context->shop->id;
        }
        return 0;
    }

    /**
     * @param $context
     * @return int
     */
    public function setLanguageId($context = null ): int
    {
        if($_SESSION['language_id']){
            return (int) $_SESSION['language_id'];
        }
        if(ToolsCore::getValue('clerk_language_select')){
            return (int) ToolsCore::getValue('clerk_language_select');
        }
        if(is_object($context)){
            return (int) $context->language->id;
        }
        return 0;
    }
}
