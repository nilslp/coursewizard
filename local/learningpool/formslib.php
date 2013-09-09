<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');

class dleglobalsettingsform extends moodleform {

    function definition() {
        global $CFG;

        $mform = &$this->_form;

        $mform->addElement('header', 'general', get_string('general'));
        
        $config = get_config('local/learningpool');
        
        // issue 6788 - force login on calendar/view.php
        $mform->addElement('advcheckbox', 'forcecalendarlogin', get_string('forcecalendarlogin', 'local_learningpool'), '', null, array(0,1) );
        $mform->addHelpButton('forcecalendarlogin', 'forcecalendarlogin', 'local_learningpool');
        if (isset($config->forcecalendarlogin)) {
            $mform->setDefault('forcecalendarlogin', $config->forcecalendarlogin);
        }
        
        $this->add_action_buttons();
    }
    
    function process() {
        $data = $this->get_data();
        if (empty($data)) {
            return false;
        }
        
        if (isset($data->forcecalendarlogin)) {
            set_config('forcecalendarlogin', $data->forcecalendarlogin, 'local/learningpool');
        }
        
        return true;
    }

}