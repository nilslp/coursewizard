<?php // $Id$
/*
 * Copyright (C) 2011 Learning Pool
 * 
 * @author Brian Quinn <brian@learningpool.com>
 * @subpackage lp_reportbuilder 
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/lib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/report_forms.php');
require_once($CFG->dirroot.'/local/learningpool/js/setup.php');

global $USER;

$id = required_param('id',PARAM_INT); // report builder id
$d = optional_param('d', null, PARAM_TEXT); // delete
$m = optional_param('m', null, PARAM_TEXT); // move
$h = optional_param('h', null, PARAM_TEXT); // show/hide
$cid = optional_param('cid',null,PARAM_INT); //column id
$confirm = optional_param('confirm', 0, PARAM_INT); // confirm delete

include_jquery();

// include code to handle column headings
$PAGE->requires->js('/blocks/lp_reportbuilder/columns.js.php');

admin_externalpage_setup('managelearningpoolreports');

$returnurl = $CFG->wwwroot."/blocks/lp_reportbuilder/columns.php?id=$id";

$report = new reportbuilder($id);

// form definition
$mform = new report_builder_edit_columns_form(null, compact('id','report'));

// form results check
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot."/blocks/lp_reportbuilder/report.php?id=$id");
}

echo $OUTPUT->header();

// toggle show/hide column
if ($h !== null && isset($cid)) {
    if ($report->showhide_column($cid, $h)) {
        $vis = $h ? 'Hide' : 'Show';
        add_to_log(SITEID, 'reportbuilder', 'update report', 'columns.php?id='. $id,
            $vis . ' Column: Report ID=' . $id . ', Column ID=' . $cid);
        echo $OUTPUT->notification(get_string('column_vis_updated','block_lp_reportbuilder'), 'notifysuccess');
    } 
    else {
        echo $OUTPUT->notification(get_string('error:column_vis_not_updated','block_lp_reportbuilder'), 'notifyfailure');
    }
}

// delete column
if ($d and $confirm ) {
    if (!confirm_sesskey()) {
        echo $OUTPUT->notification(get_string('error:bad_sesskey','block_lp_reportbuilder'), 'notifyfailure');
    }

    if (isset($cid)) {
        if($report->delete_column($cid)) {
            add_to_log(SITEID, 'reportbuilder', 'update report', 'columns.php?id='. $id,
                'Deleted Column: Report ID=' . $id . ', Column ID=' . $cid);
            redirect($returnurl, get_string('column_deleted','block_lp_reportbuilder'));
            echo $OUTPUT->notification(get_string('column_deleted', 'block_lp_reportbuilder'), 'notifysuccess');            
        } 
        else {
        	echo $OUTPUT->notification(get_string('error:column_not_deleted', 'block_lp_reportbuilder'), 'notifyfailure');
        }
    }
}

// confirm deletion column
if ($d) {   
    if(isset($cid)) {
    	echo $OUTPUT->confirm('Are you sure you want to delete this column?', "columns.php?d=1&amp;id=$id&amp;cid=$cid&amp;confirm=1&amp;sesskey=$USER->sesskey", $returnurl);
    }

    $OUTPUT->footer();
    die;
}

// move column
if ($m && isset($cid)) {
    if ($report->move_column($cid, $m)) {
        add_to_log(SITEID, 'reportbuilder', 'update report', 'columns.php?id='. $id,
            'Moved Column: Report ID=' . $id . ', Column ID=' . $cid);
        echo $OUTPUT->notification(get_string('column_moved','block_lp_reportbuilder'), 'notifysuccess');
    } 
    else {
    	echo $OUTPUT->notification(get_string('error:column_not_moved','block_lp_reportbuilder'), 'notifyfailure');
    }
}

if ($fromform = $mform->get_data()) {
    if (empty($fromform->submitbutton)) {
    	echo $OUTPUT->notification(get_string('error:unknownbuttonclicked','block_lp_reportbuilder'), 'notifyfailure');
    }

    if (build_columns($id, $fromform, $report)) {
        add_to_log(SITEID, 'reportbuilder', 'update report', 'columns.php?id='. $id,
            'Column Settings: Report ID=' . $id);
        echo $OUTPUT->notification(get_string('columns_updated','block_lp_reportbuilder'), 'notifysuccess');
    } 
    else {
    	echo $OUTPUT->notification(get_string('error:columns_not_updated','block_lp_reportbuilder'), 'notifyfailure');
    }

}

echo $OUTPUT->container_start('reportbuilder-navbuttons', 'reportbuilder-navbuttons');
echo $OUTPUT->single_button($CFG->wwwroot.'/blocks/lp_reportbuilder/index.php', get_string('allreports','block_lp_reportbuilder'), 'get');
print $report->view_button();
echo $OUTPUT->container_end();

echo $OUTPUT->heading(get_string('editreport','block_lp_reportbuilder',$report->fullname));

$currenttab = 'columns';
include_once('tabs.php');

// display the form
$mform->display();

// include JS object to define the column headings
print '<script type="text/javascript">';
$headings = array();
foreach($report->src->columnoptions as $option) {
    $key = $option->type . '-' . $option->value;
    // use defaultheading if set, otherwise name
    $value = ($option->defaultheading) ? $option->defaultheading :
        $option->name;
    $headings[$key] = $value;
}
print "var rb_column_headings = " . json_encode($headings) . ";";
if (is_numeric($id)) {
	// This variable is required for the javascript/AJAX calls
    print "var rb_reportid = " . $id . "; ";
}
print '</script>';

echo $OUTPUT->footer();

/**
 * Update the report columns table with data from the submitted form
 *
 * @param integer $id Report ID to update
 * @param object $fromform Moodle form object containing the new column data
 * @param object $report The report object
 *
 * @return boolean True if the columns could be updated successfully
 */
function build_columns($id, $fromform, $report) {
    global $DB;
    
    $transaction = $DB->start_delegated_transaction();

    if ($oldcolumns = $DB->get_records('report_builder_columns', array('reportid' => $id))) {
        // see if existing columns have changed
        foreach($oldcolumns as $cid => $oldcolumn) {
            $columnname = "column{$cid}";
            $headingname = "heading{$cid}";
            $customheadingname = "customheading{$cid}";
            // update db only if column has changed
            if(isset($fromform->$columnname) &&
                ($fromform->$columnname != $oldcolumn->type.'-'.$oldcolumn->value ||
                $fromform->$headingname != $oldcolumn->heading ||
                (isset($oldcolumn->customheading) && 
                $fromform->$customheadingname != $oldcolumn->customheading))) {
                
                
                if($fromform->$customheadingname == 0) {
                    foreach($report->src->columnoptions as $option) {                
                        $key = $option->type . '-' . $option->value;
                        
                        if($key == $fromform->$columnname ) {
                            $heading = $option->name;
                            break;
                        }
                    }
                }else{
                    $heading = isset($fromform->$headingname) ? $fromform->$headingname : '';
                }

                $todb = new object();
                $todb->id = $cid;
                $parts = explode('-', $fromform->$columnname);
                $todb->type = $parts[0];
                $todb->value = $parts[1];
                $todb->heading = $heading;
                $todb->customheading = $fromform->$customheadingname;

                if(!$DB->update_record('report_builder_columns', $todb)) {
                    $transaction->rollback(new Exception('Update of report_builder_columns failed'));
                    return false;
                }
            }
        }
    }

    // add any new columns
    if(isset($fromform->newcolumns) && $fromform->newcolumns != '0') {
        $heading = isset($fromform->newheading) ? $fromform->newheading : '';
        $todb = new object();
        $todb->reportid = $id;
        $parts = explode('-',$fromform->newcolumns);
        $todb->type = $parts[0];
        $todb->value = $parts[1];
        $todb->heading = $heading;
        $todb->customheading = $fromform->newcustomheading;
        $sortorder = $DB->get_field('report_builder_columns', 'MAX(sortorder) + 1', array('reportid' => $id));
        if(!$sortorder) {
            $sortorder = 1;
        }
        $todb->sortorder = $sortorder;
        if(!$DB->insert_record('report_builder_columns', $todb)) {
        	$transaction->rollback(new Exception('Update of report_builder_columns (new) failed'));
            return false;
        }
    }

    // update default column settings
    if(isset($fromform->defaultsortcolumn)) {
        $todb = new object();
        $todb->id = $id;
        $todb->defaultsortcolumn = $fromform->defaultsortcolumn;
        $todb->defaultsortorder = $fromform->defaultsortorder;
        if(!$DB->update_record('report_builder', $todb)) {
            $transaction->rollback(new Exception('Update of report_builder failed'));
            return false;
        }
    }
    
    $transaction->allow_commit();

    return true;
}


?>
