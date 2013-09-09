<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');

class postcodelookupconfigurationform extends moodleform {

    function definition() {
        global $CFG;

        $mform = &$this->_form;

        $mform->addElement('header', 'general', get_string('postcodelookupconfiguration', 'local_learningpool'));
        
        $config = get_config('learningpool/postcodelookup');
        
        $mform->addElement('advcheckbox', 'usepostcodelookup', get_string('usepostcodelookup', 'local_learningpool'), '', null, array(0, 1) );
        $mform->addHelpButton('usepostcodelookup', 'usepostcodelookup', 'local_learningpool');
        if (isset($config->usepostcodelookup)) {
            $mform->setDefault('usepostcodelookup', $config->usepostcodelookup);
        }
        
        $mform->addElement('header', 'postcodeserviceaccountdetails', get_string('postcodeserviceaccountdetails', 'local_learningpool'));
        
        $mform->addElement('text', 'serviceurl', get_string('postcodelookupserviceurl', 'local_learningpool'));
        $mform->addHelpButton('serviceurl', 'postcodelookupserviceurl', 'local_learningpool');
        $mform->setType('serviceurl', PARAM_RAW);
        
        if (!empty($config->serviceurl)) {
            $mform->setDefault('serviceurl', $config->serviceurl);
        }
        
        $mform->addElement('text', 'account', get_string('postcodelookupaccount', 'local_learningpool'));
        $mform->addHelpButton('account', 'postcodelookupaccount', 'local_learningpool');
        $mform->setType('account', PARAM_RAW);
        if (!empty($config->account)) {
            $mform->setDefault('account', $config->account);
        }
        
        $mform->addElement('text', 'password', get_string('postcodelookuppassword', 'local_learningpool'));
        $mform->addHelpButton('password', 'postcodelookuppassword', 'local_learningpool');
        $mform->setType('password', PARAM_RAW);
        if (!empty($config->password)) {
            $mform->setDefault('password', $config->password);
        }
        
        $mform->addElement('header', 'postcodeservicefieldmappings', get_string('postcodeservicefieldmappings', 'local_learningpool'));
        
        $mform->addElement('text', 'field_address1', get_string('postcodelookupfield_address1', 'local_learningpool'));
        $mform->addHelpButton('field_address1', 'postcodelookupfield_address1', 'local_learningpool');
        $mform->setType('field_address1', PARAM_RAW);
        if (!empty($config->field_address1)) {
            $mform->setDefault('field_address1', $config->field_address1);
        }
        
        $mform->addElement('text', 'field_address2', get_string('postcodelookupfield_address2', 'local_learningpool'));
        $mform->addHelpButton('field_address2', 'postcodelookupfield_address2', 'local_learningpool');
        $mform->setType('field_address2', PARAM_RAW);
        if (!empty($config->field_address2)) {
            $mform->setDefault('field_address2', $config->field_address2);
        }
        
        $mform->addElement('text', 'field_address3', get_string('postcodelookupfield_address3', 'local_learningpool'));
        $mform->addHelpButton('field_address3', 'postcodelookupfield_address3', 'local_learningpool');
        $mform->setType('field_address3', PARAM_RAW);
        if (!empty($config->field_address3)) {
            $mform->setDefault('field_address3', $config->field_address3);
        }
        
        $mform->addElement('text', 'field_address4', get_string('postcodelookupfield_address4', 'local_learningpool'));
        $mform->addHelpButton('field_address4', 'postcodelookupfield_address4', 'local_learningpool');
        $mform->setType('field_address4', PARAM_RAW);
        if (!empty($config->field_address4)) {
            $mform->setDefault('field_address4', $config->field_address4);
        }
        
        $mform->addElement('text', 'field_town', get_string('postcodelookupfield_town', 'local_learningpool'));
        $mform->addHelpButton('field_town', 'postcodelookupfield_town', 'local_learningpool');
        $mform->setType('field_town', PARAM_RAW);
        if (!empty($config->field_town)) {
            $mform->setDefault('field_town', $config->field_town);
        }
        
        $mform->addElement('text', 'field_postcode', get_string('postcodelookupfield_postcode', 'local_learningpool'));
        $mform->addHelpButton('field_postcode', 'postcodelookupfield_postcode', 'local_learningpool');
        $mform->setType('field_postcode', PARAM_RAW);
        if (!empty($config->field_postcode)) {
            $mform->setDefault('field_postcode', $config->field_postcode);
        }
        
        $mform->addElement('text', 'field_country', get_string('postcodelookupfield_country', 'local_learningpool'));
        $mform->addHelpButton('field_country', 'postcodelookupfield_country', 'local_learningpool');
        $mform->setType('field_country', PARAM_RAW);
        if (!empty($config->field_country)) {
            $mform->setDefault('field_country', $config->field_country);
        }
        
        $this->add_action_buttons();
    }
    
    function process() {
        $data = $this->get_data();
        if (empty($data)) {
            return false;
        }
        
        if (isset($data->usepostcodelookup)) {
            set_config('usepostcodelookup', $data->usepostcodelookup, 'learningpool/postcodelookup');
        }
        
        if (isset($data->serviceurl)) {
            set_config('serviceurl', $data->serviceurl, 'learningpool/postcodelookup');
        }
        
        if (isset($data->account)) {
            set_config('account', $data->account, 'learningpool/postcodelookup');
        }
        
        if (isset($data->password)) {
            set_config('password', $data->password, 'learningpool/postcodelookup');
        }
        
        if (isset($data->field_address1)) {
            set_config('field_address1', $data->field_address1, 'learningpool/postcodelookup');
        }
        
        if (isset($data->field_address2)) {
            set_config('field_address2', $data->field_address2, 'learningpool/postcodelookup');
        }
        
        if (isset($data->field_address3)) {
            set_config('field_address3', $data->field_address3, 'learningpool/postcodelookup');
        }
        
        if (isset($data->field_address4)) {
            set_config('field_address4', $data->field_address4, 'learningpool/postcodelookup');
        }
        
        if (isset($data->field_town)) {
            set_config('field_town', $data->field_town, 'learningpool/postcodelookup');
        }
        
        if (isset($data->field_country)) {
            set_config('field_country', $data->field_country, 'learningpool/postcodelookup');
        }
        
        if (isset($data->field_postcode)) {
            set_config('field_postcode', $data->field_postcode, 'learningpool/postcodelookup');
        }
        
        return true;
    }

}