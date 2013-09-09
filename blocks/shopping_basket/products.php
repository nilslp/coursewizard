<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");

global $USER, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT);
$d = optional_param('d', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
require_capability('block/shopping_basket:manageproducts', context_system::instance());

require_login();

$PAGE->set_context(build_context_path());
$PAGE->set_url($CFG->wwwroot . '/blocks/shopping_basket/products.php');

// Define the page layout and header/breadcrumb
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('viewproducts', 'block_shopping_basket'));
$PAGE->set_heading(get_string('viewproducts', 'block_shopping_basket'));

$viewproducts_url = new moodle_url('/blocks/shopping_basket/products.php');
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_shopping_basket'), get_settings_url());
$PAGE->navbar->add(get_string('viewproducts', 'block_shopping_basket'));

$jsconfig = array(
    'name' => 'block_shopping_basket_products',
    'fullpath' => '/blocks/shopping_basket/javascript/products.js',
    'requires' => array(
        'node',
        'event',
        'selector-css3',
        'event-hover',
        'panel',
        'io'
    ),
    'strings' => array(
        array('loading', 'block_shopping_basket'),
        array('copythehtml', 'block_shopping_basket'),
        array('close', 'block_shopping_basket')
    )
);

$PAGE->requires->js_init_call('M.block_shopping_basket_products.init', array($USER->sesskey), false, $jsconfig);

if ($d && $id && $confirm == 0) {
    // Confirm deletion of the product
    $product = get_product($id);
    
    $optionsyes = array('sesskey' => sesskey(), 'id' => $id, 'd' => 1, 'confirm' => 1);
    echo $OUTPUT->header();
    
    echo $OUTPUT->confirm(get_string('deleteproductconfirm', 'block_shopping_basket', $product->fullname),
        new moodle_url('products.php', $optionsyes), $viewproducts_url);
    
    echo $OUTPUT->footer();
    die;
}

echo $OUTPUT->header();

if ($d && $id && $confirm) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }
    
    // Deletion has been confirmed, perform a soft delete
    delete_product($id);
    
    echo $OUTPUT->notification(get_string('productdeleted', 'block_shopping_basket'), 'notifysuccess');
}

$products = get_products();

if ($products) {
    $table = new html_table();
    $table->id = 'product_table';
            
    $table->head = array(
        get_string('itemcode', 'block_shopping_basket'), 
        get_string('productname', 'block_shopping_basket'), 
        get_string('html', 'block_shopping_basket'),
        get_string('options', 'block_shopping_basket'),
        );
    $table->attributes = array('class' => 'product_table');

    $data = array();

    foreach ($products as $product) {
        $cells = array();
        
        $cells[] = new html_table_cell(html_writer::link(new moodle_url('/blocks/shopping_basket/edit_product.php', array('id' => $product->id)), $product->itemcode));
        $cells[] = new html_table_cell($product->fullname);
        $cells[] = new html_table_cell(html_writer::empty_tag('input', array('type' => 'button', 'id' => 'get_product_' . $product->id, 'value' => get_string('clickhere', 'block_shopping_basket'))));
        $cells[] = new html_table_cell(html_writer::link(new moodle_url('/blocks/shopping_basket/edit_product.php', array('id' => $product->id)), get_string('edit'))
                . '&nbsp;|&nbsp'
                . html_writer::link(new moodle_url('/blocks/shopping_basket/edit_product_discount.php', array('pid' => $product->id)), get_string('editproductdiscount', 'block_shopping_basket'))
                . '&nbsp;|&nbsp'
                . html_writer::link(new moodle_url('/blocks/shopping_basket/products.php', array('id' => $product->id, 'd' => 1)), get_string('delete')));
        
        $row = new html_table_row($cells);
        
        $data[] = $row;
    }
    
    $table->data = $data;
            
    echo html_writer::table($table);
}
else {
    // No products have yet been defined
    echo html_writer::tag('p', get_string('noproductssetup', 'block_shopping_basket'));
}

// Add new product link
echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/shopping_basket/edit_product.php', get_string('addproduct', 'block_shopping_basket'), 'GET');

echo $OUTPUT->footer();