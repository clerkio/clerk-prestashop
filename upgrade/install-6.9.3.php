<?php
/**
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
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Migrate configuration keys that exceeded the 32-character ps_configuration.name limit.
 *
 * Uses direct SQL UPDATE to rename keys in place, which:
 *   - Preserves all values, shop associations, language entries, and timestamps
 *   - Bypasses Configuration::get/set scope issues (global vs shop-specific storage)
 *   - Handles both full keys (newer PS, VARCHAR 254) and truncated keys (older PS, VARCHAR 32)
 *   - Is idempotent (safe to run multiple times)
 */
function upgrade_module_6_9_3($object)
{
    $keyMap = array(
        'CLERK_LIVESEARCH_NUMBER_SUGGESTIONS'                  => 'CLERK_LS_NUM_SUGGESTIONS',
        'CLERK_LIVESEARCH_NUMBER_CATEGORIES'                   => 'CLERK_LS_NUM_CATEGORIES',
        'CLERK_LIVESEARCH_DROPDOWN_POSITION'                   => 'CLERK_LS_DROPDOWN_POSITION',
        'CLERK_DATASYNC_USE_REAL_TIME_UPDATES'                 => 'CLERK_DSYNC_REALTIME_UPDATES',
        'CLERK_DATASYNC_INCLUDE_OUT_OF_STOCK_PRODUCTS'         => 'CLERK_DSYNC_INCL_OOS_PRODUCTS',
        'CLERK_DATASYNC_INCLUDE_ONLY_LOCAL_STOCK'              => 'CLERK_DSYNC_ONLY_LOCAL_STOCK',
        'CLERK_DATASYNC_DISABLE_CUSTOMER_SYNC'                 => 'CLERK_DSYNC_DISABLE_CUST_SYNC',
        'CLERK_POWERSTEP_EXCLUDE_DUPLICATES'                   => 'CLERK_PWRSTEP_EXCL_DUPLICATES',
        'CLERK_CATEGORY_EXCLUDE_DUPLICATES'                    => 'CLERK_CAT_EXCL_DUPLICATES',
        'CLERK_LOGGING_DATASYNC_COLLECT_EMAILS'                => 'CLERK_LOG_DSYNC_COLLECT_EMAILS',
        'CLERK_LOGGING_DATASYNC_DISABLE_ORDER_SYNCHRONIZATION' => 'CLERK_LOG_DSYNC_NO_ORDER_SYNC',
    );

    $db = Db::getInstance();
    $prefix = _DB_PREFIX_;

    foreach ($keyMap as $oldKey => $newKey) {
        $truncatedKey = substr($oldKey, 0, 32);

        $newKeyExists = (int) $db->getValue(
            "SELECT COUNT(*) FROM `{$prefix}configuration` WHERE `name` = '" . pSQL($newKey) . "'"
        );

        if ($newKeyExists > 0) {
            // New key already exists (re-run or fresh install). Clean up any leftover old keys.
            $oldIds = $db->executeS(
                "SELECT `id_configuration` FROM `{$prefix}configuration`
                 WHERE `name` = '" . pSQL($oldKey) . "' OR `name` = '" . pSQL($truncatedKey) . "'"
            );
            if (!empty($oldIds)) {
                $ids = implode(',', array_column($oldIds, 'id_configuration'));
                $db->execute("DELETE FROM `{$prefix}configuration_lang` WHERE `id_configuration` IN ({$ids})");
                $db->execute("DELETE FROM `{$prefix}configuration` WHERE `id_configuration` IN ({$ids})");
            }
            continue;
        }

        // Try renaming the full old key in place
        $db->execute(
            "UPDATE `{$prefix}configuration` SET `name` = '" . pSQL($newKey) . "'
             WHERE `name` = '" . pSQL($oldKey) . "'"
        );

        if ($db->Affected_Rows() == 0) {
            // Full key not found — try the truncated variant (older PS with VARCHAR 32)
            $db->execute(
                "UPDATE `{$prefix}configuration` SET `name` = '" . pSQL($newKey) . "'
                 WHERE `name` = '" . pSQL($truncatedKey) . "'"
            );
        } else {
            // Full key was renamed. Clean up any orphaned truncated key.
            $orphanIds = $db->executeS(
                "SELECT `id_configuration` FROM `{$prefix}configuration`
                 WHERE `name` = '" . pSQL($truncatedKey) . "'"
            );
            if (!empty($orphanIds)) {
                $ids = implode(',', array_column($orphanIds, 'id_configuration'));
                $db->execute("DELETE FROM `{$prefix}configuration_lang` WHERE `id_configuration` IN ({$ids})");
                $db->execute("DELETE FROM `{$prefix}configuration` WHERE `id_configuration` IN ({$ids})");
            }
        }
    }

    return true;
}
