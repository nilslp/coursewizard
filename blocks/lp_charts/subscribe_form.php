<?php

/**
 * Formslib template for generating an export report form
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once "$CFG->dirroot/lib/formslib.php";

class chart_subscribe_form extends moodleform {
    /**
     * Definition of the export report form
     */
    function definition() {
        $mform =& $this->_form;
        
        $id = required_param('id', PARAM_INT);
        $interval = optional_param('interval', '7 DAY', PARAM_TEXT);
        
        $select_data = array('daily' => get_string('daily', 'block_lp_charts'),
                            'weekly' => get_string('weekly', 'block_lp_charts'),
                            'monthly' => get_string('monthly', 'block_lp_charts'));
        /*
        $mform->createElement('select','interval', array('style' => 'display: none'), $select);
        // show pulldown menu
        $group=array();
        $group[] =& $mform->createElement('select','interval', array('style' => 'display: none'), $select);
        $group[] = & $mform->createElement('select', 'id', null, array($id => $id));
        $group[] =& $mform->createElement('submit', 'filter', get_string('filter','block_lp_charts'));
        */
        //$mform->addElement('select', 'id', null, array($id => $id));
        $group = array();
        
        $group[] =& $mform->createElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $group[] =& $mform->createElement('hidden', 'interval', $interval);
        $mform->setType('interval', PARAM_TEXT);
        $group[] =& $mform->createElement('select', 'emailschedule', get_string('emaillabel', 'block_lp_charts'), $select_data);
        
        $group[] =& $mform->createElement('submit', 'subscribe', get_string('subscribe', 'block_lp_charts'));

        $mform->addGroup($group, 'subscriptiongroup', '', array(' '), false);
    }
}