<?php

/**
 *
 * @copyright Learning Pool
 * @author Kevin Corry
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package shopping_basket
 */
global $CFG, $DB;
require("../../config.php");
require_once("lib.php");
require_once($CFG->libdir . '/eventslib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/filelib.php');
include_once($CFG->dirroot . '/blocks/shopping_basket/cart/payment_processor.php');

// Keep out casual intruders
if (empty($_POST)) {
    print_error("Sorry, you can not use the script that way.");
}

// Read all the incoming data from the Payment Provider.

$data = new stdClass();

foreach ($_POST as $key => $value) {
    $data->$key = $value;
}

// Order ID and User ID are stored in the custom property
// PayPal - passed via post
// Sagepay - passed via get
$custom_data = isset($_REQUEST['custom']) ? $_REQUEST['custom'] : false;
if ($custom_data) {
    $custom = explode('-', $custom_data);
    $data->orderid = (int) $custom[0];
    $data->userid = (int) $custom[1];
    $data->timemodified = time();

    // Fetch the order and create a payment processor, based on payment provider
    $order = get_order($data->orderid);
    $processor = new PaymentProcessor($order->payment_provider);
    $processor->process_response($data);
} else {
    print_error("Sorry, you can not use the script that way.");
}