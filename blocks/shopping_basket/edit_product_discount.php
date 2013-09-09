<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/shopping_basket/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");
require_once("edit_product_discount_form.php");

require_login();

$PAGE->set_context(get_system_context());

// Get the query string
$pid = required_param('pid', PARAM_INT);

$product = new object();
$product_discounts = false;
$product->id = 0;

if ($pid != 0) {
    // Get product details
    $product = get_product($pid);
    $product_discounts = get_product_discounts($pid);
}

$returnurl = $CFG->wwwroot . '/blocks/shopping_basket/products.php';

$mform  = new edit_product_discount_form(null, array('product' => $product));

if ($mform->is_cancelled()){
    // User clicked 'Cancel'
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) {
    
    // Form submitted
    if (empty($fromform->submitbutton)) {        
        print_error('error:unknownbuttonclicked', 'block_shopping_basket', $returnurl);
    }
    
    $discounts = array();
    foreach ( $fromform->product_discount_group as $id => $discount_group ) {
        if( $discount_group['delete'] == 1 && $discount_group['id'] == 0 ) {
            // ignore deletions where no record exists
            continue;
        } 
        $discount = new stdClass();
        $discount->id = $discount_group['id'];
        $discount->productid = $discount_group['productid'];
        $discount->min = $discount_group['min'];
        $discount->max = $discount_group['max'];
        $discount->rate = $discount_group['rate'];
        $discount->criteria = $discount_group['criteria'];
        $discount->discounttype = TIER_TYPE_QUANTITY; // Hardcoded for now
        $discount->delete = $discount_group['delete'];
        $discounts[] = $discount;
    }
    
    update_product_discounts($discounts);
    
    redirect($returnurl);
} else {
    
    // Edit mode
    // Set values for the form
    $toform = new stdClass();
    $toform->product_discount_group = array();
    
    if ($product_discounts) {
        $i = 0;
        foreach ($product_discounts as $discount) {
            $toform->product_discount_group[$i] = array();
            $toform->product_discount_group[$i]['id'] = $discount->id;
            $toform->product_discount_group[$i]['min'] = $discount->min;
            $toform->product_discount_group[$i]['max'] = $discount->max;
            $toform->product_discount_group[$i]['rate'] = $discount->rate;
            $toform->product_discount_group[$i]['criteria'] = $discount->criteria;
            $toform->product_discount_group[$i]['discountype'] = TIER_TYPE_QUANTITY; // Hardcoded for now
            $toform->product_discount_group[$i]['delete'] = 0;
            $i++;
        }
    }
    $mform->set_data($toform);
    
}

// Define the page layout and header/breadcrumb
$PAGE->set_url($CFG->wwwroot . '/blocks/shopping_basket/edit_product_discount.php', array('pid' => $pid));
$PAGE->set_pagelayout('base');

$PAGE->set_heading(get_string('editproductdiscount', 'block_shopping_basket'));
$PAGE->set_title(get_string('editproductdiscount', 'block_shopping_basket'));

// Build the breadcrumb
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_shopping_basket'), get_settings_url());
$PAGE->navbar->add(get_string('viewproducts', 'block_shopping_basket'), new moodle_url('/blocks/shopping_basket/products.php'));
$PAGE->navbar->add($PAGE->heading);

echo $OUTPUT->header();

echo html_writer::start_tag('div', array('id' => 'product_view'));

echo html_writer::tag('dl', html_writer::tag('dt', get_string('productname', 'block_shopping_basket')) . html_writer::tag('dd', $product->fullname));
echo html_writer::tag('dl', html_writer::tag('dt', get_string('itemcode', 'block_shopping_basket')) . html_writer::tag('dd', $product->itemcode));

if (!empty($product->description)) {
    echo html_writer::tag('dl', html_writer::tag('dt', get_string('description')) . html_writer::tag('dd', $product->description));
}

echo html_writer::tag('dl', html_writer::tag('dt', get_string('price', 'block_shopping_basket')) . html_writer::tag('dd', $product->cost));

if ($product->tax != 0) {
    $percentage = $product->tax * 100;
    $tax = $product->cost * $product->tax;
    
    echo html_writer::tag('dl', html_writer::tag('dt', get_string('taxrate', 'block_shopping_basket') . '&nbsp;(' . $percentage .'%)') . html_writer::tag('dd', number_format($tax, 2)));
}

echo html_writer::tag('dl', html_writer::tag('dt', get_string('duration', 'block_shopping_basket')) . html_writer::tag('dd', seconds_to_text($product->enrolperiod)));

// Finally display the form
$mform->display();

echo $OUTPUT->footer();
?>
