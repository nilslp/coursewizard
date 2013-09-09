<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/shopping_basket/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");
require_once("edit_product_form.php");

require_capability('block/shopping_basket:manageproducts', context_system::instance());

require_login();

$PAGE->set_context(get_system_context());

// Get the query string
$id = optional_param('id', 0, PARAM_INT);

$product = new object();
$product->id = 0;
$product->courseidlist = '';
$product->categoryidlist = '';
$product->hascategory = 0;

if ($id != 0) {
    // Get product
    $product = get_product($id);
}

$returnurl = $CFG->wwwroot . '/blocks/shopping_basket/products.php';

$mform  = new edit_product_form(null, array('product' => $product));

if ($mform->is_cancelled()){
    // User clicked 'Cancel'
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) { 
    // Form submitted
    if (empty($fromform->submitbutton)) {        
        print_error('error:unknownbuttonclicked', 'block_shopping_basket', $returnurl);
    }
    
    if ($fromform->id == 0) {
        // Add product
        add_product($fromform);
    }
    else {
        // Update an existing product
        update_product($fromform);
    }
    
    redirect($returnurl);
}
else {
    $mform->set_data($product);
}

// Define the page layout and header/breadcrumb
$PAGE->set_url($CFG->wwwroot . '/blocks/shopping_basket/edit_product.php', array('id' => $id));
$PAGE->set_pagelayout('base');

if ($id == 0) {
    $PAGE->set_heading(get_string('addproduct', 'block_shopping_basket'));
    $PAGE->set_title(get_string('addproduct', 'block_shopping_basket'));
}
else {
    $PAGE->set_heading(get_string('editproduct', 'block_shopping_basket'));
    $PAGE->set_title(get_string('editproduct', 'block_shopping_basket'));
}

// Build the breadcrumb
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_shopping_basket'), get_settings_url());
$viewproducts_url = new moodle_url('/blocks/shopping_basket/products.php');
$PAGE->navbar->add(get_string('viewproducts', 'block_shopping_basket'), $viewproducts_url);
$PAGE->navbar->add($PAGE->heading);

$jsconfig = array(
    'name' => 'block_shopping_basket_edit_product',
    'fullpath' => '/blocks/shopping_basket/javascript/edit_product.js',
    'requires' => array(
        'node',
        'event',
        'selector-css3',
        'event-hover'
    )
);

$PAGE->requires->js_init_call('M.block_shopping_basket_edit_product.init', array($CFG->wwwroot . '/blocks/shopping_basket/pix/delete.gif', $product->courseidlist, $product->categoryidlist), false, $jsconfig);

echo $OUTPUT->header();

// Finally display the form
$mform->display();

echo $OUTPUT->footer();
?>