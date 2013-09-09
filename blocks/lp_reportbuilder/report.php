<?php

/**
 * Page for displaying user generated reports
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
global $CFG,$OUTPUT,$PAGE, $SESSION, $SITE;
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/lib.php');

$makerequest = (isset($_REQUEST['addfilter']) || isset($_REQUEST['sid']) ? true : false);
$format    = optional_param('format', '', PARAM_TEXT);
$id = required_param('id',PARAM_INT);
$sid = optional_param('sid', '0', PARAM_INT);
$debug = optional_param('debug', 0, PARAM_INT);

require_login();

// Define the page layout and header/breadcrumb
$PAGE->set_url($CFG->wwwroot . '/blocks/lp_reportbuilder/reports/course_completions.php');
$PAGE->set_pagelayout('report');
$PAGE->set_context(get_context_instance_by_id(1, IGNORE_MISSING));

// new report object
$report = new reportbuilder($id, null, false, $sid);

if (!$report->is_capable($id)) {
    print_error(get_string('nopermission','block_lp_reportbuilder'));
}

if ($report->embeddedurl !== null && !isset($_SERVER['QUERY_STRING'])) {
    $querystring = '?' . $_SERVER['QUERY_STRING'];
    
    // Redirect to embedded url
    redirect($CFG->wwwroot . $report->embeddedurl . $querystring);
}

// Export the report if the button has been set
if($format != '') {
    add_to_log(SITEID, 'reportbuilder', 'export report', 'report.php?id='.$id, $report->fullname);
    $report->export_data($format);
    die;
}

add_to_log(SITEID, 'reportbuilder', 'view report', 'report.php?id='.$id, $report->fullname);

$PAGE->set_context(build_context_path());	

$report->include_js();
$report->include_css();

// Define the page layout and header/breadcrumb
$PAGE->set_pagelayout('report');
$PAGE->navbar->add(get_string('myreports','block_lp_reportbuilder'), "$CFG->wwwroot/blocks/lp_reportbuilder/myreports.php");
$PAGE->navbar->add($report->fullname);

$filter_session_name = $report->_filtering->_sessionname;

 if (!empty($SESSION->$filter_session_name)) {
    $filtering = true; 
 }
 else {
    $filtering = false; 
 }

$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($report->fullname);

//$PAGE->requires->yui2_lib('connection');
//$PAGE->requires->yui2_lib('datatable');
//$PAGE->requires->yui2_lib('datasource');

$ajaxconfig = array(
    'name' => 'block_lp_reportbuilder_ajax',
    'fullpath' => '/blocks/lp_reportbuilder/js/reports.ajax.js',       
    'requires' => array(
        'node',
        'event',
        'selector-css3',
        'yui2-datatable', 
        'yui2-datasource',
        'yui2-paginator',
        'io',
        'json-encode',
        'json',
        'panel'
    ),
    'strings'=> array(
        array('noresultsfound', 'block_lp_reportbuilder'),
        array('loading', 'block_lp_reportbuilder'),
        array('totalrecords', 'block_lp_reportbuilder'),
        array('xrecord', 'block_lp_reportbuilder'),
        array('xrecords', 'block_lp_reportbuilder'),
        array('generatingreport', 'block_lp_reportbuilder')
    )
);

$params = (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');

$PAGE->requires->js_init_call('M.block_lp_reportbuilder_ajax.init', 
        array('rid' => $report->_id, 
            'columns' => $report->columns, 
            'pageSize' => intval($report->recordsperpage), 
  //          'totalRecords' => $report->get_filtered_count(),
            'defaultSortColumn' => $report->defaultsortcolumn,
            'params' => $params,
            'filtering' => $filtering,
            'wwwroot' => $CFG->wwwroot,
            'makerequest' => $makerequest
        ), false, $ajaxconfig);

echo $OUTPUT->header();  

// Output the report header        
echo $OUTPUT->heading($report->fullname);

// Output the description
print $report->print_description();

// Display the filters
$report->display_search();

echo '<div' . (!$makerequest ? ' style="display:none"' : '') . '>';
$content = html_writer::tag('img','',array('src'=>$OUTPUT->pix_url('icon_filter', 'blocks_lp_reportbuilder')));
echo '<div id="export" class="icons-container">';
print html_writer::tag('a', $content, array('id' => 'show-hide-search-filter', 'class' => 'button', 'title' => get_string('showhidefilters', 'block_lp_reportbuilder')));
echo '</div>';	

print $report->edit_button();
echo $report->generate_export_options();

// Print saved search buttons if appropriate
echo $OUTPUT->container_start('saved-search');
print $report->save_button();
print $report->view_saved_menu();
echo $OUTPUT->container_end();
echo '</div>';

echo '<br /><br />';
echo html_writer::tag('h4', '', array('id' => 'totalCountLabel'));
$report->display_table($makerequest);

echo $OUTPUT->footer();