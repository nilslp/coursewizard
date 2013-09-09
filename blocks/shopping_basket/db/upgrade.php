<?php

/**
* This file keeps track of upgrades to
* the block_shopping_basket module
*
* Sometimes, changes between versions involve
* alterations to database structures and other
* major things that may break installations.
*
* The upgrade function in this file will attempt
* to perform all the necessary actions to upgrade
* your older installtion to the current version.
*
* If there's something it cannot do itself, it
* will tell you what you need to do.
*
* The commands in here will all be database-neutral,
* using the functions defined in lib/ddllib.php
*/

function xmldb_block_shopping_basket_upgrade($oldversion=0) {
    global $CFG, $DB, $OUTPUT;
    $result = true;
    
    if ($result && $oldversion < 2013071800) {
        // Rename shopping_basket_product_category to shopping_basket_prod_cat
        if ($DB->get_manager()->table_exists('shopping_basket_product_category')) {
            /// To rename one table:
            $DB->get_manager()->rename_table('shopping_basket_product_category', 'shopping_basket_prod_cat', true, true);
        }
        
        // Rename shopping_basket_product_course to shopping_basket_prod_course
        if ($DB->get_manager()->table_exists('shopping_basket_product_course')) {
            /// To rename one table:
            $DB->get_manager()->rename_table('shopping_basket_product_course', 'shopping_basket_prod_course', true, true);
        }
        
        // Rename shopping_basket_product_discount to shopping_basket_prod_disc
        if ($DB->get_manager()->table_exists('shopping_basket_product_discount')) {
            /// To rename one table:
            $DB->get_manager()->rename_table('shopping_basket_product_discount', 'shopping_basket_prod_disc', true, true);
        }
    }
    
    return $result;
}
