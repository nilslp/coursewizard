<?php
function xmldb_block_shopping_basket_install() {
    global $CFG, $DB, $OUTPUT;

    $result = true;
   
    if ($result) {
    	// Default the currency
        set_config('currency', 'GBP', 'block_shopping_basket');
        
        // Expiration reminder setting defaults
        set_config('enableexpiryemail', 1, 'block_shopping_basket');
        set_config('expirythreshold', 432000, 'block_shopping_basket');
    }

    set_config('lastcron', 0, 'block_shopping_basket');
    
    set_config('acceptpo', 0, 'block_shopping_basket');
    
    return $result;
}
