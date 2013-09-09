<?php
global $CFG;
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/shopping_basket/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");

global $OUTPUT, $PAGE;
require_capability('block/shopping_basket:managepurchaseorders', context_system::instance());

require_login();

$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_context(get_system_context());

// Define the page layout and header/breadcrumb
$PAGE->set_url($CFG->wwwroot . '/blocks/shopping_basket/purchase_orders.php');
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('purchaseorders', 'block_shopping_basket'));
$PAGE->set_heading(get_string('pendingpurchaseorders', 'block_shopping_basket'));

// Build the breadcrumb
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_shopping_basket'), get_settings_url());
$PAGE->navbar->add(get_string('purchaseorders', 'block_shopping_basket'));

$orders = get_pending_purchase_orders();

if ($orders) {
    $table = new html_table();
    $table->id = 'po_table';
            
    $table->head = array(
        get_string('ponumber', 'block_shopping_basket'), 
        get_string('ordernumber', 'block_shopping_basket'), 
        get_string('total', 'block_shopping_basket'),
        get_string('currency', 'block_shopping_basket'),
        get_string('user'),
        get_string('date'));
    
    $table->attributes = array('class' => 'po_table');

    $data = array();
        
    foreach ($orders as $order) {
        $cells = array();
        
        $cells[] = new html_table_cell($order->po_number);
        $cells[] = new html_table_cell($order->id);
        $cells[] = new html_table_cell($order->total);
        $cells[] = new html_table_cell($order->currency);
        $cells[] = new html_table_cell(html_writer::link(new moodle_url('/user/profile.php', array('id' => $order->userid)), $order->firstname . ' ' . $order->lastname));
        $cells[] = new html_table_cell(userdate($order->timecreated));
        
        $row = new html_table_row($cells);
        
        $data[] = $row;
    }
    
    $table->data = $data;
}

echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('purchaseorders', 'block_shopping_basket'));

echo html_writer::start_tag('div', array('class' => 'section_header'));
echo html_writer::link(new moodle_url('/blocks/shopping_basket/purchase_orders.php'), get_string('pendingpurchaseorders', 'block_shopping_basket'), array('class' => 'active_section', 'title' => get_string('pendingpurchaseorders', 'block_shopping_basket')));
echo html_writer::link(new moodle_url('/blocks/shopping_basket/purchase_orders_completed.php'), get_string('completedpurchaseorders', 'block_shopping_basket'), array('class' => 'inactive_section', 'title' => get_string('completedpurchaseorders', 'block_shopping_basket')));
echo html_writer::end_tag('div');

if ($orders) {
    echo html_writer::table($table);
}
else {
    echo $OUTPUT->box(get_string('nopurchaseorderstodisplay', 'block_shopping_basket'));
}

echo $OUTPUT->footer();