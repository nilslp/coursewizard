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
 * Moodle Formslib templates for scheduled reports settings forms
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once "$CFG->dirroot/lib/formslib.php";
require_once "$CFG->dirroot/calendar/lib.php";

/**
 * Formslib template for the new report form
 */
class scheduled_reports_new_form extends moodleform {
    function definition() {
        global $REPORT_BUILDER_EXPORT_OPTIONS, $REPORT_BUILDER_SCHEDULE_OPTIONS, $USER;
        global $DB;
        
        $CALENDARDAYS = calendar_get_days();
        
        $REPORT_BUILDER_SCHEDULE_CODES = array_flip($REPORT_BUILDER_SCHEDULE_OPTIONS);

        $mform =& $this->_form;
        $id = $this->_customdata['id'];
        $frequency = $this->_customdata['frequency'];
        $schedule = $this->_customdata['schedule'];
        $reportid = $this->_customdata['reportid'];
        $onbehalfof = isset($this->_customdata['onbehalfof']) ? $this->_customdata['onbehalfof'] : false;
        $userfullname = isset($this->_customdata['userfullname']) ? $this->_customdata['userfullname'] : '';

        $onbehalfof_value = $onbehalfof ? 1 : 0;
        
        $reportname = $DB->get_field('report_builder', 'fullname', array('id' => $reportid));

        $mform->addElement('hidden', 'id', $id);
        $mform->addElement('hidden', 'reportid', $reportid);
        $mform->addElement('hidden', 'onbehalfof', $onbehalfof_value);               
        
        $savedsearchselect = array();
        $savedsearchselect[0] = get_string('alldata', 'block_lp_reportbuilder');
        if($savedsearches = $DB->get_records_select('report_builder_saved', 'reportid='.$reportid.' AND (userid='.$USER->id . ' OR ispublic = 1)')) {
            foreach($savedsearches as $search) {
                $savedsearchselect[$search->id] = $search->name;
            }
        }

        $exportoptions = get_config('reportbuilder', 'exportoptions');

        //Export type options
        $exportformatselect = array();
        foreach($REPORT_BUILDER_EXPORT_OPTIONS as $option => $code) {
            // bitwise operator to see if option bit is set
            if(($exportoptions & $code) == $code && ($option != 'fusion')) {
                $exportformatselect[$code] = get_string('export'.$option,'block_lp_reportbuilder');
            }
        }

        //Report type options
        $reports = reportbuilder_get_reports(true);
        $reportselect = array();
        foreach($reports as $report){
            $reportselect[$report->id] = $report->fullname;
        }

        //Schedule type options
        $frequencyselect = array();
        foreach($REPORT_BUILDER_SCHEDULE_OPTIONS as $option => $code) {
            $frequencyselect[$code] = get_string('schedule'.$option, 'block_lp_reportbuilder');
        }

        //Daily selector
        $dailyselect = array();
        for($i=0; $i<24; $i++){
            $dailyselect[$i] = date('H:i', mktime($i,0,0));
        }

        //Weekly selector
        $weeklyselect = array();
        for($i=0; $i<7; $i++){
            $weeklyselect[$i] = get_string($CALENDARDAYS[$i], 'calendar');
        }

        $monthlyselect = array();
        $dateformat = ($USER->lang == 'en_utf8') ? 'jS' : 'j';
        for($i=1; $i<=31; $i++){
            $monthlyselect[$i] = date($dateformat, mktime(0,0,0,0,$i));
        }


        $mform->addElement('header', 'general', get_string('scheduledreportsettings', 'block_lp_reportbuilder'));

        $mform->addElement('static', 'report', get_string('report', 'block_lp_reportbuilder'), $reportname);
        $mform->addElement('select','savedsearchid', 'Data', $savedsearchselect);
        $mform->addElement('select','format', get_string('export','block_lp_reportbuilder'), $exportformatselect);

        $schedulegroup = array();
        $schedulegroup[] =& $mform->createElement('select','frequency', get_string('schedule', 'block_lp_reportbuilder'), $frequencyselect);
        $schedulegroup[] =& $mform->createElement('select','daily', null, $dailyselect);
        $schedulegroup[] =& $mform->createElement('select','weekly', null, $weeklyselect);
        $schedulegroup[] =& $mform->createElement('select','monthly', null, $monthlyselect);

        $mform->addGroup($schedulegroup, 'schedulegroup', get_string('schedule', 'block_lp_reportbuilder'), '', false);
        $mform->disabledIf('daily', 'frequency', 'neq', $REPORT_BUILDER_SCHEDULE_OPTIONS['daily']);
        $mform->disabledIf('weekly', 'frequency', 'neq', $REPORT_BUILDER_SCHEDULE_OPTIONS['weekly']);
        $mform->disabledIf('monthly', 'frequency', 'neq', $REPORT_BUILDER_SCHEDULE_OPTIONS['monthly']);

        if($frequency){
            switch($REPORT_BUILDER_SCHEDULE_CODES[$frequency]){
            case 'daily':
                $mform->setDefault('daily', $schedule);
                break;
            case 'weekly':
                $mform->setDefault('weekly', $schedule);
                break;
            case'monthly':
                $mform->setDefault('monthly', $schedule);
                break;
            }
        }

        if ($onbehalfof) {
            $usergroup = array();
        
            $usergroup []= &$mform->createElement('text','userid', get_string('recipient', 'block_lp_reportbuilder'), array('style' => 'display: none'));
            $usergroup []= &$mform->createElement('static', 'userfullname_container', '', '<span id="id_userfullname" class="userfullname" style="color: black;">'.$userfullname.'</span>');
            
            $usergroup []= &$mform->createElement('button', 'selectuser', get_string('selectrecipient', 'block_lp_reportbuilder'), array('class' => "select_recipient"));
            $usergroup []= &$mform->createElement('button', 'clearuser', get_string('clear', 'block_lp_reportbuilder'));
        
            $mform->addGroup($usergroup, 'userselection_group', 'Recipient', '', false);
            $mform->setType('userid', PARAM_INT);
            $mform->addGroupRule('userselection_group', 'userid', 'required', null, 1, 'server');
        }
        
        $this->add_action_buttons();
    }
    
    function definition_after_data() {
        global $DB;
        parent::definition_after_data();
        
        $mform =& $this->_form;
        $data = $this->get_data();
        if (!empty($data)) {
            $userfullname = $DB->get_field('user', "CONCAT(firstname, ' ', lastname)", array('id'=>$data->userid));
            $mform->setDefault('userfullname_container', '<span id="id_userfullname" class="userfullname">'.$userfullname.'</span>');
        }
    }
}


class scheduled_reports_add_form extends moodleform {
    function definition() {
        global $CFG;
        
        $mform =& $this->_form;

        //Report type options
        $reports = reportbuilder_get_reports();
        $reportselect = array();
        foreach ($reports as $report) {
            $reportselect[$report->id] = $report->fullname;
        }

        $mform->addElement('select','reportid', null, $reportselect);
        $mform->addElement('submit', 'submitbutton', get_string('addscheduledreport', 'block_lp_reportbuilder'));
        
        if (has_capability('block/lp_reportbuilder:schedulereportsforuser', get_context_instance(CONTEXT_SYSTEM))) {
            $mform->addElement('html',  "<br/>(or <a href='{$CFG->wwwroot}/blocks/lp_reportbuilder/schedulereportforuser.php'>click here</a> to schedule a report on behalf of another user)");
        }
        
        $renderer =& $mform->defaultRenderer();
        $elementtemplate = '<span>{element}</span>';
        $renderer->setElementTemplate($elementtemplate, 'submitbutton');
        $renderer->setElementTemplate($elementtemplate, 'reportid');
    }
}

class scheduled_reports_add_on_behalf_form extends moodleform {
    function definition() {
        global $CFG;
        
        $mform =& $this->_form;

        //Report type options
        $reports = reportbuilder_get_reports();
        $reportselect = array();
        foreach ($reports as $report) {
            $reportselect[$report->id] = $report->fullname;
        }

        $mform->addElement('select','reportid', null, $reportselect);
        $mform->addElement('submit', 'submitbutton', get_string('schedulereportforanotheruser', 'block_lp_reportbuilder'));

        $renderer =& $mform->defaultRenderer();
        $elementtemplate = '<span>{element}</span>';
        $renderer->setElementTemplate($elementtemplate, 'submitbutton');
        $renderer->setElementTemplate($elementtemplate, 'reportid');
    }
}
