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

global $USER, $DB;

$id = required_param('id',PARAM_INT); // report builder id
$d = optional_param('d', null, PARAM_TEXT); // delete
$m = optional_param('m', null, PARAM_TEXT); // move
$fid = optional_param('fid',null,PARAM_INT); //filter id
$confirm = optional_param('confirm', 0, PARAM_INT); // confirm delete

include_jquery();

// include js to handle column actions
$PAGE->requires->js('/blocks/lp_reportbuilder/filters.js.php');

admin_externalpage_setup('managelearningpoolreports');

$returnurl = $CFG->wwwroot."/blocks/lp_reportbuilder/filters.php?id=$id";

$report = new reportbuilder($id);

// form definition
$mform = new report_builder_edit_filters_form(null, compact('id','report'));

// form results check
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot."/blocks/lp_reportbuilder/report.php?id=$id");
}

echo $OUTPUT->header();

// delete fields or columns
if ($d and $confirm ) {
    if (!confirm_sesskey()) {
        echo $OUTPUT->notification(get_string('error:bad_sesskey','block_lp_reportbuilder'), 'notifyfailure');
    }

    if (isset($fid)) {
        if ($report->delete_filter($fid)) {
            add_to_log(SITEID, 'reportbuilder', 'update report', 'filters.php?id='. $id,
                'Delete Filter: Report ID=' . $id . ', Filter ID=' . $fid);
            echo $OUTPUT->notification(get_string('filter_deleted','block_lp_reportbuilder'), 'notifysuccess');
        } 
        else {
        	echo $OUTPUT->notification(get_string('error:filter_not_deleted','block_lp_reportbuilder'), 'notifyfailure');
        }
    }
}


// confirm deletion of field or column
if ($d) {
    if (isset($fid)) {
    	echo $OUTPUT->confirm('Are you sure you want to delete this filter?', "filters.php?d=1&amp;id=$id&amp;fid=$fid&amp;confirm=1&amp;sesskey=$USER->sesskey", $returnurl);
    }

    echo $OUTPUT->footer();
    die;
}

// move filter
if ($m && isset($fid)) {
    if ($report->move_filter($fid, $m)) {
        add_to_log(SITEID, 'reportbuilder', 'update report', 'filters.php?id='. $id,
            'Moved Filter: Report ID=' . $id . ', Filter ID=' . $fid);
        echo $OUTPUT->notification(get_string('filter_moved','block_lp_reportbuilder'), 'notifysuccess');
    } 
    else {
        echo $OUTPUT->notification(get_string('error:filter_not_moved','block_lp_reportbuilder'), 'notifyfailure');
    }
}

if ($fromform = $mform->get_data()) {

    if (empty($fromform->submitbutton)) {
        echo $OUTPUT->notification(get_string('error:unknownbuttonclicked','block_lp_reportbuilder'), 'notifyfailure');
    }

    if (build_filters($id, $fromform)) {
        add_to_log(SITEID, 'reportbuilder', 'update report', 'filters.php?id='. $id,
            'Filter Settings: Report ID=' . $id);
        echo $OUTPUT->notification(get_string('filters_updated','block_lp_reportbuilder'), 'notifysuccess');
    } 
    else {
        echo $OUTPUT->notification(get_string('error:filters_not_updated','block_lp_reportbuilder'), 'notifyfailure');
    }
}

echo $OUTPUT->container_start('reportbuilder-navbuttons', 'reportbuilder-navbuttons');
echo $OUTPUT->single_button($CFG->wwwroot.'/blocks/lp_reportbuilder/index.php', get_string('allreports','block_lp_reportbuilder'), 'get');
print $report->view_button();
echo $OUTPUT->container_end();

echo $OUTPUT->heading(get_string('editreport','block_lp_reportbuilder',$report->fullname));

$currenttab = 'filters';
include_once('tabs.php');

// display the form
$mform->display();

// include JS object to define the column headings
print '<script type="text/javascript">';
if (is_numeric($id)) {
    // This variable is required for the javascript/AJAX calls
    print "var rb_reportid = " . $id . "; ";
}

$headings = array();

foreach ($report->src->filteroptions as $option) {
    $key = $option->type . '-' . $option->value;
    $headings[$key] = $option->label;
}
print "var rb_filter_headings = " . json_encode($headings) . ';';
print '</script>';

echo $OUTPUT->footer();
/**
 * Update the report filters table with data from the submitted form
 *
 * @param integer $id Report ID to update
 * @param object $fromform Moodle form object containing the new filter data
 *
 * @return boolean True if the filters could be updated successfully
 */
function build_filters($id, $fromform) {
	global $DB;
	
	$transaction = $DB->start_delegated_transaction();

    if ($oldfilters = $DB->get_records('report_builder_filters', array('reportid' => $id))) {
        // see if existing filters have changed
        foreach ($oldfilters as $fid => $oldfilter) {
            $filtername = "filter{$fid}";
            $advancedname = "advanced{$fid}";
            // update db only if filter has changed
            if (isset($fromform->$filtername) &&
                ($fromform->$filtername != $oldfilter->type.'-'.$oldfilter->value ||
                $fromform->$filtername != $oldfilter->advanced)) {
                $todb = new object();
                $todb->id = $fid;
                $parts = explode('-', $fromform->$filtername);
                $thisadv = isset($fromform->$advancedname) ? 1 : 0;
                $todb->type = $parts[0];
                $todb->value = $parts[1];
                $todb->advanced = $thisadv;
                if (!$DB->update_record('report_builder_filters', $todb)) {
                    $transaction->rollback(new Exception("Unable to update report_builder_filters"));
                    return false;
                }
            }
        }
    }

    // add any new filters
    if (isset($fromform->newfilter) && $fromform->newfilter != '0') {
        $todb = new object();
        $todb->reportid = $id;
        $parts = explode('-',$fromform->newfilter);
        $thisadv = isset($fromform->newadvanced) ? 1 : 0;
        $todb->type = $parts[0];
        $todb->value = $parts[1];
        $todb->advanced = $thisadv;
        $sortorder = $DB->get_field('report_builder_filters', 'MAX(sortorder) + 1', array('reportid' => $id));
        if (!$sortorder) {
            $sortorder = 1;
        }
        $todb->sortorder = $sortorder;
        if (!$DB->insert_record('report_builder_filters', $todb)) {
            $transaction->rollback(new Exception("Unable to insert report_builder_filters record"));
            return false;
        }
    }

    $transaction->allow_commit();
    return true;
}

?>
