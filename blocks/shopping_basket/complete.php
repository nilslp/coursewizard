<?php
global $CFG;
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");
require_once($CFG->libdir . '/pagelib.php');
require_once($CFG->dirroot . "/blocks/shopping_basket/cart/shopping_cart.php");

global $PAGE, $CFG, $OUTPUT, $USER;

//require_login();
$orderid = required_param('orderid', PARAM_INT);

// Clear the basket
ShoppingCart::empty_basket();

$PAGE->set_context(build_context_path()); 
$PAGE->set_url($CFG->wwwroot.'/blocks/shopping_basket/complete.php');
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('ordercomplete', 'block_shopping_basket'));
$PAGE->set_heading(get_string('ordercomplete', 'block_shopping_basket'));

$order = get_order($orderid);

$items = get_order_details($orderid);

$PAGE->navbar->add(get_string('ordercomplete', 'block_shopping_basket'));
echo $OUTPUT->header();

if ($order->userid != $USER->id) {
    // This is not the user who placed the order viewing    
    echo html_writer::tag('div', get_string('notyourorder', 'block_shopping_basket'));
    echo $OUTPUT->footer();
    
    add_to_log(1, 'shopping_basket', 'invalid order view', '', 'orderid = ' . $order->id, 0, $USER->id);
    die();
}

// Let the user know the status of the order
$status_class = 'order_status_pending';
$orderstatusstr = 'orderinprogress';
switch($order->payment_status) {
    case PAYMENT_STATUS_COMPLETED:
        if ($order->fulfilled==1) {
            $orderstatusstr = 'ordercomplete';
        }
        $paymentstatusstr = get_string('paymentcomplete', 'block_shopping_basket');
        $status_class = 'order_status_complete';
        break;
    case PAYMENT_STATUS_DECLINED:
        $orderstatusstr = 'orderfailed';
        $status_class = 'order_status_failed';
        $paymentstatusstr = get_string('paymentdeclined', 'block_shopping_basket');
        break;
    case PAYMENT_STATUS_INVALID:
        $orderstatusstr = 'orderfailed';
        $status_class = 'order_status_failed';
        $paymentstatusstr = get_string('paymentinvalid', 'block_shopping_basket');
        break;
    case PAYMENT_STATUS_PENDING:
        $paymentstatusstr = get_string('paymentpending', 'block_shopping_basket');
        break;
    case PAYMENT_STATUS_REJECTED:
        $orderstatusstr = 'orderfailed';
        $status_class = 'order_status_failed';
        $paymentstatusstr = get_string('paymentrejected', 'block_shopping_basket');
        break;
    case PAYMENT_STATUS_ERROR:
        $orderstatusstr = 'orderfailed';
        $status_class = 'order_status_failed';
        $paymentstatusstr = get_string('paymenterror', 'block_shopping_basket');
        break;
    case PAYMENT_STATUS_ABORTED:
        $orderstatusstr = 'orderfailed';
        $status_class = 'order_status_failed';
        $paymentstatusstr = get_string('paymentaborted', 'block_shopping_basket');
        break;
    default:
        $paymentstatusstr = get_string('paymentunknown', 'block_shopping_basket');
        break;
}

$headertext = ($orderstatusstr != 'orderfailed') ? 'thankyoumessage' : 'sorrymessage';
echo html_writer::tag('h2', get_string($headertext, 'block_shopping_basket'));

// Output some order/payment status information
$orderstatus = html_writer::tag('span', get_string($orderstatusstr, 'block_shopping_basket'), array('class' => $status_class));
echo html_writer::tag('h3', get_string('orderstatus', 'block_shopping_basket') . ':&nbsp;' . $orderstatus);
if($order->payment_status != PAYMENT_STATUS_COMPLETED) {
    echo html_writer::tag('h4', get_string('paymentstatus', 'block_shopping_basket'));
    echo html_writer::tag('p', $paymentstatusstr);
}

$table = new html_table();
$table->id = 'receipt';           
$table->head = array(get_string('quantity', 'block_shopping_basket'), get_string('item', 'block_shopping_basket'), get_string('price', 'block_shopping_basket'));           
$table->attributes = array('class' => 'receipt');

$data = array();

$coursestext = get_string('courses', 'block_shopping_basket');

// Render the basket items
foreach ($items as $item) {
    $product = get_product($item->productid);

    $coursestring = '<br />' . $coursestext . ':&nbsp;';
    $coursenames = array();

    foreach ($product->courses as $course) {
        $coursenames[] =  $course->fullname;
    }

    $coursestring .= implode(', ', $coursenames);
                                        
    $cells = array();
    $cells[] = new html_table_cell($item->quantity);
    $cells[] = new html_table_cell(html_writer::link(new moodle_url('/blocks/shopping_basket/view_product.php', array('id' => $item->productid)), $item->fullname) . $coursestring);                
    $cells[] = new html_table_cell(number_format($item->linetotal, 2));

    $row = new html_table_row($cells);          

    $data[] = $row;
}
            
if ($order->discount != 0) {
    // Discount
    $discountlabelcell = new html_table_cell(get_string('discount', 'block_shopping_basket'));           
    $discountlabelcell->colspan = 2;
    $discountcell = new html_table_cell(number_format('-' . $order->discount, 2));
    $discountrow = new html_table_row(array($discountlabelcell, $discountcell));
    $discountrow->id = 'discount_row';
    $data[] = $discountrow;
}

// Sub-total
$subtotallabelcell = new html_table_cell(get_string('subtotal', 'block_shopping_basket'));           
$subtotallabelcell->colspan = 2;
$subtotalcell = new html_table_cell(number_format($order->total - $order->tax, 2));
$subtotalrow = new html_table_row(array($subtotallabelcell, $subtotalcell));
$subtotalrow->id = 'subtotal_row';
$data[] = $subtotalrow;
            
// Tax
$taxlabelcell = new html_table_cell(get_string('tax', 'block_shopping_basket'));           
$taxlabelcell->colspan = 2;            
$taxcell = new html_table_cell(number_format($order->tax, 2));
$taxrow = new html_table_row(array($taxlabelcell, $taxcell));
$taxrow->id = 'tax_row';
$data[] = $taxrow;
            
// Total
$totallabelcell = new html_table_cell(get_string('total', 'block_shopping_basket') . '&nbsp;' . sprintf("(%s)", $order->currency));           
$totallabelcell->colspan = 2;            
$totalcell = new html_table_cell(number_format($order->total, 2));
$totalrow = new html_table_row(array($totallabelcell, $totalcell));
$totalrow->id = 'total_row';            
$data[] = $totalrow;

$table->data = $data;

echo html_writer::table($table);

echo $OUTPUT->single_button($CFG->wwwroot . '/index.php', get_string('continue'), 'GET');
echo $OUTPUT->footer();