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
 * This file keeps track of upgrades to the learning pool enrolment manager
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 * *
 * @package    local
 * @subpackage lp_enrolment_manager
 * @copyright  2011 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_local_lp_enrolment_manager_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    /// Add a new column newcol to the question_myqtype
    if ($oldversion < 2011113001) {

        // Define table lp_enrol_email to be created
        $table = new xmldb_table('lp_enrol_email');

        // Adding fields to table lp_enrol_email
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sender', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('body', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

        // Adding keys to table lp_enrol_email
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_course', XMLDB_KEY_FOREIGN, array('course'), 'course', array('id'));

        // Conditionally launch create table for lp_enrol_email
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Define table lp_enrol_email_queue to be created
        $table = new xmldb_table('lp_enrol_email_queue');

        // Adding fields to table lp_enrol_email_queue
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('template', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('sent', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table lp_enrol_email_queue
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_template', XMLDB_KEY_FOREIGN, array('template'), 'lp_enrol_email', array('id'));
        $table->add_key('fk_user', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Conditionally launch create table for lp_enrol_email_queue
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // lp_enrolment_manager savepoint reached
        upgrade_plugin_savepoint(true, 2011113001, 'local', 'lp_enrolment_manager');
    }
    

    return true;
}
