<?php
class Order extends OrderCore
{
	/**
	 * Get order with start and limit
	 * @param  [type]       $start   [description]
	 * @param  [type]       $limit   [description]
	 * @param  Context|null $context [description]
	 * @return [type]                [description]
	 */
    public static function getOrdersWithInformations($start= null, $limit = null, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }
        
        $sql = 'SELECT *, (
                    SELECT osl.`name`
                    FROM `'._DB_PREFIX_.'order_state_lang` osl
                    WHERE osl.`id_order_state` = o.`current_state`
                    AND osl.`id_lang` = '.(int)$context->language->id.'
                    LIMIT 1
                ) AS `state_name`, o.`date_add` AS `date_add`, o.`date_upd` AS `date_upd`
                FROM `'._DB_PREFIX_.'orders` o
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = o.`id_customer`)
                WHERE 1
                    '.Shop::addSqlRestriction(false, 'o').'
                ORDER BY o.`date_add` DESC
                '.((int)$limit ? 'LIMIT '.(int)$start.','.(int)$limit : '');
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }
}