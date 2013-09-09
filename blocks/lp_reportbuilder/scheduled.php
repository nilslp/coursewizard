<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010, 2011 Totara Learning Solutions LTD
 * 
 * This program is free software; you can redistribute it and/or modify  
 * it under the terms of the GNU General Public License as published by  
 * the Free Software Foundation; either version 2 of the License, or     
 * (at your option) any later version.                                   
 *                                                                       
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Alastair Munro <alastair@catalyst.net.nz>
 * @package totara
 * @subpackage reportbuilder 
 */

/**
 * Page for setting up scheduled reports
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/lib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/scheduled_forms.php');

require_login();

$reportid = optional_param('reportid', 0, PARAM_INT); //report that a schedule is being added for
$id = optional_param('id', 0, PARAM_INT); //id if editing schedule

$scheduledreportsurl = $CFG->wwwroot . '/blocks/lp_reportbuilder/schedulereports.php';
$returnurl = $CFG->wwwroot . '/blocks/lp_reportbuilder/scheduled.php';

$PAGE->set_context(build_context_path());
$PAGE->set_url($CFG->wwwroot.'/blocks/lp_reportbuilder/scheduled.php');

if($id == 0){
    $report = new object();
    $report->id = 0;
    $report->reportid = $reportid;
    $report->frequency = null;
    $report->schedule = null;
}
else{
    if(!$report = $DB->get_record('report_builder_schedule', array('id' => $id))) {
        error(get_string('error:invalidreportscheduleid', 'block_lp_reportbuilder'));
    }
}

// form definition
$mform = new scheduled_reports_new_form(
    null,
    array(
        'id' => $id,
        'reportid' => $report->reportid,
        'frequency' => $report->frequency,
        'schedule' => $report->schedule
    )
);

$mform->set_data($report);

if($mform->is_cancelled()){
    redirect($scheduledreportsurl);
}

if ($id==0) {
    $pagename = 'addscheduledreport';
} else {
    $pagename = 'editscheduledreport';
}

global $SESSION,$USER;

$PAGE->set_context(build_context_path());
$PAGE->set_url($CFG->wwwroot.'/blocks/lp_reportbuilder/scheduled.php');

$title = get_string($pagename, 'block_lp_reportbuilder');

// Define the page layout and header/breadcrumb

$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($SITE->fullname);

$home_url = $CFG->wwwroot;

$PAGE->navbar->add(get_string('myreports','block_lp_reportbuilder'), "$CFG->wwwroot/blocks/lp_reportbuilder/myreports.php");
$PAGE->navbar->add($title);

echo $OUTPUT->header();

if($fromform = $mform->get_data()){
    if(empty($fromform->submitbutton)) {
        echo $OUTPUT->notification(get_string('error:unknownbuttonclicked', 'block_lp_reportbuilder'), 'notifyfailure');
    }

    if($fromform->id){
        if($newid = add_scheduled_report($fromform)) {
            echo $OUTPUT->notification(get_string('updatescheduledreport', 'block_lp_reportbuilder'), 'notifysuccess');
        }
        else {
            echo $OUTPUT->notification(get_string('error:updatescheduledreport', 'block_lp_reportbuilder'), 'notifyfailure');
        }
    }
    else {
        if($newid = add_scheduled_report($fromform)) {
            echo $OUTPUT->notification(get_string('addedscheduledreport', 'block_lp_reportbuilder'), 'notifysuccess');
        }
        else {
            echo $OUTPUT->notification(get_string('error:addscheduledreport', 'block_lp_reportbuilder'), 'notifyfailure');            
        }
    }
}

//print_container_start(true, 'reportbuilder-navbuttons');
echo $OUTPUT->single_button($CFG->wwwroot.'/blocks/lp_reportbuilder/myreports.php#scheduled', get_string('allscheduledreports','block_lp_reportbuilder'));

//print_single_button($CFG->wwwroot.'/block/lp_reportbuilder/myreports.php#scheduled', null, get_string('allscheduledreports','block_lp_reportbuilder'));
//print_container_end();
echo $OUTPUT->heading(get_string($pagename, 'block_lp_reportbuilder'));

$mform->display();

echo $OUTPUT->footer();

function add_scheduled_report($fromform){
    global $USER, $DB, $REPORT_BUILDER_EXPORT_OPTIONS, $REPORT_BUILDER_SCHEDULE_OPTIONS;

    $REPORT_BUILDER_SCHEDULE_CODES = array_flip($REPORT_BUILDER_SCHEDULE_OPTIONS);
    
    $transaction = $DB->start_delegated_transaction();
    
    if (isset($fromform->reportid) && isset($fromform->format) && isset($fromform->frequency)) {
        $todb = new object();
        if ($id = $fromform->id){
            $todb->id = $id;
        }

        $todb->reportid = $fromform->reportid;
        $todb->savedsearchid = $fromform->savedsearchid;
        $todb->userid = $USER->id;
        $todb->format = $fromform->format;
        $todb->frequency = $fromform->frequency;
        switch($REPORT_BUILDER_SCHEDULE_CODES[$fromform->frequency]){
            case 'daily':
                $todb->schedule = $fromform->daily;
                break;
            case 'weekly':
                $todb->schedule = $fromform->weekly;
                break;
            case 'monthly':
                $todb->schedule = $fromform->monthly;
                break;
        }

        // HACK -- what is this for?
        $todb->nextreport = 0;
        if (!$id){
            if(!$newid = $DB->insert_record('report_builder_schedule', $todb)) {
                $transaction->rollback(new Exception("Unable to insert into report_builder_schedule"));
                return false;
            }
        }
        else {
            $todb->nextreport = null;

            if(!$newid = $DB->update_record('report_builder_schedule', $todb)) {
                $transaction->rollback(new Exception("Unable to rollback report_builder_schedule"));
                return false;
            }
        }
        $transaction->allow_commit();
        return $newid;
    }
    
    return false;
}
?>
