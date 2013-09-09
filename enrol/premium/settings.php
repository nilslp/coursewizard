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
 * Paypal enrolments plugin settings and presets.
 *
 * @package    enrol
 * @subpackage premium
 * @copyright  2013 Learning Pool
 * @author     Brian Quinn
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    
    //--- settings ------------------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_premium_settings', '', get_string('pluginname_desc', 'enrol_premium')));

    //--- enrol instance defaults ----------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_premium_defaults',
        get_string('enrolinstancedefaults', 'admin'), get_string('enrolinstancedefaults_desc', 'admin')));

    $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                     ENROL_INSTANCE_DISABLED => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_premium/status',
        get_string('status', 'enrol_premium'), get_string('status_desc', 'enrol_premium'), ENROL_INSTANCE_DISABLED, $options));

    if (!during_initial_install()) {
        global $DB;
    
        // Get the student role
        $student = $DB->get_record('role', array('shortname' => 'student'));

        $options = get_default_enrol_roles(get_context_instance(CONTEXT_SYSTEM));

        $settings->add(new admin_setting_configselect('enrol_premium/roleid',
            get_string('defaultrole', 'enrol_premium'), get_string('defaultrole_desc', 'enrol_premium'), $student->id, $options));
    }
    
    $settings->add(new admin_setting_configcheckbox('enrol_premium/sendcoursewelcomemessage',
        get_string('sendcoursewelcomemessage', 'enrol_premium'), get_string('sendcoursewelcomemessage_help', 'enrol_premium'), 1));
    
    // Checkbox to choose whether or not internal users can enrol for free.
    // Only available when the internal hierarchy block is found
    if (file_exists($CFG->dirroot . '/blocks/internal_hierarchy/lib.php')) {
        $settings->add(new admin_setting_configcheckbox('enrol_premium/freeinternal', get_string('freeinternal', 'enrol_premium'), get_string('freeinternal_def', 'enrol_premium'), 0));
    }
    
    // Checkbox to choose whether or not to delay enrolment until the user browses to the course and clicks 'Enrol Me'
    $settings->add(new admin_setting_configcheckbox('enrol_premium/autoenrol', get_string('autoenrol', 'enrol_premium'), get_string('autoenrol_def', 'enrol_premium'), 1));
    
}
