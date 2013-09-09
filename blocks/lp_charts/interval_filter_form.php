<?php

/**
 * Formslib template for generating an export report form
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once "$CFG->dirroot/lib/formslib.php";

class chart_interval_filter_form extends moodleform {
    /**
     * Definition of the export report form
     */
    function definition() {
        $id = optional_param('id', 0, PARAM_INT);
        
        $select = array('7 DAY'=> get_string('thisweek', 'block_lp_charts'), 
                        '1 MONTH' => get_string('lastmonth', 'block_lp_charts'), 
                        '3 MONTH' => get_string('last3months', 'block_lp_charts'),
                        '6 MONTH' => get_string('last6months', 'block_lp_charts'),
                        '12 MONTH' => get_string('lastyear', 'block_lp_charts')
            );
        
        $mform =& $this->_form;
        // show pulldown menu
        $group=array();
        $group[] =& $mform->createElement('select','interval', null, $select);
        $group[] =& $mform->createElement('hidden', 'id', $id);
        $group[] =& $mform->createElement('submit', 'filter', get_string('filter','block_lp_charts'));
        $mform->setType('id', PARAM_INT);
        $mform->addGroup($group, 'filtergroup', '', array(' '), false); 
    }
}