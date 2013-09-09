<?php
global $CFG;
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");
require_once($CFG->libdir . '/pagelib.php');
require_once($CFG->dirroot . "/blocks/shopping_basket/cart/shopping_cart.php");

global $PAGE, $CFG, $OUTPUT, $USER;

//require_login();

$PAGE->set_context(build_context_path()); 
$PAGE->set_url($CFG->wwwroot.'/blocks/shopping_cart/checkout.php');
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('checkout', 'block_shopping_basket'));
$PAGE->set_heading(get_string('checkout', 'block_shopping_basket'));

$jsconfig = array(
            'name' => 'block_shopping_basket',
            'fullpath' => '/blocks/shopping_basket/block_shopping_basket.js',
            'requires' => array('base','node','selector-css3','event','io', 'json-parse', 'panel'),
            'strings' => array(
                            array('licensepurchase_help_title', 'block_shopping_basket'),
                            array('licensepurchase_help_body1', 'block_shopping_basket'),
                            array('licensepurchase_help_body2', 'block_shopping_basket'),
                            array('licensepurchase_help_body3', 'block_shopping_basket')),
        );

$PAGE->requires->js_init_call('M.block_shopping_basket.init', array($USER->sesskey, true), true, $jsconfig);
        
$checkout_url = new moodle_url('/blocks/shopping_basket/checkout.php');
$PAGE->navbar->add(get_string('checkout', 'block_shopping_basket'), $checkout_url);
echo $OUTPUT->header();

echo html_writer::start_tag('div', array('class' => 'block_shopping_basket'));
echo html_writer::tag('div', ShoppingCart::display_basket(true), array('id' => 'basket_container'));
echo html_writer::end_tag('div');

echo $OUTPUT->footer();