<?php
/**
* Unit tests for blocks/shopping_basket/cart/shopping_cart.php
*
* @author Brian Quinn <brian@learningpool.com>
*/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/shopping_basket/cart/shopping_cart.php');

class shopping_cart_test extends UnitTestCase {
    public static $includecoverage = array('blocks/shopping_basket/cart/shopping_cart.php');


    public function test_get_basket() {
        $this->assertNotNull(ShoppingCart::get_basket());
    }
    
    public function test_add_item_zero_quantity() {
        ShoppingCart::empty_basket();
        ShoppingCart::add_item(1, 0);
        
        $this->assertEqual(0, count(ShoppingCart::get_contents()));
    }  
}
