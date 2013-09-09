<?php
global $CFG;
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/shopping_basket/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");

require_capability('block/shopping_basket:managevouchers', context_system::instance());

require_login();

$id = optional_param('id', 0, PARAM_INT);
$d = optional_param('d', 0, PARAM_INT);
$voucher_deleted = false;

$PAGE->set_context(get_system_context());

// Define the page layout and header/breadcrumb
$PAGE->set_url($CFG->wwwroot . '/blocks/shopping_basket/vouchers.php');
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('discountvouchers', 'block_shopping_basket'));
$PAGE->set_heading(get_string('discountvouchers', 'block_shopping_basket'));

// Build the breadcrumb
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_shopping_basket'), get_settings_url());
$PAGE->navbar->add(get_string('discountvouchers', 'block_shopping_basket'));

if ($id != 0 && $d == 1) {
    $voucher_deleted = delete_voucher($id);
}

$discountsenabled = get_config('block_shopping_basket', 'enablediscounts');

$vouchers = get_vouchers();

if ($vouchers) {
    $table = new html_table();
    $table->id = 'voucher_table';
            
    $table->head = array(
        get_string('vouchercode', 'block_shopping_basket'), 
        get_string('amountorpercentage', 'block_shopping_basket'), 
        get_string('minorder', 'block_shopping_basket'),
        get_string('expires', 'block_shopping_basket'),
        get_string('use', 'block_shopping_basket'),
        get_string('options', 'block_shopping_basket'));
    
    $table->attributes = array('class' => 'voucher_table');

    $data = array();
    
    $string_once = get_string('once', 'block_shopping_basket');
    $string_multiple = get_string('multiple', 'block_shopping_basket');
    
    foreach ($vouchers as $voucher) {
        $expired = $voucher->expired || isset($voucher->expirydate) && $voucher->expirydate < time();
        $cells = array();
        
        $cells[] = new html_table_cell($voucher->discountcode);
        $voucher_symbol = ($voucher->discounttype == DISCOUNT_TYPE_PERCENTAGE) ? '%' : ('&nbsp;'.get_config('block_shopping_basket', 'currency'));
        $cells[] = new html_table_cell($voucher->rate . $voucher_symbol);
        $cells[] = new html_table_cell($voucher->minordervalue);
        $cells[] = new html_table_cell(isset($voucher->expirydate) ? userdate($voucher->expirydate) : '');
        $cells[] = new html_table_cell($voucher->singleuse ? $string_once : $string_multiple);
        
        if (!$expired) {
            $cells[] = new html_table_cell(html_writer::link(new moodle_url('/blocks/shopping_basket/edit_voucher.php', array('id' => $voucher->id)), get_string('edit')) . '&nbsp;|&nbsp;' . 
                html_writer::link(new moodle_url('/blocks/shopping_basket/vouchers.php', array('id' => $voucher->id, 'd' => 1)), get_string('delete')));
        }
        else {
            $cells[] = new html_table_cell(html_writer::link(new moodle_url('/blocks/shopping_basket/vouchers.php', array('id' => $voucher->id, 'd' => 1)), get_string('delete')));            
        }
        
        $row = new html_table_row($cells);
        
        if ($expired) {
            $row->style = 'text-decoration:line-through';
        }
        
        $data[] = $row;        
    }
    
    $table->data = $data;
}
else {
    // No vouchers have yet been defined
    echo html_writer::tag('p', get_string('novoucherssetup', 'block_shopping_basket'));
}

echo $OUTPUT->header();

if ($voucher_deleted) {
    echo $OUTPUT->notification(get_string('voucherdeleted', 'block_shopping_basket'), 'notifysuccess');
}

if (!$discountsenabled) {
    echo html_writer::tag('div', get_string('discountsnotenabled', 'block_shopping_basket'));
    echo html_writer::empty_tag('br');
}

if ($vouchers) {
    echo html_writer::table($table);
}

echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/shopping_basket/edit_voucher.php', get_string('addnewvoucher', 'block_shopping_basket'), 'GET');
echo $OUTPUT->footer();
