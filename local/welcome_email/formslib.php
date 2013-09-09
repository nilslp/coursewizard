<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once('lib.php');

class welcomeemailsettingsform extends moodleform {

    function definition() {
        global $CFG;

        $mform = &$this->_form;

        $mform->addElement('header', 'settings', get_string('customemailsettings', 'local_welcome_email'));
        
        $mform->addElement('checkbox', 'enablewelcomeemail', get_string('enablewelcomeemail', 'local_welcome_email'));
        $mform->addHelpButton('enablewelcomeemail', 'enablewelcomeemail', 'local_welcome_email');
        
        // add a time selector group for the daily schedule
        $timegroup = array();   
            
        $hours = array();
        for ($h = 0; $h < 24; ++$h) {
            $hours []= sprintf("%02d",$h);
        } 
        
        $minutes = array();        
        for ($m = 0; $m < 60; ++$m) {
            $minutes []= sprintf("%02d",$m);
        }
        
        $timegroup []= &$mform->createElement('select', 'welcomeemailhour', get_string('welcomeemailhour', 'local_welcome_email'), $hours);
        $timegroup []= &$mform->createElement('select', 'welcomeemailminute', get_string('welcomeemailminute', 'local_welcome_email'), $minutes);
        $mform->addGroup($timegroup, 'welcomeemailtime', get_string('welcomeemailtime', 'local_welcome_email'), ' ', false);
        $mform->addHelpButton('welcomeemailtime', 'welcomeemailtime', 'local_welcome_email');        
        $mform->disabledIf('welcomeemailtime', 'enablewelcomeemail');
        
        $mform->addElement('checkbox', 'usecustomtemplate', get_string('usecustomtemplate', 'local_welcome_email'));
        $mform->addHelpButton('usecustomtemplate', 'usecustomtemplate', 'local_welcome_email');        
        $mform->disabledIf('usecustomtemplate', 'enablewelcomeemail');
        
        $mform->addElement('header', 'templatesettings', get_string('custometemplatesettings', 'local_welcome_email'));
        
        $mform->addElement('text', 'customtemplatecc', get_string('customtemplatecc', 'local_welcome_email'));
        $mform->addHelpButton('customtemplatecc', 'customtemplatecc', 'local_welcome_email');
        $mform->setType('customtemplatecc', PARAM_RAW);
        
        $mform->addElement('text', 'customtemplatesubject', get_string('customtemplatesubject', 'local_welcome_email'));
        $mform->addHelpButton('customtemplatesubject', 'customtemplatesubject', 'local_welcome_email');
        $mform->setType('customtemplatesubject', PARAM_RAW);
        
        $mform->addElement('text', 'customtemplatefrom', get_string('customtemplatefrom', 'local_welcome_email'));
        $mform->addHelpButton('customtemplatefrom', 'customtemplatefrom', 'local_welcome_email');
        $mform->setType('customtemplatefrom', PARAM_RAW);
        
        $mform->addElement('editor', 'customtemplatebody', get_string('customtemplatebody', 'local_welcome_email'));
        $mform->addHelpButton('customtemplatebody', 'customtemplatebody', 'local_welcome_email');
        $mform->setType('customtemplatebody', PARAM_RAW);
        
        $this->add_action_buttons();
    }
    
    function definition_after_data() {
        parent::definition_after_data();
        $data = $this->get_data();
        if (empty($data)) {
            return false;
        }
        
        // decode the html for the form
        $mform = &$this->_form;
        if (isset($data->customtemplatebody)) {
            if (is_array($data->customtemplatebody)) {
                $mform->setDefault('customtemplatebody', html_entity_decode($data->customtemplatebody['text'], ENT_COMPAT, 'UTF-8'));
            } else {
                $mform->setDefault('customtemplatebody', array('text' => html_entity_decode($data->customtemplatebody, ENT_COMPAT, 'UTF-8'), 'format'=> 1));                
            }
        }
    }

    function process() {
        $data = $this->get_data();
        if (empty($data)) {
            return false;
        }
                
        if (isset($data->enablewelcomeemail)) {
            set_config('enablewelcomeemail', (int)$data->enablewelcomeemail, 'local_welcome_email');
        } else {
            set_config('enablewelcomeemail', 0, 'local_welcome_email');            
        }        
                
        if (isset($data->welcomeemailhour) && isset($data->welcomeemailminute)) {
            $time = sprintf("%02d",$data->welcomeemailhour) . sprintf("%02d",$data->welcomeemailminute);
            set_config('welcomeemailtime', $time, 'local_welcome_email');
        }
        
        if (isset($data->usecustomtemplate)) {
            set_config('usecustomtemplate', (int)$data->usecustomtemplate, 'local_welcome_email');
        } else {
            set_config('usecustomtemplate', 0, 'local_welcome_email');            
        }
        
        if (isset($data->customtemplatecc)) {
            set_config('customtemplatecc', $data->customtemplatecc, 'local_welcome_email');
        }
        
        if (isset($data->customtemplatesubject)) {
            set_config('customtemplatesubject', $data->customtemplatesubject, 'local_welcome_email');
        }
        
        if (isset($data->customtemplatefrom)) {
            set_config('customtemplatefrom', $data->customtemplatefrom, 'local_welcome_email');
        }
        
        // editor         
        if (isset($data->customtemplatebody)) {
            $text     = htmlentities($data->customtemplatebody['text'], ENT_COMPAT, 'UTF-8');
            set_config('customtemplatebody', $text, 'local_welcome_email');
        }
        
        return true;
    }

}

class welcomeemailselfregsettingsform extends moodleform {

    function definition() {
        global $CFG;

        $mform = &$this->_form;

        $mform->addElement('header', 'settings', get_string('settings', 'local_welcome_email'));     
        
        $mform->addElement('static', 'description', '', get_string('selfregdesc', 'local_welcome_email'));   
        
        $mform->addElement('text', 'selfregcc', get_string('selfregcc', 'local_welcome_email'));
        $mform->setDefault('selfregcc', get_config('local_welcome_email', 'selfregcc'));
        $mform->addHelpButton('selfregcc', 'selfregcc', 'local_welcome_email');
        $mform->setType('selfregcc', PARAM_RAW);
        
        $mform->addElement('textarea', 'selfregbody', get_string('selfregbody', 'local_welcome_email'), 'wrap="virtual" rows="20" cols="50"');
        $mform->setDefault('selfregbody', get_string('emailconfirmation'));
        $mform->addHelpButton('selfregbody', 'selfregbody', 'local_welcome_email');
        $mform->setType('selfregbody', PARAM_RAW);
        
        // in future, provide a select to allow user to customize per lang pack
        $mform->addElement('hidden', 'lng', 'en');
        $mform->setType('lng', PARAM_RAW);
       
        $this->add_action_buttons();
    }

    function process() {
        global $CFG;
        
        $data = $this->get_data();
        if (empty($data)) {
            return false;
        }
        
        // cc
        if (isset($data->selfregcc)) {
            set_config('selfregcc', $data->selfregcc, 'local_welcome_email');
        }
        
        // editor         
        if (isset($data->selfregbody) && isset($data->lng)) {
            $lang = $data->lng;
            $path = $CFG->langlocalroot.'/'.$lang.'_local/moodle.php';
            
            if (!file_exists($path)) {
                $string = <<<EOF
<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local language pack from $CFG->wwwroot
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


EOF;
            } else {            
                // load file
                $string = file_get_contents($path);
            }
            
            $data->selfregbody = addslashes($data->selfregbody);
            
            // search for string
            if (preg_match('/\$string\[\'emailconfirmation\'\].*[\r\n\r\n]*.*;/m', $string)) {
                $string = preg_replace('/\$string\[\'emailconfirmation\'\].*[\r\n\r\n]*.*;/m', '$string[\'emailconfirmation\'] = \''.$data->selfregbody.'\';', $string);
            } else {
                $string .= '$string[\'emailconfirmation\'] = \''.$data->selfregbody.'\';';
            }
            
            // write file
            file_put_contents($path, $string);
            
            $sm = get_string_manager();
            $sm->reset_caches();
        }
        
        return true;
    }

}

class welcomeemailsearchform extends moodleform {

    function definition() {
        global $CFG;

        $mform = &$this->_form;
        $mform->updateAttributes(array('id'=>'user-search-form'));

        $mform->addElement('header', 'searchbox', get_string('search'));

        $mform->addElement('text', 'search', get_string('searchbynameoremail', 'local_welcome_email'));
        $mform->addHelpButton('search', 'searchbynameoremail', 'local_welcome_email');
        $mform->setType('search', PARAM_RAW);
        
        $statusoptions = array(
            ' -- '.get_string('any').' -- ',
            get_string('status:notsent','local_welcome_email'),
            get_string('status:sent','local_welcome_email'),
            get_string('status:archived','local_welcome_email')
        );
        $mform->addElement('select', 'status', get_string('statusselect', 'local_welcome_email'), $statusoptions);
        $mform->addHelpButton('status', 'statusselect', 'local_welcome_email');
       
	   	if (has_capability('moodle/site:config', context_system::instance())) {	   	
            // "reset between" controls
            $mform->addElement('header', 'resetstatus', get_string('resetstatus', 'local_welcome_email'));
            $mform->addHelpButton('resetstatus', 'resetstatus', 'local_welcome_email');
            $mform->setAdvanced('resetstatus');

            $mform->addElement('date_time_selector', 'timestart', get_string('timestart', 'local_welcome_email'));
            $mform->addHelpButton('timestart', 'timestart', 'local_welcome_email');
            $mform->setAdvanced('timestart');

            $mform->addElement('date_time_selector', 'timeend', get_string('timeend', 'local_welcome_email'));
            $mform->addHelpButton('timeend', 'timeend', 'local_welcome_email');
            $mform->setAdvanced('timeend');

            $setstatusoptions = array(                
                get_string('status:notsent','local_welcome_email'),
                get_string('status:archived','local_welcome_email')
            );
            $mform->addElement('select', 'statussetselect', get_string('statussetselect', 'local_welcome_email'), $setstatusoptions);
            $mform->addHelpButton('statussetselect', 'statussetselect', 'local_welcome_email');
            $mform->setAdvanced('statussetselect');

            $mform->addElement('button', 'submitbutton', get_string('savechanges'));
            $mform->setAdvanced('submitbutton');
		}
        
    } 
    
    function process() {
        global $DB;
        
        $data = $this->get_data();
        if (empty($data)) {
            return false;
        }
        
        if (isset($data->timestart) && isset($data->timeend) && isset($data->statussetselect)) {
            // update users added between the two time frames
            return $DB->execute("UPDATE {lp_welcomeemail} SET email_sent=?,timesent=0 WHERE userid IN (SELECT id FROM {user} WHERE timecreated > ? AND timecreated < ?)", 
                    array(
                        $data->statussetselect,
                        $data->timestart,
                        $data->timeend
                    ));
        } 
        
        return false;
    }
}
