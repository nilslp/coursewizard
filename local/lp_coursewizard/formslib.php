<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');

class lpcoursewizardform extends moodleform {

    function definition() {
        global $CFG;

        $mform = &$this->_form;

        $mform->addElement('header', 'general', get_string('general'));
        
        $config = get_config('local/local_lp_coursewizard');
        
        // issue 6788 - force login on calendar/view.php
        $mform->addElement('text', 'templatelocation', get_string('templatelocation', 'templatelocation') );
        if (isset($config->templatelocation)) {
            $mform->setDefault('templatelocation', $config->templatelocation);
        }
        
        $this->add_action_buttons();
    }
    
    function process() {
        $data = $this->get_data();
        if (empty($data)) {
            return false;
        }
        
        if (isset($data->templatelocation)) {
            set_config('templatelocation', $data->templatelocation, 'local/local_lp_coursewizard');
        }
        
        return true;
    }

}