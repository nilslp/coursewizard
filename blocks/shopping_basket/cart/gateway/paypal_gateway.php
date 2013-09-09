<?php

global $CFG;
require_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');
require_once($CFG->dirroot . '/blocks/shopping_basket/cart/gateway/payment_gateway.php');

/**
 * PayPal Payment Gateway
 * Contains methods to send/recieve transaction details to/from PayPal
 *
 * @copyright Learning Pool
 * @author Kevin Corry
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package shopping_basket
 */
class PayPalGateway extends PaymentGateway {

    public function __construct() {
        parent::__construct();
        // List fields you wish to translate here, to ensure incoming fields
        // match our schema. Format: incoming field => schema field
        // NOTE: PayPal fields currently match our shema, so no translation needed
        $this->field_mapping = array();
    }

    /**
     * Build up our query string which will be sent to PayPal
     * @global type $CFG
     * @global type $USER
     * @param ShoppingCart $basket
     * @return string $querystr
     */
    public function build_query($basket) {
        global $CFG, $USER;

        $querystr = '?cmd=_cart';
        $querystr .= '&upload=1';
        $querystr .= '&charset=utf-8';
        $querystr .= '&currency_code=' . urlencode(ltrim(rtrim($this->currencycode)));
        $querystr .= '&business=' . urlencode(ltrim(rtrim($this->business)));
        $querystr .= '&return=' . urlencode($CFG->wwwroot . '/blocks/shopping_basket/complete.php?orderid=' . $basket->order_id);
        $querystr .= '&notify_url=' . urlencode($CFG->wwwroot . '/blocks/shopping_basket/ipn.php');
        $querystr .= '&custom=' . urlencode($basket->order_id . '-' . $USER->id);

        if ($this->discountsenabled && !empty($basket->vouchercode)) {
            // Apply the discount at the cart level
            $querystr .= '&discount_amount_cart=' . $basket->discountamount;

            // Apply the tax at the cart level
            $querystr .= '&tax_cart=' . urlencode($basket->totaltax);
        }

        $global_taxrate = 0;
        if ($this->taxratio && $this->taxratio > 0) {
            $global_taxrate = $this->taxratio * 100;
        }

        $count = 1;
        foreach ($basket->items as $item) {
            $querystr .= '&item_number_' . $count . '=' . urlencode($item->id);
            $querystr .= '&item_name_' . $count . '=' . urlencode($item->name);
            $querystr .= '&amount_' . $count . '=' . urlencode($item->price - $item->discount);
            $querystr .= '&tax_rate_' . $count . '=' . ($this->discountsenabled ? $global_taxrate : urlencode($item->itemtax));
            $querystr .= '&quantity_' . $count . '=' . urlencode($item->quantity);
            $count++;
        }

        return $querystr;
    }

    /**
     * Fire off a request to PayPal
     * @param ShoppingCart $basket
     */
    public function send_request($basket) {
        $query = $this->build_query($basket);
        if ($this->business) {
            // Send the visitor to PayPal
            @header('Location: https://www.' . ($this->sandboxmode ? 'sandbox.' : '') . 'paypal.com/cgi-bin/webscr' . $query);
        } else {
            die('PayPalID not found');
        }
    }

    /**
     * Handle PayPal Instant Payment Notifications
     */
    public function process_response($data) {
        global $DB;
        $req = 'cmd=_notify-validate';

        foreach ($data as $key => $value) {
            $req .= "&$key=" . urlencode($value);
        }
        
        // Get the user record
        if (!$user = $DB->get_record("user", array("id" => $data->userid))) {
            add_to_payment_error_log($data->orderid, $req, sprintf("Error: User (%d) not found", $data->userid));
            die;
        }

        // If a guest ordered the product, we need to check if they
        // have an account - otherwise we'll create one for them
        if ($user->username == 'guest') {

            $user = check_order_user($data);

            if ($user) {
                $data->userid = $user->id;
            } else {
                add_to_payment_error_log($data->orderid, $req, 'Error: Could not replace order Guest User');
                die;
            }
        }

        // Get the order
        if (!$order = $DB->get_record("shopping_basket_order", array("id" => $data->orderid))) {
            add_to_payment_error_log($data->orderid, $req, sprintf("Error: Order (%d) not found", $data->orderid));
            die;
        }

        /// Open a connection back to PayPal to validate the data
        $c = new curl();
        $options = array(
            'returntransfer' => true,
            'httpheader' => array('application/x-www-form-urlencoded'),
            'timeout' => 30,
        );

        // Check for Sandbox Mode
        $paypaladdr = (get_config('block_shopping_basket', 'sandboxmode')) ? 'www.sandbox.paypal.com' : 'www.paypal.com';
        $location = "https://$paypaladdr/cgi-bin/webscr";

        /// Connection is OK, so now we post the data to validate it
        $result = $c->post($location, $req, $options);

        if (!$result) {  /// Could not connect to PayPal - FAIL
            add_to_payment_error_log($data->orderid, $req, "Error: could not access " . $location);
            echo "<p>Error: could not access paypal.com</p>";
            die;
        }

        /// Now read the response and check if everything is OK.
        if (strlen($result) > 0) {

            if (strcmp($result, "VERIFIED") == 0) {          // VALID PAYMENT!
                $updated_order = new stdClass();
                $updated_order->id = $data->orderid;

                // Translate payment statuses to match ours
                switch ($data->payment_status) {
                    case 'Completed':
                        $data->payment_status = PAYMENT_STATUS_COMPLETED;
                        break;
                    case 'Pending':
                        $data->payment_status = PAYMENT_STATUS_PENDING;
                        break;
                    case 'Failed':
                        $data->payment_status = PAYMENT_STATUS_DECLINED;
                        break;
                    default:
                        $data->payment_status = PAYMENT_STATUS_UNKNOWN;
                        break;
                }
                
                // Add IPN values
                foreach ($data as $data_key => $data_val) {
                    $updated_order->$data_key = $data_val;
                }
                
                // Update Order details with IPN info
                update_order($updated_order);

                $validation = $this->validate_purchase($data);

                if (!$validation->valid) {
                    // Something wasn't right...
                    add_to_payment_error_log($data->orderid, $req, $validation->reason);
                } else {
                    process_purchase($updated_order->id);

                    process_voucher($updated_order->id);
                }
            } else if (strcmp($result, "INVALID") == 0) { // ERROR
                add_to_payment_error_log($data->orderid, $req, 'Error: Received an invalid payment notification (fake payment?)');
                die;
            }
        }
    }

    public function require_user_details() {
        return false;
    }

    public function pay_now_html($enabled = true) {
        $attributes = array('id' => 'paypal_checkout',
            'type' => 'image',
            'title' => get_string('checkoutwithpaypal', 'block_shopping_basket'),
            'name' => 'submit',
            'src' => "https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif");

        if (!$enabled) {
            $attributes['disabled'] = 'disabled';
        }

        return html_writer::empty_tag('input', $attributes);
    }

    /**
     * Check PayPal Purchase
     * @param stdClass $ipn_data
     * @return stdClass $return
     */
    function validate_purchase($ipn_data) {

        $return = new stdClass();
        $return->valid = false;
        $return->reason = '';

        $order = get_order($ipn_data->orderid);

        if (!$order) {
            $return->valid = false;
            $return->reason = 'Order doesn\'t exist';
            return $return;
        }

        if (!$this->payment_received($ipn_data)) {
            $return->valid = false;
            $return->reason = 'Payment not received';
            return $return;
        }

        if (!$this->valid_order_details($ipn_data, $order)) {
            $return->valid = false;
            $return->reason = 'Order details do not match';
            return $return;
        }

        if (!$this->valid_txn_id($ipn_data)) {
            $return->valid = false;
            $return->reason = 'txn_id has been used before';
            return $return;
        }

        if (!$this->valid_receiver_email($ipn_data)) {
            $return->valid = false;
            $return->reason = 'Receiver email does not match config Pay Pal ID';
            return $return;
        }

        // Must be valid
        $return->valid = true;
        $return->reason = '';
        return $return;
    }

    /**
     * Check IPN details against Order
     * @param stdClass $data
     * @return boolean $payment_received
     */
    function valid_order_details($data, $order) {
        $valid = false;

        $valid_cost = ($data->mc_gross == $order->total);
        $valid_currency = ($data->mc_currency == $order->currency);

        if ($valid_cost && $valid_currency) {
            $valid = true;
        }

        return $valid;
    }

    /**
     * Ensure IPN Receiver Email matches our config
     * @param stdClass $data
     * @return boolean $valid
     */
    function valid_receiver_email($data) {
        return ($data->receiver_email == get_config('block_shopping_basket', 'vendoridentifier'));
    }

}
