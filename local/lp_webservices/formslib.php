<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once('lib.php');

class lp_webservices_configure_form extends moodleform {

    function definition() {
        global $CFG, $DB;

        $mform = &$this->_form;
            
        $mform->addElement('header', 'metacompliancesetup', get_string('metacompliancesetup', 'local_lp_webservices'));    
        $mform->addElement('static', 'createmetacomplianceuser_help', '', get_string('createmetacomplianceuser_help', 'local_lp_webservices'));        
        if (!(int)$DB->record_exists('role', array('shortname' => METACOMPLIANCE_ROLE_SHORTNAME))) {
            $mform->addElement('text', 'metacomplianceusername', get_string('metacomplianceusername', 'local_lp_webservices'));
            $mform->addHelpButton('metacomplianceusername', 'metacomplianceusername', 'local_lp_webservices');
            $mform->setType('metacomplianceusername', PARAM_RAW);
            $mform->addElement('passwordunmask', 'metacompliancepassword', get_string('metacompliancepassword', 'local_lp_webservices'));
            $mform->addHelpButton('metacompliancepassword', 'metacompliancepassword', 'local_lp_webservices');
            $mform->addElement('submit', 'createmetacomplianceuser', get_string('createmetacomplianceuser', 'local_lp_webservices'));
        } else {
            $meta = new stdClass();
            $meta->token = lp_webservices_get_metauser_token();
            $meta->username = get_config('local_lp_webservices', 'metacomplianceusername');
            
            $mform->addElement('static', 'createmetacomplianceuser', '', get_string('createmetacomplianceusercomplete', 'local_lp_webservices', $meta));            
        }

        // $this->add_action_buttons();
    }
    
    
    function validation($data, $files) {
        global $DB,$CFG;
        
        $errors = array();
        if (isset($data['createmetacomplianceuser'])) {
            if (empty($data['metacompliancepassword']) || empty($data['metacomplianceusername'])) {
                $errors['createmetacomplianceuser'] = get_string('error:invaliduserdetails', 'local_lp_webservices');
            }
            
            if ($DB->record_exists('user', array('username' => $data['metacomplianceusername']))) {
                $errors['createmetacomplianceuser'] = get_string('error:usernameexists', 'local_lp_webservices');                
            }
        }
        return $errors;
    }
    function process() {
        global $OUTPUT, $PAGE;
        
        $data = $this->get_data();
        if (empty($data)) {
            return false;
        }
        
        if (isset($data->createmetacomplianceuser)) {
            lp_webservices_generate_metacompliance_user($data->metacomplianceusername, $data->metacompliancepassword);
            echo $OUTPUT->notification(get_string('generatemetacomplianceusersuccess', 'local_lp_webservices'), 'notifysuccess');
            redirect($PAGE->url);
        }
        
        return true;
    }

}