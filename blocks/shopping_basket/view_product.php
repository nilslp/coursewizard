<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/shopping_basket/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");
require_once("edit_product_form.php");

//require_login();

// Get the query string
$id = required_param('id', PARAM_INT);

// Get product
$product = get_product($id);

$returnurl = $CFG->wwwroot . '/blocks/shopping_basket/products.php';
$checkout_url = new moodle_url('/blocks/shopping_basket/checkout.php');

require_login();

$PAGE->set_context(get_system_context());

// Define the page layout and header/breadcrumb
$PAGE->set_url($CFG->wwwroot . '/blocks/shopping_basket/view_product.php', array('id' => $id));
$PAGE->set_pagelayout('base');

$PAGE->set_heading(get_string('viewproduct', 'block_shopping_basket'));
$PAGE->set_title(get_string('viewproduct', 'block_shopping_basket'));

// Build the breadcrumb
$referrer = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
if($referrer == $checkout_url) {
    $PAGE->navbar->add(get_string('checkout', 'block_shopping_basket'), $checkout_url);
} else {
    $PAGE->navbar->add(get_string('viewproducts', 'block_shopping_basket'));
}
$PAGE->navbar->add($product->fullname);

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
    
    echo html_writer::tag('dl', html_writer::tag('dt', get_string('tax', 'block_shopping_basket') . '&nbsp;(' . $percentage .'%)') . html_writer::tag('dd', number_format($tax, 2)));
}

echo html_writer::tag('dl', html_writer::tag('dt', get_string('duration', 'block_shopping_basket')) . html_writer::tag('dd', seconds_to_text($product->enrolperiod)));

$course_html = array();

foreach ($product->courses as $course) {
    $course_html[] = html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)), $course->fullname);
}

echo html_writer::tag('dl', html_writer::tag('dt', get_string('courses', 'block_shopping_basket')) . html_writer::tag('dd', implode(', ', $course_html)));

echo html_writer::end_tag('div');

echo $OUTPUT->footer();

?>