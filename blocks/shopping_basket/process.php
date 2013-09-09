<?php

global $CFG, $USER;
require_once(dirname(__FILE__) . '/../../config.php');
include_once($CFG->dirroot . '/blocks/shopping_basket/cart/shopping_cart.php');
include_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');

$ponumber = required_param('po_number', PARAM_ALPHANUM);

// The visitor has clicked the PayPal checkout button
// Check that the prices of the basket items are (still?) valid since a 
// malicious user may have found a way to tamper with the session variable
$validPrices = validate_basket_items(ShoppingCart::get_contents());

// If the submitted prices are not valid, exit the script with an error message
if ($validPrices !== true) {
    die("Shopping basket prices have been changed -- cannot continue");
}

// Price validation is complete
// Send cart contents to PayPal using their upload method, for details see: http://j.mp/h7seqw
if ($validPrices === true) {
    // Store the order
    $order_id = add_order(ShoppingCart::get_basket_for_checkout(), $ponumber);
    
    if (empty($order_id)) {
        die ("Order ID not found");
    }

    // Process the order
    process_purchase($order_id);
            
    // Invalidate any discount voucher
    process_voucher($order_id);    
    
    redirect($CFG->wwwroot . '/blocks/shopping_basket/complete.php?orderid=' . $order_id);
}

