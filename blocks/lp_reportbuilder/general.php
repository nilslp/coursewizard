<?php // $Id$
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
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @package totara
 * @subpackage reportbuilder 
 */

/**
 * Page containing general report settings
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/lib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/report_forms.php');

global $USER, $DB;
$id = required_param('id',PARAM_INT); // report builder id

admin_externalpage_setup('managelearningpoolreports');

$returnurl = $CFG->wwwroot."/blocks/lp_reportbuilder/general.php?id=$id";

$report = new reportbuilder($id);

// form definition
$mform = new report_builder_edit_form(null, compact('id','report'));

// form results check
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot."/blocks/lp_reportbuilder/report.php?id=$id");
}

echo $OUTPUT->header();

if ($fromform = $mform->get_data()) {
    if (empty($fromform->submitbutton)) {
    	echo $OUTPUT->notification(get_string('error:unknownbuttonclicked','block_lp_reportbuilder'), 'notifyfailure');
    }

    $todb = new object();
    $todb->id = $id;
    $todb->fullname = addslashes($fromform->fullname);
    $todb->hidden = $fromform->hidden;
    $todb->showinblock = $fromform->showinblock;
    
    $todb->description = addslashes($fromform->description);
    if((int)$fromform->recordsperpage > 5000) {
        $rpp = 5000;
    } else if ((int)$fromform->recordsperpage < 1) {
        $rpp = 1;
    } else {
        $rpp = (int)$fromform->recordsperpage;
    }
    $todb->recordsperpage = $rpp;
    if ($DB->update_record('report_builder',$todb)) {
        add_to_log(SITEID, 'reportbuilder', 'update report', 'general.php?id='. $id,
            'General Settings: Report ID=' . $id);
        echo $OUTPUT->notification(get_string('reportupdated','block_lp_reportbuilder'), 'notifysuccess');
    } 
    else {
        echo $OUTPUT->notification(get_string('error:couldnotupdatereport','block_lp_reportbuilder'), 'notifysuccess');
    }
}

print_container_start(true, 'reportbuilder-navbuttons');
echo $OUTPUT->single_button($CFG->wwwroot.'/blocks/lp_reportbuilder/index.php', get_string('allreports','block_lp_reportbuilder'), 'get');
//print_single_button($CFG->wwwroot.'/blocks/lp_reportbuilder/index.php', null, get_string('allreports','local_reportbuilder'));
print $report->view_button();
print_container_end();

echo $OUTPUT->heading(get_string('editreport','block_lp_reportbuilder',$report->fullname));

$currenttab = 'general';
include_once('tabs.php');

// display the form
$mform->display();

echo $OUTPUT->footer();
?>
