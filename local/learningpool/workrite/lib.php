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
 * Legacy scorm functionality managed here
 *
 * @package    local
 * @subpackage learningpool
 * @copyright  2012 Learning Pool
 * @author     Pete Smith
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/mod/scorm/locallib.php');

class local_lp_workriteform extends moodleform {

    function definition() {
        global $CFG;

        $mform = &$this->_form;

        $mform->addElement('header', 'settings', get_string('workritecategory', 'local_learningpool'));

        //textboxes
        $mform->addElement('text', 'coSsoId', get_string('coSsoId', 'local_learningpool'));
        $mform->addElement('text', 'coWsId', get_string('coWsId', 'local_learningpool'));
        $mform->addElement('text', 'coLogin', get_string('coLogin', 'local_learningpool'));
        $mform->addElement('text', 'coPassword', get_string('coPassword', 'local_learningpool'));
        $mform->addElement('text', 'role', get_string('role', 'local_learningpool'));
        $mform->addElement('text', 'places', get_string('places', 'local_learningpool'));
        $mform->addElement('text', 'wsdlurl', get_string('wsdlurl', 'local_learningpool'));
        $mform->addElement('text', 'linktext', get_string('linktext', 'local_learningpool'));

        //help
        $mform->addHelpButton('coSsoId', 'coSsoId', 'local_learningpool');
        $mform->addHelpButton('coWsId', 'coWsId', 'local_learningpool');
        $mform->addHelpButton('coLogin', 'coLogin', 'local_learningpool');
        $mform->addHelpButton('coPassword', 'coPassword', 'local_learningpool');
        $mform->addHelpButton('role', 'role', 'local_learningpool');
        $mform->addHelpButton('places', 'places', 'local_learningpool');
        $mform->addHelpButton('wsdlurl', 'wsdlurl', 'local_learningpool');
        $mform->addHelpButton('linktext', 'linktext', 'local_learningpool');


        $this->add_action_buttons();
    }

    function process() {


        //global $DB,$USER,$OUTPUT;

        $data = $this->get_data();
        if (empty($data)) {
            return true;
        }


        set_config('coSsoId', $data->coSsoId, 'soap_login');
        set_config('coWsId', $data->coWsId, 'soap_login');
        set_config('coLogin', $data->coLogin, 'soap_login');
        set_config('coPassword', $data->coPassword, 'soap_login');
        set_config('role', $data->role, 'soap_login');
        set_config('places', $data->places, 'soap_login');
        set_config('wsdlurl', $data->wsdlurl, 'soap_login');
        set_config('linktext', $data->linktext, 'soap_login');
    }

}
