<?php
/**
 * Representation of a payment provider gateway. Contains abstract methods to
 * send/receive transaction details to/from a payment provider.
 * 
 * @copyright Learning Pool
 * @author Kevin Corry
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package shopping_basket
 */
abstract class PaymentGateway {
    
    public $sandboxmode = false;
    public $currencycode;
    public $business;
    public $discountsenabled;
    public $taxratio;
    public $field_mapping;
    public $failureurl;
    
    public function __construct() {
        global $CFG;
        $this->currencycode = get_config('block_shopping_basket', 'currency');
        $this->business = get_config('block_shopping_basket', 'vendoridentifier');
        $this->sandboxmode = get_config('block_shopping_basket', 'sandboxmode');
        $this->discountsenabled = get_config('block_shopping_basket', 'enablediscounts');
        $this->taxratio = get_config('block_shopping_basket', 'taxrate');
        $this->failureurl = $CFG->wwwroot . '/blocks/shopping_basket/failed.php';
    }
    
    abstract public function build_query($basket);
    
    abstract public function send_request($basket);
    
    abstract public function process_response($data);
    
    /**
     * Convert incoming data from the payment provider to match 
     * our schema nomenclature
     * @param type $rawdata
     */
    public function map_data($rawdata) {
        foreach($this->field_mapping as $data_field => $schema_field) {
            if(isset($rawdata->$data_field)) {
                $rawdata->$schema_field = $rawdata->$data_field;
                unset($rawdata->$data_field);
            }
        }
        return $rawdata;
    }

    abstract public function require_user_details();
    
    abstract public function pay_now_html();
    
    abstract public function validate_purchase($ipn_data);
    
    /**
    * Check Payment status
    * @param stdClass $data
    * @return boolean $payment_received
    */
    function payment_received($data) {
        return ($data->payment_status == PAYMENT_STATUS_COMPLETED);
    }
    
    /**
    * Ensure IPN txn_id is unique
    * @param stdClass $data
    * @return boolean $valid
    */
    function valid_txn_id($data) {
       global $DB;
       return $DB->count_records('shopping_basket_order', array('txn_id' => $data->txn_id)) == 1;
    }

}
