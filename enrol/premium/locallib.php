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
 * Premium enrol plugin implementation for internal users
 *
 * @package    enrol
 * @subpackage premium
 * @copyright  2013 Kevin Corry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class premium_enrol_internal_form extends moodleform {
    protected $instance;
    
    /**
     * Overriding this function to get unique form id for multiple self enrolments
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $formid = $this->_customdata->id.'_'.get_class($this);
        return $formid;
    }

    public function definition() {
        global $DB;

        $mform = $this->_form;
        $instance = $this->_customdata;
        $plugin = enrol_get_plugin('premium');

        $heading = $plugin->get_instance_name($instance);
        $mform->addElement('header', 'selfheader', $heading);

        $mform->addElement('static', 'nokey', '', get_string('freeinternal', 'enrol_premium'));
        $this->add_action_buttons(false, get_string('enrolme', 'enrol_premium'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $instance->courseid);

        $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $mform->setDefault('instance', $instance->id);
    }

    public function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);
        $instance = $this->instance;

        return $errors;
    }
}

class premium_enrol_manual_form extends moodleform {
    protected $instance;
    
    /**
     * Overriding this function to get unique form id for multiple self enrolments
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $formid = $this->_customdata->id.'_'.get_class($this);
        return $formid;
    }

    public function definition() {
        global $DB;

        $mform = $this->_form;
        $instance = $this->_customdata;
        $plugin = enrol_get_plugin('premium');

        $heading = $plugin->get_instance_name($instance);
        $mform->addElement('header', 'selfheader', $heading);

        $mform->addElement('static', 'nokey', '', get_string('premiummanualenrol', 'enrol_premium'));
        $this->add_action_buttons(false, get_string('enrolme', 'enrol_premium'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $instance->courseid);

        $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $mform->setDefault('instance', $instance->id);
    }

    public function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);
        $instance = $this->instance;

        return $errors;
    }
}
