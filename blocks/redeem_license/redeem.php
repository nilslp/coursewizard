<?php
global $CFG;
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");
require_once($CFG->libdir . '/pagelib.php');
require_once($CFG->dirroot . '/blocks/redeem_license/lib.php');
require_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');

global $OUTPUT;

require_login(null,false);

if (isguestuser()) {
    // Prevent Guest users from seeing this page
    print_error('redeemlicenselogin', 'block_redeem_license', get_login_url());
    die;
}

$licensekey = required_param('license_code', PARAM_TEXT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
// Referrer is posted through when 'Redeem' is clicked - we'd lose where we were
// referred from otherwise
$posted_referrer = optional_param('referral_url', $CFG->wwwroot, PARAM_URL);
// Keep track of the previous page
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $CFG->wwwroot;
$validationmessage = validate_license($licensekey);

$PAGE->set_context(build_context_path()); 
$PAGE->set_url($CFG->wwwroot.'/blocks/redeem_license/redeem.php');

// Define the page layout and header/breadcrumb
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('redeemlicense', 'block_redeem_license'));
$PAGE->set_heading(get_string('redeemlicense', 'block_redeem_license'));

if ($confirm) {
    redeem_license($licensekey);
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('licenceapplied', 'block_redeem_license'), 'notifysuccess');
    echo $OUTPUT->single_button($posted_referrer, get_string('continue'), 'get');
    echo $OUTPUT->footer();
    die;
}

echo $OUTPUT->header();

echo html_writer::tag('span', get_string('youhaveentered', 'block_redeem_license'));
echo html_writer::empty_tag('br');
echo html_writer::tag('span', $licensekey, array('id' => 'license_key'));

if (!empty($validationmessage)) {
    echo html_writer::tag('div', $validationmessage);
}
else {
    // Get and display product information
    $product = get_product_from_license($licensekey);
    
    echo html_writer::start_tag('div', array('id' => 'product_view'));

    echo html_writer::tag('dl', html_writer::tag('dt', get_string('productname', 'block_shopping_basket')) . html_writer::tag('dd', $product->fullname));

    if (!empty($product->description)) {
        echo html_writer::tag('dl', html_writer::tag('dt', get_string('description')) . html_writer::tag('dd', $product->description));
    }

    echo html_writer::tag('dl', html_writer::tag('dt', get_string('duration', 'block_shopping_basket')) . html_writer::tag('dd', seconds_to_text($product->enrolperiod)));

    $course_html = array();

    foreach ($product->courses as $course) {
        $course_html[] = html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)), $course->fullname);
    }

    echo html_writer::tag('dl', html_writer::tag('dt', get_string('courses', 'block_shopping_basket')) . html_writer::tag('dd', implode(', ', $course_html)));

    echo html_writer::end_tag('div');
    
    echo html_writer::start_tag('span', array('class' => 'redeem-submit-btn'));
    echo html_writer::start_tag('form', array('action' => $CFG->wwwroot . '/blocks/redeem_license/redeem.php', 'method' => 'post'));
    echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('redeem', 'block_redeem_license')));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'id' => 'confirm', 'name' => 'confirm', 'value' => 1));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'id' => 'license_code', 'name' => 'license_code', 'value' => $licensekey));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'referral_url', 'value' => $referrer));
    echo html_writer::end_tag('form');
    echo html_writer::end_tag('span');
}
echo html_writer::start_tag('span', array('class' => 'redeem-cancel-btn'));
echo $OUTPUT->single_button($referrer, get_string('cancel'), 'GET');
echo html_writer::end_tag('span');
echo $OUTPUT->footer();