<?php

global $CFG;
require_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');
require_once($CFG->dirroot . '/blocks/shopping_basket/cart/gateway/payment_gateway.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * SagePay Payment Gateway
 * Contains methods to send/recieve transaction details to/from SagePay
 *
 * @copyright Learning Pool
 * @author Kevin Corry
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package shopping_basket
 */
class SagePayGateway extends PaymentGateway {

    public function __construct() {
        parent::__construct();
        // List fields you wish to translate here, to ensure incoming fields
        // match our schema. Format: incoming field => schema field
        $this->field_mapping = array(
            'VendorTxCode' => 'business_key',
            'Status' => 'payment_status',
            'StatusDetail' => 'pending_reason',
            'VPSTxId' => 'txn_id',
            'SecurityKey' => 'txn_key',
            'TxType' => 'txn_type',
        );
    }

    /**
     * Build our query string to send to sagepay
     * @global type $CFG
     * @global type $USER
     * @param ShoppingCart $basket
     * @param string $business_key - *our* unique identifier for this order
     * @return string $querystr
     */
    public function build_query($basket) {
        global $CFG, $USER;

        $querystr = '';
        $billingdetails = shopping_basket_get_billing_details();
        
        $address1 = '';
        $city = '';
        $postcode = '';
        
        if (isset($billingdetails->address)) {
            $address1 = isset($billingdetails->address->address1->value) ? $billingdetails->address->address1->value : '';
            $city = isset($billingdetails->address->city->value) ? $billingdetails->address->city->value : '';
            $postcode = isset($billingdetails->address->postcode->value) ? $billingdetails->address->postcode->value : '';
        }
        
        $site = get_site();
        
        // Add customer detail fields
        $fields = array(
            'VPSProtocol' => "2.23",
            'TxType' => "PAYMENT",
            'Vendor' => $this->business,
            'VendorTxCode' => $basket->business_key,
            'Amount' => self::round_preserve_zero($basket->total),
            'Currency' => $this->currencycode,
            'Description' => "Your purchase from ". $site->fullname,
            'CustomerEMail' => $USER->email,
            // SagePay doesn't seem to support custom attributes, so we'll
            // add our custom data here as a get var: orderid-userid
            'NotificationURL' => $CFG->wwwroot . '/blocks/shopping_basket/ipn.php?custom=' . urlencode($basket->order_id . '-' . $USER->id),
            'BillingSurname' => $USER->lastname,
            'BillingFirstnames' => $USER->firstname,
            'BillingAddress1' => $address1,
            'BillingCity' => $city,
            'BillingPostCode' => $postcode,
            'BillingCountry' => $USER->country,
            'DeliverySurname' => $USER->lastname,
            'DeliveryFirstnames' => $USER->firstname,
            'DeliveryAddress1' => $address1,
            'DeliveryCity' => $city,
            'DeliveryPostCode' => $postcode,
            'DeliveryCountry' => $USER->country,
        );

        // Build our query string
        foreach ($fields as $key => $val) {
            $querystr .= '&' . $key . '=' . urlencode($val);
        }

        $global_taxrate = 0;
        if ($this->taxratio && $this->taxratio > 0) {
            $global_taxrate = $this->taxratio * 100;
        }

        // Add basket contents
        // Number of detail rows = number of items + sub-total, tax and total rows + voucher row (if needed)
        $hasvoucher = $this->discountsenabled && !empty($basket->vouchercode) ? 1 : 0;
        $num_detail_rows = count($basket->items) + 3 + $hasvoucher;
        $basketstr = '&basket=' . $num_detail_rows;

        foreach ($basket->items as $item) {
            $price = $item->price - $item->discount;
            $taxrate = $this->discountsenabled ? $global_taxrate : $item->itemtax;
            // If a voucher is applied, we hide the item tax,
            // as tax will be applied basket-wide, not per-item
            $itemtax = ($hasvoucher == 1) ? 0 : round($price * ($taxrate / 100), 2);
            $itemtotal = $price + $itemtax;
            $linetotal = ($hasvoucher == 1) ? $item->linetotal : $item->linetotal + $item->linetax;
            // Name : Quantity : Item value : Item Tax : Item Total : Line Total
            $basketstr .= ':' . urlencode($item->name);
            $basketstr .= ':' . urlencode($item->quantity);
            $basketstr .= ':' . self::request_prepare_number($price);
            $basketstr .= ':' . self::request_prepare_number($itemtax);
            $basketstr .= ':' . self::request_prepare_number($itemtotal);
            $basketstr .= ':' . self::request_prepare_number($linetotal);
        }

        if ($hasvoucher == 1) {
            // Voucher row (::::: signify blank cells)
            $basketstr .= ':' . urlencode(get_string('discount', 'block_shopping_basket')) . ':::::-' . self::request_prepare_number($basket->discountamount);
        }

        // Sub-total row (::::: signify blank cells)
        $basketstr .= ':' . urlencode(get_string('subtotal', 'block_shopping_basket')) . ':::::' . self::request_prepare_number($basket->subtotal);

        // Tax row (::::: signify blank cells)
        $basketstr .= ':' . urlencode(get_string('tax', 'block_shopping_basket')) . ':::::' . self::request_prepare_number($basket->totaltax);

        // Total row (::::: signify blank cells)
        $basketstr .= ':' . urlencode(get_string('total', 'block_shopping_basket')) . ':::::' . self::request_prepare_number($basket->total);

        $querystr = ltrim($querystr, '&') . $basketstr;

        return $querystr;
    }

    /**
     * Fire off a request to sagepay
     * @param ShoppingCart $basket
     */
    public function send_request($basket) {

        // Unique business_key generation - max 40 chars for sagepay:
        // vendor (max 15 chars) + underscore (1 char) + unique id (13 chars) + random 0-9999 (max 4 chars) = max 33 chars
        $basket->business_key = uniqid($this->business.'_') . rand(0, 9999);

        $query = $this->build_query($basket);

        if (!$this->sandboxmode) {
            $url = 'https://live.sagepay.com/gateway/service/vspserver-register.vsp';
        } else {
            // Simulator - use for DEV
            //$url = 'https://test.sagepay.com/Simulator/VSPServerGateway.asp?Service=VendorRegisterTx';
            // Test - use for QA (preview, etc;)
            $url = 'https://test.sagepay.com/gateway/service/vspserver-register.vsp';
        }

        if ($this->business) {
            // Register the purchase with Sage Pay
            $arrResponse = $this->request_post($url, $query);

            // OK – Process executed without error
            // MALFORMED – Input message was missing fields or badly formatted
            // INVALID – POST info was invalid: incorrect vendor name/currency..
            // ERROR – A problem occurred at Sage Pay
            $status = $arrResponse["Status"];

            // Caters for both OK and OK REPEATED if the same transaction is registered twice
            if (substr($status, 0, 2) == "OK") {
                // Extract the VPSTxId (Sage Pay's unique reference for this transaction),
                // the SecurityKey (used to validate the call back from Sage Pay later) and the NextURL
                // (the URL to which the customer's browser must be redirected to enable them to pay)
                $vps_txn_id = $arrResponse["VPSTxId"];
                $vps_txn_key = $arrResponse["SecurityKey"];
                $next_url = $arrResponse["NextURL"];
                
                // Store the security key against our order
                $order_updates = new stdClass();
                $order_updates->id = $basket->order_id;
                $order_updates->business_key = $basket->business_key;
                $order_updates->txn_id = $vps_txn_id;
                $order_updates->txn_key = $vps_txn_key;
                update_order($order_updates);

                // Finally, redirect the customer to the NextURL
                ob_flush();
                redirect($next_url);
                exit();
            } else {
                $order_updates = new stdClass();
                $order_updates->id = $basket->order_id;
                $order_updates->payment_status = PAYMENT_STATUS_ERROR;
                update_order($order_updates);
                add_to_payment_error_log($basket->order_id, '', $arrResponse['StatusDetail']);
                // Something went wrong - the transaction wasn't registered
                redirect($this->failureurl . '?orderid=' . $basket->order_id);
            }
        } else {
            die('Sage Pay Vendor not found');
        }
    }

    /**
     * Handle a payment notification message from sagepay
     */
    public function process_response($data) {
        global $CFG;

        // Information is POSTed to this page from SagePay.
        // The POST will ALWAYS contain VendorTxCode, VPSTxID and Status fields
        // Define end of line character used to correctly format response to Sage Pay Server
        $eoln = chr(13) . chr(10);

        // Retrieve our txn_key from our database - this will enable us to
        // validate the POST to ensure it came from SagePay
        $order = get_order($data->orderid);

        if (strlen($order->txn_key) == 0) {
            // We cannot find this order in the database - To protect the
            // customer, send back an INVALID response. This will prevent
            // SagePay from settling any authorised transactions
            ob_flush();
            header("Content-type: text/html");
            echo "Status=INVALID" . $eoln;
            echo "RedirectURL=" . $CFG->wwwroot . "/blocks/shopping_basket/complete.php?reasonCode=001" . $eoln;
            echo "StatusDetail=Unable to find the transaction in our database." . $eoln;
            exit();
        } else {
            // We've found the order in the database - now we create a signature
            // to compare with the contents of the VPSSignature field in the POST
            $mysignature = $this->generate_signature($data, $order->txn_key);

            // Compare our MD5 Hash signature with that from SagePay
            if ($mysignature !== $data->VPSSignature) {
                // If the signatures DON'T match, send back a Status of INVALID
                ob_flush();
                header("Content-type: text/plain");
                echo "Status=INVALID" . $eoln;
                echo "RedirectURL=" . $CFG->wwwroot . "/blocks/shopping_basket/complete.php?reasonCode=002" . $eoln;
                echo "StatusDetail=Cannot match the MD5 Hash. Order might be tampered with." . $eoln;
                exit();
            } else {
                // It matched!
                // Update order payment status and let SagePay know we heard them
                $updated_order = new stdClass();
                $updated_order->id = $data->orderid;
                $updated_order->pending_reason = '';
                
                // Translate payment statuses to match ours
                switch ($data->payment_status) {
                    case 'OK':
                        $data->payment_status = PAYMENT_STATUS_COMPLETED;
                        break;
                    case 'NOTAUTHED':
                        $data->payment_status = PAYMENT_STATUS_DECLINED;
                        $updated_order->pending_reason = 'The transaction was not authorised by the bank';
                        break;
                    case 'ABORT':
                        $data->payment_status = PAYMENT_STATUS_ABORTED;
                        $updated_order->pending_reason = 'The customer clicked Cancel on the payment pages, or the transaction was timed out due to customer inactivity';
                        break;
                    case 'REJECTED':
                        $data->payment_status = PAYMENT_STATUS_REJECTED;
                        $updated_order->pending_reason = 'The transaction was failed by your 3D-Secure or AVS/CV2 rule-bases';
                        break;
                    case 'AUTHENTICATED':
                        $data->payment_status = PAYMENT_STATUS_AUTHENTICATED;
                        $updated_order->pending_reason = 'The transaction was successfully 3D-Secure Authenticated and can now be Authorised';
                        break;
                    case 'REGISTERED':
                        $data->payment_status = PAYMENT_STATUS_REGISTERED;
                        $updated_order->pending_reason = 'The transaction was could not be 3D-Secure Authenticated, but has been registered to be Authorised';
                        break;
                    case 'ERROR':
                        $data->payment_status = PAYMENT_STATUS_ERROR;
                        $updated_order->pending_reason = 'There was an error during the payment process';
                        break;
                    default:
                        $data->payment_status = PAYMENT_STATUS_UNKNOWN;
                        $updated_order->pending_reason = 'An unknown status was returned from SagePay';
                        break;
                }

                $updated_order->payment_status = $data->payment_status;
                
                // Run our update
                update_order($updated_order);

                $validation = $this->validate_purchase($data);

                if (!$validation->valid) {
                    // Something wasn't right...
                    add_to_payment_error_log($data->orderid, '', $validation->reason);
                } else {
                    process_purchase($updated_order->id);

                    process_voucher($updated_order->id);
                }

                ob_flush();
                header("Content-type: text/plain");

                // Always send a Status of OK if we've read everything correctly.
                // Only reply with INVALID for messages with a Status of ERROR
                if ($data->payment_status == PAYMENT_STATUS_ERROR) {
                    echo "Status=INVALID" . $eoln;
                } else {
                    echo "Status=OK" . $eoln;
                }

                // Redirect the customer to the order complete page
                $strRedirectPage = $CFG->wwwroot . "/blocks/shopping_basket/complete.php?orderid=" . $data->orderid;
                
                echo "RedirectURL=" . $strRedirectPage . $eoln;
                // No need to send a StatusDetail, since we're happy with the POST
                exit();
            }
        }
    }

    public function require_user_details() {
        return true;
    }

    public function pay_now_html($enabled = true) {
        global $CFG;

        $attributes = array(
            'id' => 'sagepay_checkout',
            'type' => 'submit',
            'title' => get_string('paynow', 'block_shopping_basket'),
            'value' => get_string('paynow', 'block_shopping_basket'),
            'name' => 'submit');

        if (!$enabled) {
            $attributes['disabled'] = 'disabled';
        }

        return html_writer::empty_tag('input', $attributes)
                . html_writer::empty_tag('br')
                . html_writer::empty_tag('br')
                . html_writer::empty_tag('img', array('src' => $CFG->wwwroot . '/blocks/shopping_basket/pix/SagePay_Logo.gif'));
    }

    /**
     * Utility function to send a cURL request
     * @param type $url
     * @param type $data
     * @return type
     */
    function request_post($url, $data) {
        // Set a one-minute timeout for this script
        set_time_limit(60);

        // Initialise output variable
        $output = array();

        // Open the cURL session
        $curlSession = curl_init();

        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_HEADER, 0);
        curl_setopt($curlSession, CURLOPT_POST, 1);
        curl_setopt($curlSession, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlSession, CURLOPT_TIMEOUT, 30);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, 0);

        //Send the request and store the result in an array
        $rawresponse = curl_exec($curlSession);

        //Split response into name=value pairs
        $response = split(chr(10), $rawresponse);

        // Check that a connection was made
        if (curl_error($curlSession)) {
            // If it wasn't...
            $output['Status'] = "FAIL";
            $output['StatusDetail'] = curl_error($curlSession);
        }

        // Close the cURL session
        curl_close($curlSession);

        // Tokenise the response
        for ($i = 0; $i < count($response); $i++) {
            // Find position of first "=" character
            $splitAt = strpos($response[$i], "=");
            // Create an associative (hash) array with key/value pairs ('trim' strips excess whitespace)
            $output[trim(substr($response[$i], 0, $splitAt))] = trim(substr($response[$i], ($splitAt + 1)));
        }

        // Return the output
        return $output;
    }

    /**
     * Check SagePay Purchase
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

        if (!$this->valid_txn_id($ipn_data)) {
            $return->valid = false;
            $return->reason = 'txn_id has been used before';
            return $return;
        }

        // Must be valid
        $return->valid = true;
        $return->reason = '';
        return $return;
    }

    /**
     * Generate a signature to match against an incoming SagePay request.
     * This will enable us to verify it's a legitimate incoming message
     * @param type $data
     * @param type $txn_key
     * @return type
     */
    function generate_signature($data, $txn_key) {
        // Retrieve signature fields from POST.
        // Decode fields, setting unset as blank
        $sig_fields = array(
            'TxAuthNo' => '',
            'AVSCV2' => '',
            'txn_key' => '',
            'AddressResult' => '',
            'PostCodeResult' => '',
            'CV2Result' => '',
            'GiftAid' => '',
            '3DSecureStatus' => '',
            'CAVV' => '',
            'AddressStatus' => '',
            'PayerStatus' => '',
            'CardType' => '',
            'Last4Digits' => '',
        );

        foreach ($sig_fields as $field => $val) {
            if (isset($data->$field)) {
                $sig_fields[$field] = urldecode($data->$field);
            }
        }

        // Create a signature to compare with the contents of the
        // VPSSignature field in the POST. NOTE: Order is crucial
        $message = $data->txn_id . $data->business_key . $data->payment_status . $sig_fields['TxAuthNo'];
        $message.= strtolower($this->business) . $sig_fields['AVSCV2'] . $txn_key;
        $message.= $sig_fields['AddressResult'] . $sig_fields['PostCodeResult'];
        $message.= $sig_fields['CV2Result'] . $sig_fields['GiftAid'];
        $message.= $sig_fields['3DSecureStatus'] . $sig_fields['CAVV'] . $sig_fields['AddressStatus'];
        $message.= $sig_fields['PayerStatus'] . $sig_fields['CardType'] . $sig_fields['Last4Digits'];

        return strtoupper(md5($message));
    }
    
    /**
     * Prepare a number for a request to SagePay
     * (urlencode and round, preserving zeros)
     * @param type $value
     * @return string
     */
    public static function request_prepare_number($value) {
        return urlencode(self::round_preserve_zero($value));
    }
    
    /**
     * Rounds a number to 2 dp, preserving zeros
     * @param float $value - number to format
     * @param int $decimals - number of decimal places to format to
     * @param string $dec_sep - decimal symbol
     * @param string $thou_sep - thousand separator symbol
     * @return float
     */
    public static function round_preserve_zero($value, $decimals=2, $dec_sep='.', $thou_sep='') {
        return number_format($value, $decimals, $dec_sep, $thou_sep);
    }

}
