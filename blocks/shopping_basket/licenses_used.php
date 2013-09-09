<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");

global $USER, $OUTPUT, $PAGE;

require_login();

if ($USER->username == 'guest') {
    // Prevent Guest users from seeing this page
    print_error('accessdenied', 'admin');
    die;
}

$PAGE->set_context(build_context_path());
$PAGE->set_url($CFG->wwwroot . '/blocks/shopping_basket/licenses_used.php');

// Define the page layout and header/breadcrumb
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('licensesused', 'block_shopping_basket'));
$PAGE->set_heading(get_string('viewlicensemanager', 'block_shopping_basket'));

$viewlicensemanager_url = new moodle_url('/blocks/shopping_basket/license_manager.php');
$PAGE->navbar->add(get_string('viewlicensemanager', 'block_shopping_basket'), $viewlicensemanager_url);
$PAGE->navbar->add(get_string('licensesused', 'block_shopping_basket'));

echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('viewlicensemanager', 'block_shopping_basket'));

// Get the query string
$id = required_param('id', PARAM_INT);
$licenses = get_user_product_licenses($USER->id,$id,'used');
$product = get_product($id);

echo html_writer::start_tag('div', array('class' => "group yui3-u-1-3"));
echo html_writer::tag('div', html_writer::tag('span', get_string('licenseproduct', 'block_shopping_basket'), array('class' => 'active_section')), array('class' => 'section_header'));

if ($product) {
    $i = 0;
    echo html_writer::start_tag('div', array('id' => 'product_view_vertical'));

    echo html_writer::tag('dl', html_writer::tag('dt', get_string('productname', 'block_shopping_basket')) . html_writer::tag('dd', $product->fullname), array('class' => "r" . $i++ % 2));

    echo html_writer::tag('dl', html_writer::tag('dt', get_string('itemcode', 'block_shopping_basket')) . html_writer::tag('dd', $product->itemcode), array('class' => "r" . $i++ % 2));

    if (!empty($product->description)) {
        echo html_writer::tag('dl', html_writer::tag('dt', get_string('description')) . html_writer::tag('dd', $product->description), array('class' => "r" . $i++ % 2));
    }

    echo html_writer::tag('dl', html_writer::tag('dt', get_string('price', 'block_shopping_basket')) . html_writer::tag('dd', $product->cost), array('class' => "r" . $i++ % 2));

    if ($product->tax != 0) {
        $percentage = $product->tax * 100;
        $tax = $product->cost * $product->tax;

        echo html_writer::tag('dl', html_writer::tag('dt', get_string('tax', 'block_shopping_basket') . '&nbsp;(' . $percentage .'%)') . html_writer::tag('dd', number_format($tax, 2)), array('class' => "r" . $i++ % 2));
    }

    echo html_writer::tag('dl', html_writer::tag('dt', get_string('duration', 'block_shopping_basket')) . html_writer::tag('dd', seconds_to_text($product->enrolperiod)), array('class' => "r" . $i++ % 2));

    $course_html = array();

    foreach ($product->courses as $course) {
        $course_html[] = html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)), $course->fullname);
    }

    echo html_writer::tag('dl', html_writer::tag('dt', get_string('courses', 'block_shopping_basket')) . html_writer::tag('dd', implode('<br />', $course_html)), array('class' => "r" . $i++ % 2));

    echo html_writer::end_tag('div');
}

echo html_writer::end_tag('div');
echo html_writer::start_tag('div', array('class' => "group yui3-u-2-3"));

echo html_writer::start_tag('div', array('class' => 'section_header'));
echo html_writer::link(new moodle_url('/blocks/shopping_basket/licenses_free.php', array('id' => $id)), get_string('licensesfree', 'block_shopping_basket'), array('class' => 'inactive_section', 'title' => get_string('licensesfree', 'block_shopping_basket')));
echo html_writer::link(new moodle_url('/blocks/shopping_basket/licenses_used.php', array('id' => $id)), get_string('licensesused', 'block_shopping_basket'), array('class' => 'active_section', 'title' => get_string('licensesused', 'block_shopping_basket')));
echo html_writer::end_tag('div');

if ($licenses) {
    
    $table = new html_table();
    $table->id = 'licenses_table';
    $table->head = array(
        get_string('licensecode', 'block_shopping_basket'),
        get_string('licenseuser', 'block_shopping_basket'),
        get_string('licensestart', 'block_shopping_basket'),
        get_string('licenseend', 'block_shopping_basket'),
    );
    $table->attributes = array('class' => 'licenses_table');

    $data = array();

    foreach ($licenses as $license) {
        $cells = array();
        
        $code_cell = new html_table_cell($license->code);
        $code_cell->attributes = array('class'=>'license');
        $cells[] = $code_cell;
        $cells[] = new html_table_cell($license->username.'<br/>'.obfuscate_mailto($license->useremail));
        
        if ($license->startdate) {
            $cells[] = new html_table_cell(userdate($license->startdate));
        }
        else {
            $cells[] = new html_table_cell('-');
        }
        
        if ($license->enddate) {
            $cells[] = new html_table_cell(userdate($license->enddate));
        }
        else {
            $cells[] = new html_table_cell('-');
        }
        
        $row = new html_table_row($cells);
        
        $data[] = $row;
    }
    
    $table->data = $data;
    
    echo html_writer::table($table);
    
    if ($product) {
        echo html_writer::start_tag('div', array('id' => 'export_to_excel'));
        echo html_writer::link(new moodle_url('/blocks/shopping_basket/licenses_export.php', array('id' => $product->id, 'type' => 'used')), get_string('licenseexport', 'block_shopping_basket') );
        echo html_writer::end_tag('div');
    }
}
else {
    echo html_writer::tag('p', get_string('nousedlicenses', 'block_shopping_basket'));
}

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
