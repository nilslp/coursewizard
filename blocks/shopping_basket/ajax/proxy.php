<?php

/**
 * This file serves as a proxy for AJAX requests 
 */
require_once '../../../config.php';
global $CFG;
require_once($CFG->dirroot . '/blocks/shopping_basket/cart/shopping_cart.php');
require_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');

$sesskey = required_param('sesskey', PARAM_TEXT);
$ischeckout = optional_param('checkout', 0, PARAM_BOOL);
$operation = optional_param('op', '', PARAM_TEXT);
$productid = optional_param('product', 0, PARAM_INT);
$vouchercode = optional_param('vouchercode', '', PARAM_TEXT);

if (!confirm_sesskey($sesskey)) {
    die;
}

switch ($operation) {
    case 'get_product_markup':
        $product = get_product($productid);
  //      $product_html = generate_product_html($product);

        $product_html = generate_product_html($product);
        $html = html_writer::tag('textarea', htmlentities($product_html), array('id' => 'html_textarea', 'style' => 'width: 100%, height: 100%'));
        break;
    
    case 'apply_voucher':
        ShoppingCart::apply_discount_voucher($vouchercode);
        $html = ShoppingCart::display_basket($ischeckout);
        break;
    
    case 'remove_voucher':
        ShoppingCart::remove_discount_voucher();
        $html = ShoppingCart::display_basket($ischeckout);
        break;
    
    default:
        $html = ShoppingCart::display_basket($ischeckout);
}

// Return the shopping cart HTML
header('Content-type: application/html');
echo $html;
die;

