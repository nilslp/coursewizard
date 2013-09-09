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
 * @author Piers Harding <piers@catalyst.net.nz>
 * @package totara
 * @subpackage reportbuilder 
 */
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
//require_once('../lib.php');

require_login();

global $DB;

// Get params
$id = required_param('id', PARAM_INT); //ID
$confirm = optional_param('confirm', '', PARAM_INT); // Delete confirmation hash

if (!$report = $DB->get_record('report_builder_schedule', array('id' => $id))) {
    print_error(get_string('error:invalidreportscheduleid','block_lp_reportbuilder'));
}

$reportname = $DB->get_field('report_builder', 'fullname', array('id' => $report->reportid));

$PAGE->set_context(build_context_path());
$PAGE->set_url($CFG->wwwroot.'/blocks/lp_hierarchy/deletescheduled.php');
$PAGE->set_title(get_string('myreports','block_lp_reportbuilder'));
$PAGE->set_heading($SITE->fullname);

/// Display page
echo $OUTPUT->header();

$returnurl = "{$CFG->wwwroot}/blocks/lp_reportbuilder/schedulereports.php";
$deleteurl = "{$CFG->wwwroot}/blocks/lp_reportbuilder/deletescheduled.php?id={$report->id}&amp;confirm=1&amp;sesskey={$USER->sesskey}";

if (!$confirm) {
    $strdelete = get_string('deletecheckschedulereport', 'block_lp_reportbuilder');

    echo $OUTPUT->confirm("{$strdelete}<br /><br />" . format_string($reportname),
        new moodle_url($deleteurl), 
        new moodle_url($returnurl));

    echo $OUTPUT->footer();
    
    exit;
}

// Delete report builder schedule
if (!confirm_sesskey()) {
    print_error('confirmsesskeybad', 'error');
}

add_to_log(SITEID, 'scheduledreport', 'delete', "blocks/lp_reportbuilder/scheduled.php?id=$report->id", "$reportname (ID $report->id)");

$DB->delete_records('report_builder_schedule', array('id' => $report->id));

echo $OUTPUT->notification(get_string('deletedscheduledreport', 'block_lp_reportbuilder', $reportname), 'notifysuccess');

echo $OUTPUT->continue_button($returnurl);
echo $OUTPUT->footer();