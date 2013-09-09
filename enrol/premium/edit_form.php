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
 * Adds new instance of enrol_paypal to specified course
 * or edits current instance.
 *
 * @package    enrol
 * @subpackage premium
 * @copyright  2012 Learning Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class enrol_premium_edit_form extends moodleform {

    function definition() {
        $mform = $this->_form;

        list($instance, $plugin, $context) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('pluginname', 'enrol_premium'));

        $mform->addElement('hidden', 'currency');
        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));

        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        $mform->addElement('select', 'status', get_string('status', 'enrol_premium'), $options);
        $mform->setDefault('status', $plugin->get_config('status'));

        $currency = get_config('block_shopping_basket', 'currency');
               
        if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $plugin->get_config('roleid'));
        }
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_premium'), $roles);
        $mform->setDefault('roleid', $plugin->get_config('roleid'));

        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'courseid');
        $mform->addElement('hidden', 'currency');
        $mform->setDefault('currency', $plugin->get_config('currency'));
        $mform->addElement('hidden', 'enrolperiod');
        $mform->setDefault('enrolperiod', 0);
        $mform->addElement('hidden', 'enrolstartdate');
        $mform->setDefault('enrolstartdate', 0);
        $mform->addElement('hidden', 'enrolenddate');
        $mform->setDefault('enrolenddate', 0);
        $mform->addElement('hidden', 'cost');
        // Assign a default cost to prevent free enrolment
        $mform->setDefault('cost', 0.01);

        $mform->addElement('advcheckbox', 'customint4', get_string('sendcoursewelcomemessage', 'enrol_premium'));
        $mform->setDefault('customint4', $plugin->get_config('sendcoursewelcomemessage'));
        $mform->addHelpButton('customint4', 'sendcoursewelcomemessage', 'enrol_premium');

        $mform->addElement('editor', 'customtext1', get_string('customwelcomemessage', 'enrol_premium'), array('cols'=>'60', 'rows'=>'8'));
        $mform->addHelpButton('customtext1', 'customwelcomemessage', 'enrol_premium');
        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }

    function validation($data, $files) {
        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        list($instance, $plugin, $context) = $this->_customdata;

        if ($data['status'] == ENROL_INSTANCE_ENABLED) {
            if (!empty($data['enrolenddate']) and $data['enrolenddate'] < $data['enrolstartdate']) {
                $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_premium');
            }

            if (!is_numeric($data['cost'])) {
                $errors['cost'] = get_string('costerror', 'enrol_premium');

            }
        }

        return $errors;
    }
}