<?php
global $CFG;
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/shopping_basket/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");
require_once("edit_voucher_form.php");

require_capability('block/shopping_basket:managevouchers', context_system::instance());

require_login();

$PAGE->set_context(get_system_context());

// Get the query string
$id = optional_param('id', 0, PARAM_INT);

$voucher = new object();
$voucher->id = 0;

if ($id != 0) {
    // Get voucher
    $voucher = get_voucher($id);
}

$returnurl = $CFG->wwwroot . '/blocks/shopping_basket/vouchers.php';

$mform = new block_shopping_basket_edit_voucher_form(null, compact('id'));

if ($mform->is_cancelled()){
    // User clicked 'Cancel'
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) { 
    // Form submitted
    if (empty($fromform->submitbutton)) {        
        print_error('error:unknownbuttonclicked', 'block_shopping_basket', $returnurl);
    }

    $voucher->discountcode = $fromform->discountcode;
    $voucher->discounttype = $fromform->discounttype;
    $voucher->rate = $fromform->rate;
    $voucher->minordervalue = $fromform->minordervalue;
    $voucher->expirydate = isset($fromform->enableexpiry) ? $fromform->expirydate : null;
    $voucher->singleuse = !empty($fromform->singleuse) ? 1 : 0;
    $voucher->maxval = isset($fromform->maxval) ? 1 : 0;

    if ($fromform->id == 0) {
        // Add voucher
        add_voucher($voucher);
    }
    else {
        // Update an existing voucher
        update_voucher($voucher);
    }
    
    redirect($returnurl);
}
else {
    $mform->set_data($voucher);
}

// Define the page layout and header/breadcrumb
$PAGE->set_url($CFG->wwwroot . '/blocks/shopping_basket/edit_voucher.php', array('id' => $id));
$PAGE->set_pagelayout('base');

if ($id == 0) {
    $PAGE->set_heading(get_string('addnewvoucher', 'block_shopping_basket'));
    $PAGE->set_title(get_string('addnewvoucher', 'block_shopping_basket'));
}
else {
    $PAGE->set_heading(get_string('editvoucher', 'block_shopping_basket'));
    $PAGE->set_title(get_string('editvoucher', 'block_shopping_basket'));
}

// Build the breadcrumb
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_shopping_basket'), get_settings_url());
$PAGE->navbar->add(get_string('discountvouchers', 'block_shopping_basket'), new moodle_url('/blocks/shopping_basket/vouchers.php'));
$PAGE->navbar->add($PAGE->heading);

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();