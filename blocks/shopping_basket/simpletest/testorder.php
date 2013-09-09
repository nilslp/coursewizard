<?php
/**
 * Unit tests relating to order functions (blocks/shopping_basket)
 * @copyright Learning Pool
 * @author Kevin Corry
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package shopping_basket
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');
require_once($CFG->dirroot . "/blocks/shopping_basket/cart/shopping_cart.php");
require_once($CFG->dirroot . '/blocks/shopping_basket/cart/payment_processor.php');

/** This class contains the test cases for the order functions in lib.php. */
class shopping_basket_order_test extends UnitTestCaseUsingDatabase {
    
    public  static $includecoverage = array('blocks/shopping_basket/lib.php');
    public $userid = 0;
    
    public function setUp() {
        parent::setUp();
        
        // Shopping basket tables
        $this->create_test_tables(
            array (
                'shopping_basket_log',
                'shopping_basket_license',
                'shopping_basket_order',
                'shopping_basket_order_detail',
                'shopping_basket_product',
                'shopping_basket_prod_cat',
                'shopping_basket_prod_course',
                'shopping_basket_prod_disc',
                'shopping_basket_voucher',
            ),
            'blocks/shopping_basket'
        );
        // Core tables
        $this->create_test_tables(
            array(
                'cache_flags',
                'config',
                'config_plugins',
                'course',
                'enrol',
                'groups',
                'context',
                'events_handlers',
                'role',
                'role_assignments',
                'user',
                'user_enrolments',
                'user_info_field',
                'user_preferences',
            ),
            'lib'
        );
        
        $this->switch_to_test_db();

        // Create default products
        $product = new stdClass();
        $product->id = 1;
        $product->itemcode = 'SKU001';
        $product->fullname = 'SKU001 Product';
        $product->cost = 10;
        $product->tax = 0.2;
        $product->timecreated = time();
        $product->timemodified = time();
        $product->modifierid = 2;
        $product->id = $this->testdb->insert_record('shopping_basket_product', $product);
        
        $product = new stdClass();
        $product->id = 2;
        $product->itemcode = 'SKU002';
        $product->fullname = 'SKU002 Product';
        $product->cost = 34;
        $product->tax = 0.2;
        $product->timecreated = time();
        $product->timemodified = time();
        $product->modifierid = 2;
        $product->id = $this->testdb->insert_record('shopping_basket_product', $product);
        
        $product = new stdClass();
        $product->id = 3;
        $product->itemcode = 'SKU003';
        $product->fullname = 'SKU003 Product';
        $product->cost = 50;
        $product->tax = 0.2;
        $product->timecreated = time();
        $product->timemodified = time();
        $product->modifierid = 2;
        $product->id = $this->testdb->insert_record('shopping_basket_product', $product);
        
        // Create a couple of courses
        $course = new stdClass();
        $course->category = 1;
        $course->fullname = 'Anonymous test course';
        $course->shortname = 'ANON';
        $course->summary = '';
        $this->testdb->insert_record('course', $course);
        
        $course = new stdClass();
        $course->category = 1;
        $course->fullname = 'Anonymous test course 2';
        $course->shortname = 'ANON2';
        $course->summary = '';
        $this->testdb->insert_record('course', $course);
        
        $course = new stdClass();
        $course->category = 1;
        $course->fullname = 'Anonymous test course 3';
        $course->shortname = 'ANON3';
        $course->summary = '';
        $course->id = $this->testdb->insert_record('course', $course);
        
        // Create product-course rlnshp
        $product_course = new stdClass();
        $product_course->product = 1;
        $product_course->course = $course->id;
        $product_course->timemodified = time();
        $product_course->modifierid = 2;
        $product_course->id = $this->testdb->insert_record('shopping_basket_prod_course', $product_course);
        
        // Create product-course rlnshp
        $product_course = new stdClass();
        $product_course->product = 2;
        $product_course->course = $course->id;
        $product_course->timemodified = time();
        $product_course->modifierid = 2;
        $product_course->id = $this->testdb->insert_record('shopping_basket_prod_course', $product_course);
        
        // Create product-course rlnshp
        $product_course = new stdClass();
        $product_course->product = 3;
        $product_course->course = $course->id;
        $product_course->timemodified = time();
        $product_course->modifierid = 2;
        $product_course->id = $this->testdb->insert_record('shopping_basket_prod_course', $product_course);
        
        // Create product-discount rlnshps
        $product_discount = new stdClass();
        $product_discount->productid = 3;
        $product_discount->min = 0;
        $product_discount->max = 5;
        $product_discount->rate = 5;
        $product_discount->criteria = TIER_CRITERIA_FIXED_AMOUNT;
        $product_discount->type = TIER_TYPE_QUANTITY;
        $this->testdb->insert_record('shopping_basket_prod_disc', $product_discount);
        
        $product_discount = new stdClass();
        $product_discount->productid = 3;
        $product_discount->min = 6;
        $product_discount->max = 10;
        $product_discount->rate = 7;
        $product_discount->criteria = TIER_CRITERIA_PERCENTAGE;
        $product_discount->type = TIER_TYPE_QUANTITY;
        $this->testdb->insert_record('shopping_basket_prod_disc', $product_discount);
        
        // Create required contexts
        $contexts = array(CONTEXT_SYSTEM => 1, CONTEXT_COURSE => $course->id, CONTEXT_MODULE => 1);
        foreach ($contexts as $level => $instance) {
            $context = new stdClass;
            $context->contextlevel = $level;
            $context->instanceid = $instance;
            $context->path = 'not initialised';
            $context->depth = '13';
            $this->testdb->insert_record('context', $context);
        }
        
        // Create required settings
        $settings = array (
            'block_shopping_basket' => array (
                'currency' => 'GBP',
                'taxrate' => '0.2',
                'enablediscounts' => '1',
                'vendoridentifier' => 'business@lpsite.com',
                'sandboxmode' => '1',
                'enrolonpurchasedate' => '1',
                'alertemail' => 'kevinc@learningpool.com',
                'lastcron' => '1370357144',
                'expirynotifytemplate' => '',
                'enableexpirynotice' => '1',
                'customexpirynotice' => '',
                'expirythreshold' => '432000',
                'enableexpiryemail' => '1',
                'customexpiryemail' => '',
                'acceptpo' => '0',
            ),
            'enrol_premium' => array (
                'autoenrol' => 1,
            )
	);

        foreach ($settings as $plugin => $setting) {
            foreach($setting as $name => $value) {
                $setting = new stdClass;
                $setting->plugin = $plugin;
                $setting->name = $name;
                $setting->value = $value;
                $this->testdb->insert_record('config_plugins', $setting);
            }
        }

        // Create default user
        $user = new stdClass();
        $user->username = 'testuser';
        $user->confirmed = 1;
        $user->firstname = 'Jimmy';
        $user->lastname = 'Kinnon';
        $this->userid = $this->testdb->insert_record('user', $user);
        
        // Create student role
        $role = new stdClass();
        $role->name = 'Student';
        $role->shortname = 'student';
        $role->description = 'Student role';
        $role->archetype = 'student';
        $this->testdb->insert_record('role', $role);
        
        // Create test vouchers
        $voucher = new stdClass();
        $voucher->discountcode = '10%OFF';
        $voucher->discounttype = DISCOUNT_TYPE_PERCENTAGE;
        $voucher->rate = 10;
        $voucher->minordervalue = 0;
        $voucher->expirydate = null;
        $voucher->singleuse = 0;
        $voucher->maxval = 0;
        $this->testdb->insert_record('shopping_basket_voucher', $voucher);
        
        $voucher = new stdClass();
        $voucher->discountcode = '5GBPOFF';
        $voucher->discounttype = DISCOUNT_TYPE_FIXED_ORDER;
        $voucher->rate = 5;
        $voucher->minordervalue = 0;
        $voucher->expirydate = null;
        $voucher->singleuse = 0;
        $voucher->maxval = 0;
        $this->testdb->insert_record('shopping_basket_voucher', $voucher);
    }

    public function tearDown() {
        parent::tearDown();
    }
    
    /**
     * Update a plugin config item - useful when results vary based on config
     * @param type $plugin
     * @param type $setting
     * @param type $value
     */
    private function update_config_setting($plugin, $setting, $value) {
        $id = $this->testdb->get_record('config_plugins', array('plugin' => $plugin, 'name' => $setting), 'id');
        $update = new stdClass();
        $update->id = $id->id;
        $update->plugin = $plugin;
        $update->name = $setting;
        $update->value = $value;
        $this->testdb->update_record('config_plugins', $update);
    }
    
    /**
     * Create a dummy basket (single product)
     * @param type $productid
     * @param type $quantity
     * @return type
     */
    function single_item_basket($productid, $quantity, $voucher = false) {
        $basket = new ShoppingCart();
        $basket->empty_basket();
        $basket->add_item($productid, $quantity);
        if($voucher) {
            $basket->apply_discount_voucher($voucher);
        }
        return $basket->get_basket();
    }
    
    /**
     * Create a dummy basket (two products)
     * @param type $productid
     * @param type $quantity
     * @return type
     */
    function multi_item_basket($product_one, $qty_one, $product_two, $qty_two, $voucher = false) {
        $basket = new ShoppingCart();
        $basket->empty_basket();
        $basket->add_item($product_one, $qty_one);
        $basket->add_item($product_two, $qty_two);
        if($voucher) {
            $basket->apply_discount_voucher($voucher);
        }
        return $basket->get_basket();
    }
    
    /**
     * Test order creation - single item, qty = 1
     */
    function test_order_create_single_item_single() {
        global $DB, $USER;
        
        $basket = $this->single_item_basket(1,1);
        
        // Add order
        $order_id = add_order($basket);
        
        $order = get_order($order_id);
        $order_details = get_order_details($order_id);
        
        // Check order details match basket
        $this->assertEqual($order_id, $order->id, 'Order ID test');
        $this->assertEqual($order->userid, $USER->id, 'User ID test');
        $this->assertEqual($order->total, $basket->total, 'Order Total test');
        $this->assertEqual($order->fulfilled, 0, 'Order Fulfilled test');
        
        // Check order detail records were created
        $this->assertEqual(count($order_details), count($basket->items), 'Order item count test');
        
        // Check order detail records match basket items
        foreach($order_details as $order_detail) {
            $this->assertEqual($order_detail->productid, $basket->items[$order_detail->productid]->id, 'Order detail Product ID test');
            $this->assertEqual($order_detail->quantity, $basket->items[$order_detail->productid]->quantity, 'Order detail quantity test');
            $this->assertEqual($order_detail->cost, $basket->items[$order_detail->productid]->price, 'Order detail cost test');
            $this->assertEqual($order_detail->linetotal, $basket->items[$order_detail->productid]->linetotal, 'Order detail line total test');
            $this->assertEqual($order_detail->linetax, $basket->items[$order_detail->productid]->linetax, 'Order detail line tax test');
        }
    }
    
    /**
     * Test order creation - single item, qty = 3
     */
    function test_order_create_single_item_multiple() {
        global $DB, $USER;
        
        $basket = $this->single_item_basket(1,3);
        
        // Add order
        $order_id = add_order($basket);
        
        $order = get_order($order_id);
        $order_details = get_order_details($order_id);
        
        // Check order details match basket
        $this->assertEqual($order_id, $order->id, 'Order ID test');
        $this->assertEqual($order->userid, $USER->id, 'User ID test');
        $this->assertEqual($order->total, $basket->total, 'Order Total test');
        $this->assertEqual($order->fulfilled, 0, 'Order Fulfilled test');
        
        // Check order detail records were created
        $this->assertEqual(count($order_details), count($basket->items), 'Order item count test');
        
        // Check order detail records match basket items
        foreach($order_details as $order_detail) {
            $this->assertEqual($order_detail->productid, $basket->items[$order_detail->productid]->id, 'Order detail Product ID test');
            $this->assertEqual($order_detail->quantity, $basket->items[$order_detail->productid]->quantity, 'Order detail quantity test');
            $this->assertEqual($order_detail->cost, $basket->items[$order_detail->productid]->price, 'Order detail cost test');
            $this->assertEqual($order_detail->linetotal, $basket->items[$order_detail->productid]->linetotal, 'Order detail line total test');
            $this->assertEqual($order_detail->linetax, $basket->items[$order_detail->productid]->linetax, 'Order detail line tax test');
        }
    }
    
    /**
     * Test order creation - muliple items, qty = 1
     */
    function test_order_create_multiple_item_single() {
        global $DB, $USER;
        
        $basket = $this->multi_item_basket(1,1,2,1);
        
        // Add order
        $order_id = add_order($basket);
        
        $order = get_order($order_id);
        $order_details = get_order_details($order_id);
        
        // Check order details match basket
        $this->assertEqual($order_id, $order->id, 'Order ID test');
        $this->assertEqual($order->userid, $USER->id, 'User ID test');
        $this->assertEqual($order->total, $basket->total, 'Order Total test');
        $this->assertEqual($order->fulfilled, 0, 'Order Fulfilled test');
        
        // Check order detail records were created
        $this->assertEqual(count($order_details), count($basket->items), 'Order item count test');
        
        // Check order detail records match basket items
        foreach($order_details as $order_detail) {
            $this->assertEqual($order_detail->productid, $basket->items[$order_detail->productid]->id, 'Order detail Product ID test');
            $this->assertEqual($order_detail->quantity, $basket->items[$order_detail->productid]->quantity, 'Order detail quantity test');
            $this->assertEqual($order_detail->cost, $basket->items[$order_detail->productid]->price, 'Order detail cost test');
            $this->assertEqual($order_detail->linetotal, $basket->items[$order_detail->productid]->linetotal, 'Order detail line total test');
            $this->assertEqual($order_detail->linetax, $basket->items[$order_detail->productid]->linetax, 'Order detail line tax test');
        }
    }
    
    /**
     * Test order creation - muliple items, qty = 3
     */
    function test_order_create_multiple_item_multiple() {
        global $DB, $USER;
        
        $basket = $this->multi_item_basket(1,3,2,3);
        
        // Add order
        $order_id = add_order($basket);
        
        $order = get_order($order_id);
        $order_details = get_order_details($order_id);
        
        // Check order details match basket
        $this->assertEqual($order_id, $order->id, 'Order ID test');
        $this->assertEqual($order->userid, $USER->id, 'User ID test');
        $this->assertEqual($order->total, $basket->total, 'Order Total test');
        $this->assertEqual($order->fulfilled, 0, 'Order Fulfilled test');
        
        // Check order detail records were created
        $this->assertEqual(count($order_details), count($basket->items), 'Order item count test');
        
        // Check order detail records match basket items
        foreach($order_details as $order_detail) {
            $this->assertEqual($order_detail->productid, $basket->items[$order_detail->productid]->id, 'Order detail Product ID test');
            $this->assertEqual($order_detail->quantity, $basket->items[$order_detail->productid]->quantity, 'Order detail quantity test');
            $this->assertEqual($order_detail->cost, $basket->items[$order_detail->productid]->price, 'Order detail cost test');
            $this->assertEqual($order_detail->linetotal, $basket->items[$order_detail->productid]->linetotal, 'Order detail line total test');
            $this->assertEqual($order_detail->linetax, $basket->items[$order_detail->productid]->linetax, 'Order detail line tax test');
        }
    }
    
    /**
     * Test order creation - muliple items, mixed qty
     */
    function test_order_create_multiple_item_mixed() {
        global $DB, $USER;
        
        $basket = $this->multi_item_basket(1,2,2,1);
        
        // Add order
        $order_id = add_order($basket);
        
        $order = get_order($order_id);
        $order_details = get_order_details($order_id);
        
        // Check order details match basket
        $this->assertEqual($order_id, $order->id, 'Order ID test');
        $this->assertEqual($order->userid, $USER->id, 'User ID test');
        $this->assertEqual($order->total, $basket->total, 'Order Total test');
        $this->assertEqual($order->fulfilled, 0, 'Order Fulfilled test');
        
        // Check order detail records were created
        $this->assertEqual(count($order_details), count($basket->items), 'Order item count test');
        
        // Check order detail records match basket items
        foreach($order_details as $order_detail) {
            $this->assertEqual($order_detail->productid, $basket->items[$order_detail->productid]->id, 'Order detail Product ID test');
            $this->assertEqual($order_detail->quantity, $basket->items[$order_detail->productid]->quantity, 'Order detail quantity test');
            $this->assertEqual($order_detail->cost, $basket->items[$order_detail->productid]->price, 'Order detail cost test');
            $this->assertEqual($order_detail->linetotal, $basket->items[$order_detail->productid]->linetotal, 'Order detail line total test');
            $this->assertEqual($order_detail->linetax, $basket->items[$order_detail->productid]->linetax, 'Order detail line tax test');
        }
    }
    
    /**
     * Test order process_purchase - single item
     */
    function test_process_order_single() {
        global $DB;
        
        $basket = $this->single_item_basket(1,1);
        
        // Add order
        $order_id = add_order($basket);
        $order = get_order($order_id);
        
        process_purchase($order_id);
        
        // Not paid yet, check should be false
        $check = check_order_fulfilled($order);
        $this->assertFalse($check->fulfilled);
        
        $updates = new stdClass();
        $updates->id = $order_id;
        // Override user - add_order uses $USER
        $updates->userid = $this->userid;
        $updates->payment_status = PAYMENT_STATUS_COMPLETED;
        
        $set_paid = $DB->update_record('shopping_basket_order', $updates);
        
        process_purchase($order_id);
        
        $order = get_order($order_id);
        // Paid, check should be true
        $check = check_order_fulfilled($order);
        $this->assertTrue($check->fulfilled);
    }
    
    /**
     * Test order process_purchase - single item
     */
    function test_process_order_single_noautoenrol() {
        global $DB;
        
        //Set autoenrol = 0
        $this->update_config_setting('enrol_premium', 'autoenrol', 0);
        
        $basket = $this->single_item_basket(1,1);
        
        // Add order
        $order_id = add_order($basket);
        $order = get_order($order_id);
        
        process_purchase($order_id);
        
        // Not paid yet, check should be false
        $check = check_order_fulfilled($order);
        $this->assertFalse($check->fulfilled);
        
        $updates = new stdClass();
        $updates->id = $order_id;
        // Override user - add_order uses $USER
        $updates->userid = $this->userid;
        $updates->payment_status = PAYMENT_STATUS_COMPLETED;
        
        $set_paid = $DB->update_record('shopping_basket_order', $updates);
        
        process_purchase($order_id);
        
        $order = get_order($order_id);
        // Paid, check should be true
        $check = check_order_fulfilled($order);
        $this->assertTrue($check->fulfilled);
        
        //Set autoenrol back to 1
        $this->update_config_setting('enrol_premium', 'autoenrol', 1);
    }
    
    /**
     * Test order process_purchase - multiple items
     */
    function test_process_order_multiple() {
        global $DB;
        
        $basket = $this->multi_item_basket(1,3,2,3);
        
        // Add order
        $order_id = add_order($basket);
        $order = get_order($order_id);
        
        process_purchase($order_id);
        
        // Not paid yet, check should be false
        $check = check_order_fulfilled($order);
        $this->assertFalse($check->fulfilled);
        
        $updates = new stdClass();
        $updates->id = $order_id;
        // Override user - add_order uses $USER
        $updates->userid = $this->userid;
        $updates->payment_status = PAYMENT_STATUS_COMPLETED;
        
        $set_paid = $DB->update_record('shopping_basket_order', $updates);
        
        process_purchase($order_id);
        
        $order = get_order($order_id);
        // Paid, check should be true
        $check = check_order_fulfilled($order);
        $this->assertTrue($check->fulfilled);
    }
    
    /**
     * Test order process_purchase - multiple items
     */
    function test_process_order_multiple_noautoenrol() {
        global $DB;
        
        //Set autoenrol = 0
        $this->update_config_setting('enrol_premium', 'autoenrol', 0);
        
        $basket = $this->multi_item_basket(1,3,2,3);
        
        // Add order
        $order_id = add_order($basket);
        $order = get_order($order_id);
        
        process_purchase($order_id);
        
        // Not paid yet, check should be false
        $check = check_order_fulfilled($order);
        $this->assertFalse($check->fulfilled);
        
        $updates = new stdClass();
        $updates->id = $order_id;
        // Override user - add_order uses $USER
        $updates->userid = $this->userid;
        $updates->payment_status = PAYMENT_STATUS_COMPLETED;
        
        $set_paid = $DB->update_record('shopping_basket_order', $updates);
        
        process_purchase($order_id);
        
        $order = get_order($order_id);
        // Paid, check should be true
        $check = check_order_fulfilled($order);
        $this->assertTrue($check->fulfilled);
        
        //Set autoenrol back to 1
        $this->update_config_setting('enrol_premium', 'autoenrol', 1);
    }
    
    /**
     * Test order validation functions
     */
    function test_order_validation_single() {
        global $DB;
        $basket = $this->single_item_basket(1,1);
        
        // Add order
        $order_id = add_order($basket);
        
        $updates = new stdClass();
        $updates->id = $order_id;
        // Override user - add_order uses $USER
        $updates->userid = $this->userid;
        $updates->currency = 'GBP';
        $updates->total = 12;
        $updates->txn_id = 'XYZ';
        $updates->payment_status = PAYMENT_STATUS_COMPLETED;
        
        $set_paid = $DB->update_record('shopping_basket_order', $updates);
        
        // Begin the tests proper
        
        $ipn_data = new stdClass();
        $ipn_data->orderid = 1;
        $ipn_data->payment_status = PAYMENT_STATUS_COMPLETED;
        $ipn_data->mc_gross = 12;
        $ipn_data->mc_currency = 'GBP';
        $ipn_data->txn_id = 'XYZ';
        $ipn_data->receiver_email = 'business@lpsite.com';
        
        $processor = new PaymentProcessor(get_config('block_shopping_basket', 'paymentprovider'));
        
        // All should be valid here
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertTrue($validate->valid);
        
        $ipn_data->payment_status = 'Pending';
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertFalse($validate->valid);
        
        $ipn_data->payment_status = PAYMENT_STATUS_COMPLETED;
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertTrue($validate->valid);
        
        $ipn_data->mc_gross = 10;
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertFalse($validate->valid);
        
        $ipn_data->mc_gross = 12;
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertTrue($validate->valid);
        
        $ipn_data->mc_currency = 'EUR';
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertFalse($validate->valid);
        
        $ipn_data->mc_currency = 'GBP';
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertTrue($validate->valid);

        $ipn_data->receiver_email = 'incorrect@lpsite.com';
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertFalse($validate->valid);
        
        $ipn_data->receiver_email = 'business@lpsite.com';
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertTrue($validate->valid);
    }
    
    /**
     * Test order validation functions
     */
    function test_order_validation_multi() {
        global $DB;
        $basket = $this->multi_item_basket(1,1,2,2);
        
        // Add order
        $order_id = add_order($basket);
        
        $updates = new stdClass();
        $updates->id = $order_id;
        // Override user - add_order uses $USER
        $updates->userid = $this->userid;
        $updates->currency = 'GBP';
        $updates->total = 36;
        $updates->txn_id = 'XYZ';
        $updates->payment_status = PAYMENT_STATUS_COMPLETED;
        
        $set_paid = $DB->update_record('shopping_basket_order', $updates);
        
        // Begin the tests proper
        
        $ipn_data = new stdClass();
        $ipn_data->orderid = 1;
        $ipn_data->payment_status = PAYMENT_STATUS_COMPLETED;
        $ipn_data->mc_gross = 36;
        $ipn_data->mc_currency = 'GBP';
        $ipn_data->txn_id = 'XYZ';
        $ipn_data->receiver_email = 'business@lpsite.com';
        
        $processor = new PaymentProcessor(get_config('block_shopping_basket', 'paymentprovider'));
        
        // All should be valid here
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertTrue($validate->valid);
        
        $ipn_data->payment_status = 'Pending';
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertFalse($validate->valid);
        
        $ipn_data->payment_status = PAYMENT_STATUS_COMPLETED;
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertTrue($validate->valid);
        
        $ipn_data->mc_gross = 30;
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertFalse($validate->valid);
        
        $ipn_data->mc_gross = 36;
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertTrue($validate->valid);
        
        $ipn_data->mc_currency = 'EUR';
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertFalse($validate->valid);
        
        $ipn_data->mc_currency = 'GBP';
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertTrue($validate->valid);

        $ipn_data->receiver_email = 'incorrect@lpsite.com';
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertFalse($validate->valid);
        
        $ipn_data->receiver_email = 'business@lpsite.com';
        $validate = $processor->gateway->validate_purchase($ipn_data);
        $this->assertTrue($validate->valid);
    }
    
    /**
     * Test User creation from IPN details
     * @global type $CFG
     */
    function test_create_order_user() {
        global $CFG;
        
        $ipn_data = new stdClass();
        $ipn_data->orderid = 1;
        $ipn_data->payer_email = 'unit.tester@testing.com';
        $ipn_data->first_name = 'Unit';
        $ipn_data->last_name = 'Tester';
        $ipn_data->address_city = 'London';
        $ipn_data->address_country_code = 'GB';
        
        $user = check_order_user($ipn_data);

        $this->assertNotEqual($user, false);
        $this->assertEqual($user->username, $ipn_data->payer_email);
        $this->assertEqual($user->firstname, $ipn_data->first_name);
        $this->assertEqual($user->lastname, $ipn_data->last_name);
        $this->assertEqual($user->email, $ipn_data->payer_email);
        $this->assertEqual($user->city, $ipn_data->address_city);
        $this->assertEqual($user->country, $ipn_data->address_country_code);
        $this->assertEqual($user->confirmed, 0);
        $this->assertEqual($user->lang, current_language());
        $this->assertEqual($user->mnethostid, $CFG->mnet_localhost_id);
        $this->assertEqual($user->auth, 'email');
        $this->assertEqual($user->password, hash_internal_user_password('welcome'));
    }
    
    /**
     * Test basket voucher codes
     */
    function test_basket_vouchers() {
        
        // Single item baskets
        
        // 10% off 10 GBP = 9 GBP. 9 + 1.80 VAT = 10.80
        $basket = $this->single_item_basket(1, 1, '10%OFF');
        $this->assertEqual($basket->total, 10.80);
        $this->assertEqual($basket->discountamount, 1);
        
        // 5 GBP off 10 GBP = 5 GBP. 5 + 1 VAT = 6
        $basket = $this->single_item_basket(1, 1, '5GBPOFF');
        $this->assertEqual($basket->total, 6);
        $this->assertEqual($basket->discountamount, 5);
        
        // 10% off 50 GBP = 45 GBP. 45 + 9 VAT = 54
        $basket = $this->single_item_basket(1, 5, '10%OFF');
        $this->assertEqual($basket->total, 54);
        $this->assertEqual($basket->discountamount, 5);
        
        // 5 GBP off 50 GBP = 45 GBP. 45 + 9 VAT = 54
        $basket = $this->single_item_basket(1, 5, '5GBPOFF');
        $this->assertEqual($basket->total, 54);
        $this->assertEqual($basket->discountamount, 5);
        
        // 10% off 102 GBP = 10.20 GBP. 91.80 + 18.36 VAT = 110.16
        // Had to use strings to compare these floats!
        // Values were identical, but the test failed ...
        $basket = $this->single_item_basket(2, 3, '10%OFF');
        $this->assertEqual(''.$basket->total, '110.16');
        $this->assertEqual(''.$basket->discountamount, '10.20');
        
        // 5 GBP off 102 GBP = 97 GBP. 97 + 19.4 VAT = 116.40
        $basket = $this->single_item_basket(2, 3, '5GBPOFF');
        $this->assertEqual($basket->total, 116.40);
        $this->assertEqual($basket->discountamount, 5);
        
        // Multi item baskets
        
        // 10% off 88 GBP = 8.80 GBP. 79.20 + 15.84 VAT = 95.04
        $basket = $this->multi_item_basket(1, 2, 2, 2, '10%OFF');
        $this->assertEqual($basket->total, 95.04);
        $this->assertEqual($basket->discountamount, 8.80);
        
        // 5 GBP off 88 GBP = 83 GBP. 83 + 16.60 VAT = 99.60
        $basket = $this->multi_item_basket(1, 2, 2, 2, '5GBPOFF');
        $this->assertEqual($basket->total, 99.60);
        $this->assertEqual($basket->discountamount, 5);
        
        // 10% off 44 GBP = 4.40 GBP. 39.60 + 7.92 VAT = 47.52
        // Had to use strings to compare these floats!
        // Values were identical, but the test failed ...
        $basket = $this->multi_item_basket(1, 1, 2, 1, '10%OFF');
        $this->assertEqual(''.$basket->total, '47.52');
        $this->assertEqual(''.$basket->discountamount, '4.40');
        
        // 5 GBP off 44 GBP = 39 GBP. 39 + 7.80 VAT = 46.80
        $basket = $this->multi_item_basket(1, 1, 2, 1, '5GBPOFF');
        $this->assertEqual($basket->total, 46.80);
        $this->assertEqual($basket->discountamount, 5);
    }
    
    /**
     * Tiered pricing tests
     */
    function test_tiered_discounts() {
        
        // 5 off per item = 50 - 5
        $basket = $this->single_item_basket(3, 1);
        foreach($basket->items as $item) {
            $this->assertEqual($item->discount, 5);
            $this->assertEqual($item->linetotal, 45);
        }
        $this->assertEqual($basket->totaltax, 9);
        $this->assertEqual($basket->total, 54);
        
        // 7% off per item = (50 - 3.50) * 7
        $basket = $this->single_item_basket(3, 7);
        foreach($basket->items as $item) {
            $this->assertEqual($item->discount, 3.5);
            $this->assertEqual($item->linetotal, 325.50);
        }
        $this->assertEqual($basket->totaltax, 65.10);
        $this->assertEqual($basket->total, 390.60);
        
        // 5 off per item, plus 5 GBP voucher = 50 - 5 - 5
        $basket = $this->single_item_basket(3, 1, '5GBPOFF');
        foreach($basket->items as $item) {
            $this->assertEqual($item->discount, 5);
            $this->assertEqual($item->linetotal, 45);
        }
        $this->assertEqual($basket->totaltax, 8);
        $this->assertEqual($basket->total, 48);
        
        // 7% off per item, plus 5 GBP voucher = (50 - 3.50 * 7) - 5
        $basket = $this->single_item_basket(3, 7, '5GBPOFF');
        foreach($basket->items as $item) {
            $this->assertEqual($item->discount, 3.5);
            $this->assertEqual($item->linetotal, 325.50);
        }
        $this->assertEqual($basket->totaltax, 64.10);
        $this->assertEqual($basket->total, 384.60);
    }
}
?>