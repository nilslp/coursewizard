<?php
/**
 * Page containing list of saved searches for this report
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/lib.php');
require_once('report_forms.php');

global $DB;

define('REPORT_BUILDER_SAVED_SEARCHES_CONFIRM_DELETE', 1);
define('REPORT_BUILDER_SAVED_SEARCHES_FAILED_DELETE', 2);

$id = optional_param('id',null,PARAM_INT); // id for report
$sid = optional_param('sid',null,PARAM_INT); // id for saved search
$d = optional_param('d',false, PARAM_BOOL); // delete saved search?
$confirm = optional_param('confirm', false, PARAM_BOOL); // confirm delete

$returnurl = $CFG->wwwroot.'/blocks/lp_reportbuilder/savedsearches.php?id='.$id;

require_login();
$PAGE->set_context(build_context_path());
$PAGE->set_url($returnurl);

if (!$id) {
    if (!$sid) {
        // can't recover here
        print_error('error:invalidsavedsearchid','block_lp_reportbuilder');
    } else {        
        $id = $DB->get_field('report_builder_saved', 'reportid', array('id' => $sid));
    }
}

$report = new reportbuilder($id);

if (!$report->is_capable($id)) {
    error(get_string('nopermission','blocks_lp_reportbuilder'));
}

$fullname = $report->fullname;
$pagetitle = format_string(get_string('savesearch','block_lp_reportbuilder').': '.$fullname);

// Define the page layout and header/breadcrumb
$PAGE->set_pagelayout('base');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($SITE->fullname);

$PAGE->navbar->add(get_string('report','block_lp_reportbuilder'));
$PAGE->navbar->add($fullname);

if ($d && $confirm) {
    // Delete an existing saved search
    if (!confirm_sesskey()) {
        echo $OUTPUT->notification(get_string('error:bad_sesskey','blocks_lp_reportbuilder'), 'notifyfailure');
    }
    if ($DB->delete_records('report_builder_saved', array('id' => $sid))) {
    	echo $OUTPUT->notificaton(get_string('savedsearchdeleted','blocks_lp_reportbuilder'), 'notifysuccess');
    } else {
    	echo $OUTPUT->notification(get_string('error:savedsearchnotdeleted','blocks_lp_reportbuilder', 'notifyfailure'));
    }
} else if ($d) {
    
    echo $OUTPUT->header(get_string('savedsearches','block_lp_reportbuilder'));

    // Prompt to delete
    echo $OUTPUT->confirm(get_string('savedsearchconfirmdelete','block_lp_reportbuilder'),
        "savedsearches.php?id={$id}&amp;sid={$sid}&amp;d=1&amp;confirm=1&amp;" .
        "sesskey={$USER->sesskey}", $returnurl);

    echo $OUTPUT->footer();
    die;
}

//$PAGE->navbar->add(get_string('savesearch', 'block_lp_reportbuilder'));
echo $OUTPUT->header();

echo $report->edit_button();
print $report->view_button();

if ($searches = $DB->get_records_select('report_builder_saved', "(userid = {$USER->id} OR ispublic <> 0) AND reportid = {$id}")) {
    $tableheader = array(get_string('name','block_lp_reportbuilder'),
                         get_string('options','block_lp_reportbuilder'));
    $data = array();

    foreach($searches as $search) {
        $cells = array();
        $strdelete = get_string('delete','block_lp_reportbuilder');

//        $row[] = '<a href="' . $CFG->wwwroot . '/blocks/lp_reportbuilder/report.php?id=' . $id .
//            '&amp;sid='.$search->id.'">' . $search->name . '</a>';
        $cell_name = new html_table_cell('<a href="' . $CFG->wwwroot . '/blocks/lp_reportbuilder/report.php?id=' . $id .
            '&amp;sid='.$search->id.'">' . $search->name . '</a>');
        
        $delete = '<a href="' . $CFG->wwwroot .
            '/blocks/lp_reportbuilder/savedsearches.php?d=1&amp;id=' . $id . '&amp;sid=' .
            $search->id . '" title="' . $strdelete . '">' .
            '<img src="' . $CFG->wwwroot . '/pix/t/delete.gif" alt="' .
            $strdelete . '"></a>';
        
        $cell_delete = new html_table_cell($delete);
        
        $cells[] = $cell_name;
        $cells[] = $cell_delete;
        
        $row = new html_table_row($cells);
        
        $data[] = $row;
    }
    
    $table = new html_table();
    $table->summary = '';
    $table->head = $tableheader;
    $table->data = $data;

    echo html_writer::table($table);
} else {
    // print_error('error:nosavedsearches','block_lp_reportbuilder');
    echo $OUTPUT->box(get_string('nosearchesforreport', 'block_lp_reportbuilder'), 'generalbox', 'notice');
}

echo $OUTPUT->footer();
?>
