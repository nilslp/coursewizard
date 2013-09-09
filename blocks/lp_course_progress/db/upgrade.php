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
 * This file keeps track of upgrades to the lp_course_progress block
 *
 * @package blocks
 * @subpackage lp_course_progress
 * @copyright 2012 Learning Pool
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_lp_course_progress_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012030800) {
        // Define table lp_incomplete_notification to be created
        $table = new xmldb_table('lp_incomplete_notification');

        // Adding fields to table lp_incomplete_notification
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('description', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('startdate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('courses', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('frequency', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('next', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);

        // Adding keys to table lp_incomplete_notification
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for lp_incomplete_notification
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // lp_course_progress savepoint reached
        upgrade_block_savepoint(true, 2012030800, 'lp_course_progress');
        
        // Set the default configs
        set_config('persistordering', '0', 'block_lp_course_progress');
        set_config('scoremessagedisplayoption', '1', 'block_lp_course_progress');
        set_config('showallcourses', '0', 'block_lp_course_progress');        
    }
        
    if ($oldversion < 2012042400) {
        require_once($CFG->dirroot.'/blocks/lp_course_progress/lib.php');
        
        // lp_course_progress savepoint reached
        upgrade_block_savepoint(true, 2012042400, 'lp_course_progress');            
        
        // update configs
        $show_all = get_config('block_lp_course_progress', 'showallcourses');
        if ($show_all) {
            set_config('splitallcourses', LP_DISPLAY_COURSE_TABS_ALL, 'block_lp_course_progress');  
        } else {
            set_config('splitallcourses', LP_DISPLAY_COURSE_TABS_ENROLLED_ONLY, 'block_lp_course_progress');
        }
    }
        

    // Moodle v2.1.0 release upgrade line
    // Put any upgrade step following this

    // Moodle v2.2.0 release upgrade line
    // Put any upgrade step following this
    if ($oldversion < 2013071802) {
        // Check if the historic table exists
        $dbman = $DB->get_manager();
        
        // Define table lp_incomplete_notification to be created
        $oldtable = new xmldb_table('lp_incomplete_notify');
        
        if (!$dbman->table_exists($oldtable)) {
            // This is a fairly recent instance
            set_config('migrated', 1, 'block_lp_course_progress');
        }
    }
    
    return true;
}
