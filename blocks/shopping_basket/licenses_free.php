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

// Get the query string
$id = required_param('id', PARAM_INT);
$lid = optional_param('lid', 0, PARAM_INT);
$r = optional_param('r', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$PAGE->set_context(build_context_path());
$PAGE->set_url($CFG->wwwroot . '/blocks/shopping_basket/licenses_free.php');
$freelicenses_url = new moodle_url('/blocks/shopping_basket/licenses_free.php', array('id' => $id));
// Define the page layout and header/breadcrumb
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('licensesfree', 'block_shopping_basket'));
$PAGE->set_heading(get_string('viewlicensemanager', 'block_shopping_basket'));

$viewlicensemanager_url = new moodle_url('/blocks/shopping_basket/license_manager.php');
$PAGE->navbar->add(get_string('viewlicensemanager', 'block_shopping_basket'), $viewlicensemanager_url);
$PAGE->navbar->add(get_string('licensesfree', 'block_shopping_basket'));

$jsconfig = array(
    'name' => 'block_shopping_basket_licenses_free',
    'fullpath' => '/blocks/shopping_basket/javascript/licenses_free.js',
    'requires' => array(
        'node',
        'event',
        'selector-css3',
        'event-hover',
        'panel',
        'io'
    ),
    'strings' => array(
        array('pleaseenteremail', 'block_shopping_basket'),
        array('emailsent', 'block_shopping_basket'),
        array('emailnotsent', 'block_shopping_basket'),
        array('sendinglicenseto', 'block_shopping_basket'),
        array('confirmlicensesend', 'block_shopping_basket')
    )
);

$PAGE->requires->js_init_call('M.block_shopping_basket_licenses_free.init', array($USER->sesskey, $CFG->wwwroot . '/blocks/shopping_basket/pix/ajax-loader.gif'), false, $jsconfig);

if ($r && $lid && $confirm == 0) {
    // Confirm regeneration of license key
    $optionsyes = array('sesskey' => sesskey(), 'id' => $id, 'lid' => $lid, 'r' => 1, 'confirm' => 1);
    echo $OUTPUT->header();
    
    echo $OUTPUT->confirm(get_string('licenseregenerateconfirm', 'block_shopping_basket'),
        new moodle_url('licenses_free.php', $optionsyes), $freelicenses_url);
    
    echo $OUTPUT->footer();
    die;
}

echo $OUTPUT->header();

if ($r && $lid && $confirm) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }
    
    // Deletion has been confirmed, perform a soft delete
    $regen = regenerate_license($lid);
    if($regen) {
        echo $OUTPUT->notification(get_string('licenseregenerated', 'block_shopping_basket'), 'notifysuccess');
    } else {
        echo $OUTPUT->notification(get_string('licenseregenerateerror', 'block_shopping_basket'), 'notifyproblem');
    }
}

$product = get_product($id);
$licenses = get_user_product_licenses($USER->id,$id,'free');

echo html_writer::tag('h2', get_string('viewlicensemanager', 'block_shopping_basket'));
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
echo html_writer::link(new moodle_url('/blocks/shopping_basket/licenses_free.php', array('id' => $id)), get_string('licensesfree', 'block_shopping_basket'), array('class' => 'active_section', 'title' => get_string('licensesfree', 'block_shopping_basket')));
echo html_writer::link(new moodle_url('/blocks/shopping_basket/licenses_used.php', array('id' => $id)), get_string('licensesused', 'block_shopping_basket'), array('class' => 'inactive_section', 'title' => get_string('licensesused', 'block_shopping_basket')));
echo html_writer::end_tag('div');

if ($licenses) {
    
    $table = new html_table();
    $table->id = 'licenses_table';
    $table->head = array(
        get_string('licensecode', 'block_shopping_basket'),
        get_string('licensestart', 'block_shopping_basket'),
        get_string('licenseend', 'block_shopping_basket'),
        get_string('options', 'block_shopping_basket'),
        get_string('assignlicense', 'block_shopping_basket')
    );
    $table->attributes = array('class' => 'licenses_table');

    $data = array();

    foreach ($licenses as $license) {
        $cells = array();
        
        $code_cell = new html_table_cell($license->code);
        $code_cell->attributes = array('class'=>'license');
        $cells[] = $code_cell;
        
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
        
        $cells[] = new html_table_cell(html_writer::tag('span',
                html_writer::link(new moodle_url('/blocks/shopping_basket/licenses_free.php', array('id' => $product->id, 'lid' => $license->id, 'r' => 1)), get_string('licenseregenerate', 'block_shopping_basket') . html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/reload')))),
                 array('class' => 'regeneratelink')));
        
        $cells[] = new html_table_cell(html_writer::empty_tag('input', array('type' => 'text', 'class' => 'email_field', 'placeholder' => get_string('email'), 'id' => 'email_' . $license->id)) . '&nbsp;' . html_writer::tag('span', '&nbsp;', array('class' => 'email_icon', 'id' => 'email_icon_' . $license->id))
                . html_writer::empty_tag('hidden', array('id' => 'code_' . $license->id, 'value' => $license->code)));
        
        $row = new html_table_row($cells);
        
        $data[] = $row;
    }
    
    $table->data = $data;
    
    echo html_writer::table($table);
    
    if ($product) {
        echo html_writer::start_tag('div', array('id' => 'export_to_excel'));
        echo html_writer::link(new moodle_url('/blocks/shopping_basket/licenses_export.php', array('id' => $product->id, 'type' => 'free')), get_string('licenseexport', 'block_shopping_basket') );
        echo html_writer::end_tag('div');
    }
}
else {
    echo html_writer::tag('p', get_string('nofreelicenses', 'block_shopping_basket'));
}

echo html_writer::end_tag('div');
echo html_writer::tag('div', null, array('id' => 'panel_container'));
echo $OUTPUT->footer();