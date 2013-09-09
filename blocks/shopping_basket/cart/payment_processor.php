<?php
global $CFG;
include_once($CFG->dirroot . '/blocks/shopping_basket/cart/gateway/paypal_gateway.php');
include_once($CFG->dirroot . '/blocks/shopping_basket/cart/gateway/sagepay_gateway.php');

/**
 * Payment Processor
 * Contains methods to implement payment gateways, based on the payment provider
 * 
 * @copyright Learning Pool
 * @author Kevin Corry
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package shopping_basket
 */
class PaymentProcessor {
    
    public $gateway;
    
    function __construct($type) {
        //Create the respective object depending upon gateway
        $this->gateway = $this->get_gateway($type);
    }
    
    /**
     * Implements gateway send_request
     * @param ShoppingCart $basket
     */
    public function process_request($basket) {
        $this->gateway->send_request($basket);
    }
    
    /**
     * Implements gateway process_response
     * @param object $rawdata - incoming data from provider
     */
    public function process_response($rawdata) {
        //Map raw incoming data from the provider to match our schema
        $mappeddata = $this->gateway->map_data($rawdata);
        $this->gateway->process_response($mappeddata);
    }
    
    /**
     * Creates gateway based on paypal provider
     * @param int $type
     */
    public static function get_gateway($type) {
        $gateway = false;
        switch($type) {
            case PAYMENT_PROVIDER_PAYPAL: {
                $gateway = new PayPalGateway();
                break;
            }
            case PAYMENT_PROVIDER_SAGEPAY: {
                $gateway = new SagePayGateway();
                break;
            }
            default: {
                // Default provider - PayPal
                $gateway = new PayPalGateway();
                break;
            }
        }
        return $gateway;
    }
}
