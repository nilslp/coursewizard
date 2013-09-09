<?php
global $CFG;
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');
include_once($CFG->dirroot . '/blocks/shopping_basket/cart/payment_processor.php');

/**
 * Representation of a shopping cart with static methods for adding, removing
 * and changing item quantities.  The cart itself is held as a session variable
 * called 'ShoppingCart'.
 * 
 * @copyright Learning Pool
 * @author Brian Quinn
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package shopping_basket
 */
class ShoppingCart {
    
    public function __construct() {
        
    }
    
    /**
     * Remove a specific item ID completely from the basket
     * @param type $id
     */
    private static function remove_item($id) {
        $basket = self::get_basket();
        
        if (array_key_exists($id, $basket->items)) {
            unset($basket->items[$id]);
        }

        self::save($basket);
        
        self::update_totals();
    }
    
    /**
     * Add a specific item to the basket
     * @param type $id Unique identifer of the item
     * @param int $quantity Quantity
     * @return type
     */
    public static function add_item($id, $quantity = 1) {       
        // Validation
        $quantity = floor($quantity);
        
        if ($quantity < 1) {
            return;
        } 
        
        $already_added = false;
        
        $basket = self::get_basket();

        $line_item = new stdClass();
        
        $product = get_product($id);
        
        $discountsenabled = get_config('block_shopping_basket', 'enablediscounts');
        
        if ($discountsenabled) {
            // When discount vouchers are enabled, the tax rate must be global
            $product->tax = self::get_global_taxrate();
        }
        
        $line_item->id = $id;
        $line_item->name = $product->fullname;
        $line_item->quantity = $quantity;
        $line_item->price = $product->cost;
        $line_item->discount = 0;
        $line_item->linetotal = $quantity * $product->cost;
        $line_item->linetax = round($product->tax * $line_item->linetotal, 2);
        $line_item->itemtax = $product->tax * 100;
        
        foreach ($basket->items as $item) {
            if ($item->id == $line_item->id) {
                // The item already exists in the array
                // Change the quantity and recalculate the tax and line total
                $item->quantity = $item->quantity + $quantity;
                $item->linetotal = $item->quantity * $item->price;
                $item->linetax = round($product->tax * $item->linetotal, 2);
                $item->itemtax = $product->tax * 100;
                
                $already_added = true;
                break;
            }
        }
        
        if (!$already_added) {
            $basket->items[$line_item->id] = $line_item;
        }
        
        self::save($basket);
        
        self::update_totals();
    }
    
    /**
     * Save the basket in session
     * @param stdClass $basket
     */
    private static function save($basket) {
        $_SESSION["ShoppingCart"] = $basket;
    }
    
    /**
     * This function handles al $_POST and $_GET operations used to manipulate
     * the contents of the basket
     * @return type
     */
    private static function process() {
        if (!$_POST && !$_GET) {
            // Nothing to do
            return;
        }
        
        $itemtoremove = optional_param('deletebasketitem', 0, PARAM_ALPHANUM);
        $itemtoupdate = optional_param('updatebasketitem', 0, PARAM_ALPHANUM);
        
        // Item added
        if (optional_param('my-add-button', '', PARAM_TEXT) != '') {
            $item_id = optional_param('my-item-id', '', PARAM_ALPHANUM);
            $item_quantity = optional_param('my-item-qty', 0, PARAM_INT);
            
            self::add_item($item_id, $item_quantity);
        } 
        
        // Item removed
        if ($itemtoremove !== 0) {
            self::remove_item($itemtoremove);
        }
        
        // Item quantity updated
        if ($itemtoupdate !== 0) {
            $item_quantity = optional_param('quantity', 0, PARAM_INT);
            
            if ($item_quantity <= 0) {
                self::remove_item($itemtoupdate);
            }
            else {
                self::update_item_quantity($itemtoupdate, $item_quantity);
            }
        }
        
        // Basket emptied
    }
    
    /**
     * Updates the quantity of a given product in the baseket
     * @param type $id Unique identifier of the product
     * @param int $quantity Quantity
     */
    private static function update_item_quantity($id, $quantity) {
        $basket = self::get_basket();
        
        $product = get_product($id);
        
        $discountsenabled = get_config('block_shopping_basket', 'enablediscounts');
        
        if ($discountsenabled) {
            // When discount vouchers are enabled, the tax rate must be global
            $product->tax = self::get_global_taxrate();
        }
        
        if (array_key_exists($id, $basket->items)) {
            // Update the item quantity and re-evaluate the line total
            $basket->items[$id]->quantity = $quantity;
            $basket->items[$id]->linetotal = $quantity * $product->cost;
            $basket->items[$id]->linetax = round($basket->items[$id]->linetotal * $product->tax, 2);
            $basket->items[$id]->itemtax =  $product->tax * 100;
        }
        
        self::save($basket);
        
        self::update_totals();
    }
    
    /**
     * Returns the shopping basket from session -- if it is not set a new one
     * is instantiated
     * @return type
     */
    public static function get_basket() {
        $basket = isset($_SESSION["ShoppingCart"]) ? $_SESSION["ShoppingCart"] : null;
              
        if (!$basket) {
            $basket = new stdClass();
            
            $basket->items = array();
            $basket->subtotal = 0;
            $basket->totaltax = 0;
            $basket->total = 0;
            $basket->itemcount = 0;
            $basket->vouchercode = '';
            $basket->discounttype = 0;
            $basket->discountrate = 0;
            $basket->discountamount = 0;
            
            $_SESSION["ShoppingCart"] = $basket;
        }
        
        return $basket;
    }
    
    /**
     * Return the basket
     * @return stdClass
     */
    public static function get_basket_for_checkout() {
        return self::get_basket();
    }
    
    /**
     * Keeps a running total on of the number of items and sub-total, i.e. sets
     * itemcount and subtotal
     */
    private static function update_totals() {
        $basket = self::get_basket();
        
        if ($basket) {
            $subtotal = 0;
            $itemcount = 0;
            $totaltax = 0;
            
            $discountsenabled = get_config('block_shopping_basket', 'enablediscounts');
            
            // Re-evaluate the item count, total and tax
            foreach ($basket->items as $item) {
                $product = get_product($item->id);
                // When discount vouchers are enabled, the tax rate must be global
                if ($discountsenabled) {
                    $product->tax = self::get_global_taxrate();
                }
                $product_discount = get_discount( $item->id, $item->quantity );
                $item->discount = $product_discount->amount;
                $item->linetotal = $item->quantity * ($item->price - $product_discount->amount);
                $item->linetax = round($product->tax * $item->linetotal, 2);
                $item->itemtax = $product->tax * 100;
                $subtotal += ($item->quantity * ($item->price - $product_discount->amount));
                $itemcount += $item->quantity;
                $totaltax += $item->linetax;
            }
            
            $basket->subtotal = $subtotal;
            
            if (count($basket->items) == 0 || !self::valid_discount_voucher()) {
                // Clear any discount voucher
                $basket->vouchercode = '';
                $basket->discounttype = 0;
                $basket->discountrate = 0;
            }
            
            if (!empty($basket->vouchercode)) {
                switch ($basket->discounttype) {
                    case DISCOUNT_TYPE_PERCENTAGE:
                        $percentage = floatval($basket->discountrate / 100);
                        $discountamount = round($basket->subtotal * $percentage, 2);
                        $basket->discountamount = $discountamount;
                        $basket->subtotal = $basket->subtotal - $discountamount;
                        break;
                    
                    case DISCOUNT_TYPE_FIXED_ORDER:
                        $basket->discountamount = $basket->discountrate;
                        $basket->subtotal = $basket->subtotal - $basket->discountrate;
                        break;
                }
                $totaltax = round($basket->subtotal * self::get_global_taxrate(), 2);
            }
            else {
                $basket->discountamount = 0;
            }
            
            // Add any tax (if set)                
            $basket->totaltax = round($totaltax, 2);
            $basket->total = $basket->subtotal + $basket->totaltax;
            $basket->itemcount = $itemcount;
        }
        
        self::save($basket);
    }
    
    public static function round_up($value, $decimals=2) { 
       $factor = (int) pow(10, (int) $decimals); 
       return round(ceil($value * $factor) / $factor, $decimals); 
    } 
    
    /**
     * Completely removes the shopping basket from session, clearing the contents
     */
    public static function empty_basket() {
        unset($_SESSION["ShoppingCart"]);
    }
    
    /**
     * Returns a stdClass object representation of a basket
     * @return type
     */
    public static function get_contents() {
        return self::get_basket()->items;
    }
    
    /**
     * Checks if a specified item is in the basket
     * @param int $id
     * @return bool true/false
     */
    public static function is_item_in_basket($id) {
        return key_exists($id, self::get_basket()->items);
    }
    
    public static function apply_discount_voucher($code) {
        $voucher = get_voucher_by_code($code);
        
        if (!$voucher) {
            return;
        }
        
        if (isset($voucher->expirydate) && $voucher->expirydate < time()) {
            // Voucher has expired
            return;
        }
        
        if ($voucher->minordervalue > self::get_basket_subtotal()) {
            // Order is too small for this voucher
            return;
        }
    
        self::add_discount_to_basket($code, $voucher->discounttype, $voucher->rate);
    }
    
    private static function valid_discount_voucher() {
        $basket = self::get_basket();
        
        // No voucher applied - nothing to do...
        if(empty($basket->vouchercode)) {
            return false;
        }
        
        $voucher = get_voucher_by_code($basket->vouchercode);
        
        // Voucher not found, get rid of it...
        if (!$voucher) {
            return false;
        }
        
        // Voucher expired, remove it...
        if (isset($voucher->expirydate) && $voucher->expirydate < time()) {
            // Voucher has expired
            return false;
        }
        
        // Basket total is less than discount amount value, remove voucher...
        if ($voucher->rate > $basket->subtotal) {
            // Order is too small for this voucher
            return false;
        }
        
        // Basket total doesn't match required value, remove voucher...
        if ($voucher->minordervalue > $basket->subtotal) {
            // Order is too small for this voucher
            return false;
        }
        
        //Vouchers fine
        return true;
    }
    
    private static function get_basket_subtotal() {
        return self::get_basket()->subtotal;
    }
    
    private static function add_discount_to_basket($code, $discounttype = DISCOUNT_TYPE_PERCENTAGE, $rate = 0) {
        $basket = self::get_basket();
        
        $basket->vouchercode = $code;
        $basket->discounttype = $discounttype;
        $basket->discountrate = $rate;
        
        self::save($basket);
                
        self::update_totals();        
    }
    
    public static function remove_discount_voucher() {
        $basket = self::get_basket();
        
        $basket->vouchercode = '';
        $basket->discounttype = 0;
        $basket->discountrate = 0;
        
        self::save($basket);
        
        self::update_totals();
    }
    
    /**
     * Generates the HTML for the user's shopping basket
     * @global type $CFG
     * @param bool $checkout Set to true for a checkout page
     * @return type
     */
    public static function display_basket($checkout = false) {   
        global $CFG, $USER;
        
        self::process();
        
        $basket = self::get_basket();
        $output = '';
        
        if ($checkout) {
            $gateway = PaymentProcessor::get_gateway(get_config('block_shopping_basket', 'paymentprovider'));
            $billingdetails = shopping_basket_get_billing_details();
            
            if ($gateway->require_user_details() && !$billingdetails->is_valid) {
                $addresshelplink = html_writer::link(new moodle_url('/user/edit.php', array('id' => $USER->id, 'course' => 1)), get_string('clicktoupdateaddress', 'block_shopping_basket'), array('id' => 'shopping_basket_address_help'));
                $output .= html_writer::tag('div', get_string('addressincomplete', 'block_shopping_basket', 'notifyproblem').'&nbsp;'.$addresshelplink, array('id' => 'notice', 'class' => 'address_notice'));
            }
            
            // If the user has purchased licenses, give them some info on the License Manager
            foreach ($basket->items as $item) {
                if($item->quantity > 1) {
                    $licensehelplink = html_writer::link('#', get_string('licenseaboutlink', 'block_shopping_basket'), array('id'=>'shopping_basket_help','class'=>'shopping_basket_help'));
                    $output .= html_writer::tag('div', get_string('licensepurchase', 'block_shopping_basket', 'notifyproblem').'&nbsp;'.$licensehelplink, array('id' => 'notice', 'class' => 'license_notice'));
                    break;
                }
            }
        }
        
        // Render the basket items
        if ($basket->itemcount !== 0) {
            $currency = get_config('block_shopping_basket', 'currency');
            $discountsenabled = get_config('block_shopping_basket', 'enablediscounts');
            $removetext = get_string('removefrombasket', 'block_shopping_basket');
            $clicktoremovetext = get_string('clicktoremove', 'block_shopping_basket');
            $coursestext = get_string('courses', 'block_shopping_basket');
            
            if ($checkout) {
                $output .=  html_writer::start_tag('form', array('id' => 'checkout_form', 'name' => 'checkout_form', 'method' => 'post', 'action' => "{$CFG->wwwroot}/blocks/shopping_basket/gateway.php"));                
            }
            else {
                $output .=  html_writer::start_tag('form', array('method' => 'post', 'action' => "{$CFG->wwwroot}/blocks/shopping_basket/checkout.php"));
            }

            if ($basket->itemcount == 1) {
                $headertext = get_string('singleitem', 'block_shopping_basket', $basket->itemcount);
            }
            else {
                $headertext = get_string('multipleitems', 'block_shopping_basket', $basket->itemcount);
            }
            
            $output .= html_writer::tag('div', $headertext, array('class' => 'basketheader'));
            
            $table = new html_table();
            $table->id = 'shopping_cart';
            
            $table->head = array(get_string('quantity', 'block_shopping_basket'), get_string('item', 'block_shopping_basket'), get_string('price', 'block_shopping_basket'));
            $table->attributes = array('class' => 'shopping_cart');

            $current_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $_SERVER['REQUEST_URI'];

            // Persist any existing query string parameters
            if (strpos($current_url, '?') == 0) {
                $current_url = $current_url . '?';
            }
            else {
                $current_url = $current_url . '&';
            }

            $data = array();
            
            // Render the basket items
            foreach ($basket->items as $item) {
                
                $product = get_product($item->id);
                $product_discount = get_discount($item->id,$item->quantity);
                $product_discount_str = '';
                
                if ($product_discount->amount != 0) {
                    $product_discount_str = number_format($product_discount->amount,2).'&nbsp;'.$currency.'&nbsp;'.get_string('discountperitem','block_shopping_basket');
                    $product_discount_str = html_writer::tag('span',$product_discount_str,array('class'=>'product_discount'));
                }
                
                if ($checkout) {
                    $coursestring = '<br />' . $coursestext . ':&nbsp;';
                    $coursenames = array();
                    
                    foreach ($product->courses as $course) {
                        $coursenames[] =  $course->fullname;
                    }
                    
                    $coursestring .= implode(', ', $coursenames);
                    
                    $cells = array();
                    $cells[] = new html_table_cell(html_writer::tag('input', null, array('name' => 'basket-item[]', 'type' => 'hidden', 'value' => $item->id)) .html_writer::tag('input', null, array('id' => "item_quantity_{$item->id}", 'name' => "item-{$item->id}-quantity", 'class' => 'item_quantity', 'type' => 'text', 'value' => $item->quantity, 'size' => '1')));
                    $cells[] = new html_table_cell(html_writer::link(new moodle_url('/blocks/shopping_basket/view_product.php', array('id' => $item->id)), $item->name) . $product_discount_str . $coursestring);
                    $cells[] = new html_table_cell(number_format($item->linetotal, 2) . html_writer::empty_tag('br') . html_writer::link($current_url . "deletebasketitem={$item->id}", $removetext, array('title' => $clicktoremovetext, 'class' => 'remove_basket_item', 'id' => "remove_basket_{$item->id}")));

                    $row = new html_table_row($cells);          

                    $data[] = $row;
                }
                else {
                    $textcell = new html_table_cell(html_writer::link(new moodle_url('/blocks/shopping_basket/view_product.php', array('id' => $item->id)), $item->name).$product_discount_str);
                    $textcell->colspan = 3;
                    $textcell->attributes = array('class' => 'basket_item_desc');
                    $firstrow = new html_table_row(array($textcell));          
                    $data[] = $firstrow;
                    
                    $numbercells = array();
                    
                    $numbercells[] = new html_table_cell(html_writer::tag('input', null, array('name' => 'basket-item[]', 'type' => 'hidden', 'value' => $item->id)) .html_writer::tag('input', null, array('id' => "item_quantity_{$item->id}", 'name' => "item-{$item->id}-quantity", 'class' => 'item_quantity', 'type' => 'text', 'value' => $item->quantity, 'size' => '1')));
                    $numbercells[] = new html_table_cell();
                    $numbercells[] = new html_table_cell(number_format($item->linetotal, 2) . html_writer::empty_tag('br') . html_writer::link($current_url . "deletebasketitem={$item->id}", $removetext, array('title' => $clicktoremovetext, 'class' => 'remove_basket_item', 'id' => "remove_basket_{$item->id}")));
                    $secondrow = new html_table_row($numbercells);
                    
                    $data[] = $secondrow;
                }
            }
            
            $discountstring = '';
            
            if ($basket->discountrate != 0) {
                switch ($basket->discounttype) {
                    case DISCOUNT_TYPE_PERCENTAGE:
                        $discountstring = '- ' . $basket->discountrate . '%';
                        break;
                    case DISCOUNT_TYPE_FIXED_ORDER:
                        $discountstring = '- ' . $basket->discountrate;
                        break;
                }                
            }

            if ($basket->discountrate != 0) {
                // Discount
                $discountlabelcell = new html_table_cell(get_string('discount', 'block_shopping_basket') . '<br />');           
                $discountlabelcell->colspan = 2;
                $discountcell = new html_table_cell($discountstring . '<br />' . html_writer::link('#', $removetext, array('title' => $clicktoremovetext, 'class' => 'remove_discount')));
                $discountrow = new html_table_row(array($discountlabelcell, $discountcell));
                $discountrow->id = 'discount_row';
                $data[] = $discountrow;
            }
            
            // Sub-total
            $subtotallabelcell = new html_table_cell(get_string('subtotal', 'block_shopping_basket'));
            $subtotallabelcell->colspan = 2;
            $subtotalcell = new html_table_cell(number_format($basket->subtotal, 2));
            $subtotalrow = new html_table_row(array($subtotallabelcell, $subtotalcell));
            $subtotalrow->id = 'subtotal_row';
            $data[] = $subtotalrow;
            
            // Tax
            $taxlabelcell = new html_table_cell(get_string('tax', 'block_shopping_basket'));           
            $taxlabelcell->colspan = 2;            
            $taxcell = new html_table_cell(number_format($basket->totaltax, 2));
            $taxrow = new html_table_row(array($taxlabelcell, $taxcell));
            $taxrow->id = 'tax_row';
            $data[] = $taxrow;
            
            // Total
            $totallabelcell = new html_table_cell(get_string('total', 'block_shopping_basket') . '&nbsp;' . sprintf("(%s)", $currency));           
            $totallabelcell->colspan = 2;
            $totalcell = new html_table_cell(number_format($basket->total, 2));
            $totalrow = new html_table_row(array($totallabelcell, $totalcell));
            $totalrow->id = 'total_row';
            $data[] = $totalrow;
            
            // Add discount
            if ($checkout &&  $discountsenabled && $basket->discountrate == 0) {
                $addvoucherlabelcell = new html_table_cell('<br />' . get_string('voucherprompt', 'block_shopping_basket') . '<br />' . 
                        html_writer::empty_tag('input', array('type' => 'text', 'id' => 'vouchercode', 'name' => 'vouchercode', 'value' => '', 'maxlength' => 20)) 
                        . html_writer::empty_tag('input', array('type' => 'button', 'id' => 'add_discount', 'name' => 'add_discount', 'class' => 'add_discount', 'title' => get_string('apply', 'block_shopping_basket'), 'value' => get_string('apply', 'block_shopping_basket'))));           
                $addvoucherlabelcell->colspan = 3;            
                $voucherrow = new html_table_row(array($addvoucherlabelcell));
                $voucherrow->id = 'voucher_row';            
                $data[] = $voucherrow;
            }
            
            $table->data = $data;
            
            $output .= html_writer::table($table);
                        
            if (!$checkout) {
                $output .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('checkout', 'block_shopping_basket')));
            }
            else {
                // We are on the checkout page
                // Ensure that the user is logged in
                if ($USER->id != 0) {
                                        
                    if ($gateway->require_user_details()) {
                        // Enter/verify user details and checkout

                        $output .= html_writer::start_tag('div', array('id' => 'billing_details'));
                        
                        if (isset($billingdetails)) {
                            $output .= html_writer::tag('b', $billingdetails->category);
                            $output .= html_writer::empty_tag('br');
                            
                            $address = $billingdetails->address;
                            
                            if (isset($address)) {
                                foreach ($address as $key => $line) {
                                    if (isset($line->value)) {
                                        $output .= $line->value . html_writer::empty_tag('br');
                                    }
                                }
                            }

                            $output .= html_writer::empty_tag('br');
                            
                            if (!$billingdetails->is_valid) {
                                $output .= get_string('addressupdaterequired', 'block_shopping_basket') . html_writer::empty_tag('br');
                            }
                            
                            $output .= html_writer::link(new moodle_url('/user/edit.php', array('id' => $USER->id, 'course' => 1)), get_string('addeditaddressdetails', 'block_shopping_basket'), null);
                        }
                        
                        $output .= html_writer::end_tag('div');
                        
                        $output .= $gateway->pay_now_html($billingdetails->is_valid);
                    }
                    else {
                        $output .= $gateway->pay_now_html();
                    }    
                    
                    if (get_config('block_shopping_basket', 'acceptpo')) {
                        // Provide the Purchase Order option
                        $output .= html_writer::empty_tag('br');
                        $output .= html_writer::label(get_string('orpurchaseorder', 'block_shopping_basket') . '&nbsp;', 'po_number');
                        $output .= html_writer::empty_tag('input', array('id' => 'po_number', 'name' => 'po_number', 'type' => 'text', 'autocomplete' => 'off', 'placeholder' => get_string('ponumber', 'block_shopping_basket')));
                        $output .= html_writer::empty_tag('input', array('id' => 'submit_po', 'name' => 'submit_po', 'type' => 'submit', 'value' => 'OK'));
                    }
                }
                else {
                    // Output some text to get the user to log in
                    $signinhtml = get_string('notloggedin', 'block_shopping_basket') . html_writer::empty_tag('br');
                    $loginurl = new moodle_url('/login/index.php');
                    $signinhtml .= get_string('pleaseloginorcreateaccount', 'block_shopping_basket', "<a href='$loginurl'>" . get_string('clickhere', 'block_shopping_basket') .'</a>');
                    
                    $output .= html_writer::tag('div', $signinhtml);
                }
            }
            
            $output .= html_writer::end_tag('form');
        }
        else {
            // The basket is empty
            $output = html_writer::start_tag('div', array('id' => 'empty_basket'));
            $output .= get_string('basketempty', 'block_shopping_basket');
            $output .= html_writer::end_tag('div');
        }
        
        return $output;
    }
    
    /**
     * Get the globally defined tax rate
     * @return float
     */
    private static function get_global_taxrate() {
        $taxrate = get_config('block_shopping_basket', 'taxrate');
        if (!isset($taxrate) || empty($taxrate)) {
            $taxrate = 0;
        }
        return $taxrate;
    }
}
