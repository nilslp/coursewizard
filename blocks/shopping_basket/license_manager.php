<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");

global $USER, $OUTPUT, $PAGE;

require_login(null,false);

if ($USER->username == 'guest') {
    // Prevent Guest users from seeing this page
    print_error('licensemanagerlogin', 'block_shopping_basket', get_login_url());
    die;
}

$PAGE->set_context(build_context_path());
$PAGE->set_url($CFG->wwwroot . '/blocks/shopping_basket/license_manager.php');

// Define the page layout and header/breadcrumb
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('viewlicensemanager', 'block_shopping_basket'));
$PAGE->set_heading(get_string('viewlicensemanager', 'block_shopping_basket'));
$PAGE->navbar->add(get_string('viewlicensemanager', 'block_shopping_basket'));

echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('viewlicensemanager', 'block_shopping_basket'));

$context = get_context_instance(CONTEXT_SYSTEM);

if (has_capability('block/lp_reportbuilder:viewreports', $context)) {
    // Display a link to the 'My Reports' page
    echo html_writer::tag('div', 
            get_string('toviewreports', 'block_shopping_basket', html_writer::link(new moodle_url('/blocks/lp_reportbuilder/myreports.php'), ucfirst(get_string('clickhere', 'block_shopping_basket')))), 
            array('class' => 'helpertext'));
}

$products_with_licenses = get_user_license_overview($USER->id);

if ($products_with_licenses) {

    echo html_writer::tag('div', get_string('licensemanagerblurbtext', 'block_shopping_basket'), array('class' => 'helpertext'));

    $table = new html_table();
    $table->id = 'licenses_table';
    $table->head = array(
        get_string('productname', 'block_shopping_basket'), 
        get_string('licensesfree', 'block_shopping_basket'),
        get_string('licensesused', 'block_shopping_basket')
    );
    $table->attributes = array('class' => 'licenses_table');

    $data = array();

    foreach ($products_with_licenses as $product) {
        $cells = array();
        
        $cells[] = new html_table_cell(html_writer::link(new moodle_url('/blocks/shopping_basket/licenses_free.php', array('id' => $product->productid)), $product->productname . html_writer::tag('span', '&gt', array('class' => 'arrow')) ));  
        $cells[] = new html_table_cell(
            html_writer::link(new moodle_url('/blocks/shopping_basket/licenses_free.php', array('id' => $product->productid)), $product->total - $product->used)
        );
        $cells[] = new html_table_cell(
            html_writer::link(new moodle_url('/blocks/shopping_basket/licenses_used.php', array('id' => $product->productid)), $product->used)
        );
        
        $row = new html_table_row($cells);
        
        $data[] = $row;
    }
    
    $table->data = $data;
    
    echo html_writer::table($table);
}
else {
    echo html_writer::tag('p', get_string('nolicenses', 'block_shopping_basket'));
}

echo $OUTPUT->footer();
