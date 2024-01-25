<?php
class ContextHelper {

    public static function getAllLanguages(){
        return Language::getLanguages(true);
    }

    public static function getAllShops(){
        return Shop::getShops(true);
    }

    public static function getAllContexts(){
        $all_contexts = [];
        $all_shops = Shop::getShops(true);
        $all_languages = Language::getLanguages(true);
        foreach ($all_shops as $shop) {
            $shop_id = $shop['id_shop'];
            foreach ($all_languages as $language) {
                if($language['id_shop'] !== $shop_id){
                    continue;
                }
                $all_contexts[] = ['shop_id' => $shop_id, 'lang_id' => $language['id_lang']];
            }
        }
        return $all_contexts;
    }

}