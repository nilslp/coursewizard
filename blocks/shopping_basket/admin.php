<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once("admin_form.php");

global $OUTPUT, $PAGE;

require_capability('block/shopping_basket:managesettings', context_system::instance());

require_login();

$PAGE->set_context(get_system_context());

$cancelurl = $CFG->wwwroot . '/index.php';
$returnurl = $CFG->wwwroot . '/blocks/shopping_basket/admin.php';

$mform = new block_shopping_basket_admin_form();

if ($mform->is_cancelled()){
    // User clicked 'Cancel'
    redirect($cancelurl);
}

// Get the query string
$success = optional_param('s', '', PARAM_TEXT);

if ($fromform = $mform->get_data()) { 
    // Form submitted
    if (empty($fromform->submitbutton)) {        
        print_error('error:unknownbuttonclicked', 'block_shopping_basket', $returnurl);
    }
    $success = update_basket_settings($fromform);
    redirect($returnurl.'?s='.$success);
}
else {
    $settings = get_config('block_shopping_basket');
    $mform->set_data($settings);
}

// Define the page layout and header/breadcrumb
$PAGE->set_url($CFG->wwwroot . '/blocks/shopping_basket/admin.php');
$PAGE->set_pagelayout('base');
$PAGE->set_heading(get_string('settingbasketsettings', 'block_shopping_basket'));
$PAGE->set_title(get_string('settingbasketsettings', 'block_shopping_basket'));

// Build the breadcrumb
$PAGE->navbar->add(get_string('pluginname', 'block_shopping_basket'), new moodle_url('/blocks/shopping_basket/admin.php'));

echo $OUTPUT->header();

if ($success == '1') {
    echo $OUTPUT->notification(get_string('settingssaved', 'block_shopping_basket'), 'notifysuccess');
} elseif ($success == '0') {
    echo $OUTPUT->notification(get_string('settingsnotsaved', 'block_shopping_basket'), 'notifyproblem');
}

// Finally display the form
$mform->display();

echo $OUTPUT->footer();
?>