<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010, 2011 Totara Learning Solutions LTD
 * 
 * This program is free software; you can redistribute it and/or modify  
 * it under the terms of the GNU General Public License as published by  
 * the Free Software Foundation; either version 2 of the License, or     
 * (at your option) any later version.                                   
 *                                                                       
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @package totara
 * @subpackage reportbuilder 
 */

/**
* This file keeps track of upgrades to
* the reportbuilder module
*
* Sometimes, changes between versions involve
* alterations to database structures and other
* major things that may break installations.
*
* The upgrade function in this file will attempt
* to perform all the necessary actions to upgrade
* your older installtion to the current version.
*
* If there's something it cannot do itself, it
* will tell you what you need to do.
*
* The commands in here will all be database-neutral,
* using the functions defined in lib/ddllib.php
*/

function xmldb_block_lp_reportbuilder_upgrade($oldversion=0) {

    global $CFG, $DB, $OUTPUT;
    $result = true;

    /*if ($result && $oldversion < 2010081901) {
        // hack to get cron working via admin/cron.php
        // at some point we should create a local_modules table
        // based on data in version.php
        set_config('local_reportbuilder_cron', 60);
    }
    */

    if ($result && $oldversion < 2010090200) {
        if($reports = $DB->get_records_select('report_builder', 'embeddedurl IS NOT NULL')) {
            foreach($reports as $report) {
                $url = $report->embeddedurl;
                // remove the wwwroot from the url
                if($CFG->wwwroot == substr($url, 0, strlen($CFG->wwwroot))) {
                    $url = substr($url, strlen($CFG->wwwroot));
                }
                // check to fix embedded urls with wrong host
                // this should fix all historical cases as up to now all embedded reports
                // have been in the /my/ directory
                // this does nothing if '/my/' not in url or
                // url already without wwwroot
                $url = substr($url, strpos($url, '/my/'));

                // do the update if needed
                if($report->embeddedurl != $url) {
                    $todb = new object();
                    $todb->id = $report->id;
                    $todb->embeddedurl = addslashes($url);
                    $result = $result && $DB->update_record('report_builder', $todb);
                }
            }
        }
    }

    // add various table settings to report_builder table
    if ($result && $oldversion < 2010090900) {
        /// Define field recordsperpage to be added to report_builder
        $table = new XMLDBTable('report_builder');
        $field = new XMLDBField('recordsperpage');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '40', 'description');
        /// Launch add field recordsperpage
        $result = $result && $DB->get_manager()->add_field($table, $field);

        /// Define field defaultsortcolumn to be added to report_builder
        $field = new XMLDBField('defaultsortcolumn');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null, 'recordsperpage');
        /// Launch add field defaultsortcolumn
        $result = $result && $DB->get_manager()->add_field($table, $field);

        $field = new XMLDBField('defaultsortorder');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, 4, 'defaultsortcolumn');
        /// Launch add field defaultsortorder
        $result = $result && $DB->get_manager()->add_field($table, $field);
    }

    // tables for scheduled reports
    if ($result && $oldversion < 2010101200) {
        $table = new XMLDBTable('report_builder_schedule');
        if(!table_exists($table)) {
            $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
            $table->addFieldInfo('reportid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('savedsearchid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('format', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('frequency', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('schedule', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('nextreport', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);

            /// Adding keys to table report_builder_group
            $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

            $DB->get_manager()->create_table($table);
        }
    }

    if ($result && $oldversion < 2010122300) {

        /// Define field embedded to be added to report_builder
        $table = new XMLDBTable('report_builder');
        $field = new XMLDBField('embedded');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'description');

        /// Launch add field embedded
        $result = $result && $DB->get_manager()->add_field($table, $field);

        // update for existing records
        $sql = "UPDATE {$CFG->prefix}report_builder SET embedded=1 WHERE embeddedurl IS NOT NULL";
        $result = $result && $DB->execute($sql);

        // now drop embeddedurl column
        $field = new XMLDBField('embeddedurl');
         /// Launch drop field embeddedurl
        $result = $result && $DB->get_manager()->drop_field($table, $field);
    }

    if ($result && $oldversion < 2011011800) {

        /// Remove urgency column from Notifications and Reminders embedded reports
        $sql = "DELETE FROM {$CFG->prefix}report_builder_columns
            WHERE value = 'urgency' AND reportid IN (SELECT id FROM {$CFG->prefix}report_builder
                WHERE shortname IN ('notifications', 'reminders'))";
        $result = $result && $DB->execute($sql);

        /// Remove urgency filter from Notifications and Reminders embedded reports
        $sql = "DELETE FROM {$CFG->prefix}report_builder_filters
            WHERE value = 'urgency' AND reportid IN (SELECT id FROM {$CFG->prefix}report_builder
                WHERE shortname IN ('notifications', 'reminders'))";
        $result = $result && $DB->execute($sql);

    }

    if ($result && $oldversion < 2011011801) {

        /// Remove 'Plan' and 'Plan Status' cols from 'ROL courses' report
        $sql = "DELETE FROM {$CFG->prefix}report_builder_columns
            WHERE (value = 'planlink' OR value='status')
            AND reportid IN (SELECT id FROM {$CFG->prefix}report_builder
                WHERE shortname IN ('plan_courses'))";
        $result = $result && $DB->execute($sql);

    }

    if ($result && $oldversion < 2011020100) {
        // replace 'msgtype' filter with 'category' in alerts/tasks
        $sql = "UPDATE {$CFG->prefix}report_builder_filters
            SET value = 'category'
            WHERE (type = 'message_values' AND value = 'msgtype')
            AND reportid IN (SELECT id FROM {$CFG->prefix}report_builder
                WHERE source = 'totaramessages')";
        $result = $result && $DB->execute($sql);

    }

    if($result && $oldversion < 2011031400) {
        $custom_field_locations = array(
            'session' => 'facetoface_session_field',
            'user_profile' => 'user_info_field',
            'competency' => 'comp_depth_info_field',
            'position' => 'pos_depth_info_field',
            'organisation' => 'org_depth_info_field',
            'course' => 'course_info_field'
        );

        foreach($custom_field_locations as $type => $location){
            if($cust_fields = $DB->get_records($location)) {
                foreach($cust_fields as $c) {
                    $columns = $DB->get_records('report_builder_columns', 'type', $type);
                    if($columns) {
                        foreach($columns as $col) {
                            if($col->value == $c->shortname) {
                                $prefix = ($type == 'user_profile') ? 'user' : $type;
                                $newrec = new object();
                                $newrec->id = $col->id;
                                $newrec->value = $prefix . '_' . $c->id;
                                $result = $result && $DB->update_record('report_builder_columns', $newrec);
                            }
                        }
                    }
                }
            }
        }
    }


    if ($result && $oldversion < 2011040400) {
        $sql = "SELECT * FROM {$CFG->prefix}report_builder_columns WHERE type='user' AND value like 'user_%'";
        $rb_columns_result = $DB->get_records_sql($sql);

        //Revert incorrect upgrade on user fields
        if($rb_columns_result){
            foreach($rb_columns_result as $r) {
                if($title = $DB->get_field('user_info_field', 'shortname', 'id', (int)substr($r->value, 5))) {
                    $newrec = new object();
                    $newrec->id = $r->id;
                    $newrec->value = $title;
                    $result = $result && $DB->update_record('report_builder_columns', $newrec);
                }
            }
        }

        //Update user_profile fields correctly
        if($cust_fields = $DB->get_records('user_info_field')) {
            foreach($cust_fields as $c) {
                $columns = $DB->get_records('report_builder_columns', 'type', 'user_profile');
                if($columns) {
                    foreach($columns as $col) {
                        if($col->value == $c->shortname) {
                            $newrec = new object();
                            $newrec->id = $col->id;
                            $newrec->value = 'user_' . $c->id;
                            $result = $result && $DB->update_record('report_builder_columns', $newrec);
                        }
                    }
                }
            }
        }

        //Update report builder filters
        $custom_field_locations = array(
            'session' => 'facetoface_session_field',
            'user_profile' => 'user_info_field',
            'competency' => 'comp_depth_info_field',
            'position' => 'pos_depth_info_field',
            'organisation' => 'org_depth_info_field',
            'course' => 'course_info_field'
        );

        foreach($custom_field_locations as $type => $location){
            if($cust_fields = $DB->get_records($location)) {
                foreach($cust_fields as $c) {
                    $columns = $DB->get_records('report_builder_filters', 'type', $type);
                    if($columns) {
                        foreach($columns as $col) {
                            if($col->value == $c->shortname) {
                                $prefix = ($type == 'user_profile') ? 'user' : $type;
                                $newrec = new object();
                                $newrec->id = $col->id;
                                $newrec->value = $prefix . '_' . $c->id;
                                $result = $result && $DB->update_record('report_builder_filters', $newrec);
                            }
                        }
                    }
                }
            }
        }
    }

    if ($result && $oldversion < 2011051200) {
        // move course type icon columnoption from its own section into 'course' section
        $sql = "UPDATE {$CFG->prefix}report_builder_columns
            SET type='course'
            WHERE type='course_info_data' AND value='coursetypeicon'";
        $result = $result && $DB->execute($sql);
    }

    if ($result && $oldversion < 2011071200) {
        // correct bad lang strings saved to columns table
        $fixheading = array(
            'sessionname' => 'sessname',
            'sessiondate' => 'sessdate',
            'starttime' => 'sessstart',
            'endtime' => 'sessfinish',
        );
        foreach ($fixheading as $before => $after) {
            $sql = "UPDATE {$CFG->prefix}report_builder_columns
                SET heading='" . get_string($after, 'rb_source_facetoface_sessions') . "'
                WHERE heading='[[$before]]'
                AND reportid IN (
                    SELECT id FROM {$CFG->prefix}report_builder
                    WHERE source = 'facetoface_sessions'
                )";
            $result = $result && $DB->execute($sql);

        }
    }

    if ($result && $oldversion < 2011081900) {

        // fail upgrade if any settings are > 100 chars (only possible with local customisations)
        if ($DB->record_exists_select('report_builder_settings', $DB->sql_length('type') . ' > 100')) {
            notify("Record in report settings table 'type' field is longer than 100 characters");
            return false;
        }

        if ($DB->record_exists_select('report_builder_settings', $DB->sql_length('name') . ' > 100')) {
            notify("Record in report settings table 'name' field is longer than 100 characters");
            return false;
        }

        // shorten the fields to a maximum of 100 characters
        $table = new XMLDBTable('report_builder_settings');
        $field = new XMLDBField('type');
        $field->setAttributes(XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $result = $result && $DB->get_manager()->change_field_precision($table, $field);

        $field = new XMLDBField('name');
        $field->setAttributes(XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $result = $result && $DB->get_manager()->change_field_precision($table, $field);
    }

    if ($result && $oldversion < 2012051001) {
        echo $OUTPUT->notification('Updating vw_lp_course_students view...','notifysuccess');
        
        $sql = "DROP VIEW IF EXISTS vw_lp_course_students";
    
        $result = $DB->execute($sql);

        $sql = "CREATE VIEW vw_lp_course_students
                AS
                SELECT ue.id, 
                    e.courseid, 
                    ue.userid, 
                    ue.timestart,
                    ue.timeend,
                    h.hierarchyid, 
                    h.level1 AS `level1`,
                    h.level2 AS `level2`,
                    h.level3 AS `level3`,
                    h.level4 AS `level4`,
                    h.level5 AS `level5`,
                    u.deleted 
                FROM {$CFG->prefix}enrol e
                INNER JOIN {$CFG->prefix}user_enrolments ue ON ue.enrolid = e.id
                INNER JOIN vw_lp_user_hierarchy h ON h.userid = ue.userid
                INNER JOIN {$CFG->prefix}user u ON u.id = ue.userid
                WHERE e.roleid = 5
                    AND ue.timestart != 0
                    AND ue.timeend = 0";
    
        $result = $DB->execute($sql);
    }

    if ($result && $oldversion < 2012072000) {
        echo $OUTPUT->notification('Updating vw_lp_course_students view...','notifysuccess');
        
        $sql = "DROP VIEW IF EXISTS vw_lp_course_students";
    
        $result = $DB->execute($sql);

        $sql = "CREATE VIEW vw_lp_course_students
                AS
                SELECT ue.id, 
                    e.courseid, 
                    ue.userid, 
                    ue.timestart,
                    ue.timeend,
                    h.hierarchyid, 
                    h.level1 AS `level1`,
                    h.level2 AS `level2`,
                    h.level3 AS `level3`,
                    h.level4 AS `level4`,
                    h.level5 AS `level5`,
                    u.deleted 
                FROM {$CFG->prefix}enrol e
                INNER JOIN {$CFG->prefix}user_enrolments ue ON ue.enrolid = e.id
                INNER JOIN vw_lp_user_hierarchy h ON h.userid = ue.userid
                INNER JOIN {$CFG->prefix}user u ON u.id = ue.userid
                WHERE e.roleid = 5
                    AND ue.timeend = 0";
    
        $result = $DB->execute($sql);
    }
         
    // tables for completion summaries
    if ($result && $oldversion < 2012100400) { 
        // reset embedded reports ...
        echo $OUTPUT->notification("Resetting embedded reports (Please visit <a href='{$CFG->wwwroot}/blocks/lp_reportbuilder/index.php'>{$CFG->wwwroot}/blocks/lp_reportbuilder/index.php</a> to complete installation)",'notifysuccess');
        
        $sql = "DELETE FROM {$CFG->prefix}report_builder_settings WHERE reportid in (SELECT id FROM {$CFG->prefix}report_builder WHERE embedded = 1)";
	
        $result = $DB->execute($sql);
        
        $sql = "DELETE FROM {$CFG->prefix}report_builder_filters WHERE reportid in (SELECT id FROM {$CFG->prefix}report_builder WHERE embedded = 1)";
	
        $result = $DB->execute($sql);
        
        $sql = "DELETE FROM {$CFG->prefix}report_builder_columns WHERE reportid in (SELECT id FROM {$CFG->prefix}report_builder WHERE embedded = 1)";

        $result = $DB->execute($sql);
        
        $sql = "DELETE FROM {$CFG->prefix}report_builder WHERE embedded = 1";

        $result = $DB->execute($sql);

        // add completion summary table
        $table = new xmldb_table('report_builder_module_comp');
        //  function add_field($name, $type, $precision=null, $unsigned=null, $notnull=null, $sequence=null, $default=null, $previous=null)
        /*
            * 
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('possiblevalues', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
            */
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('timestart', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('timeend', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('hierarchyid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('level1', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null);
        $table->add_field('level2', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('level3', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('level4', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('level5', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('moduleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('moduleinstance', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('completionstate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('moduletype', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('viewed', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$DB->get_manager()->table_exists($table)) {
            $DB->get_manager()->create_table($table);
        }
    }

    if ($result && $oldversion < 2012101201) {
        $result = true; // This isn't correct but add_field no longer returns true/false
        
        /// Define field createdby to be added to report_builder_schedule
        $table = new xmldb_table('report_builder_schedule');
        
        $createdby_field = new xmldb_field('createdby');
        $createdby_field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'nextreport');
        /// Add field createdby
        if (!$DB->get_manager()->field_exists($table, $createdby_field)) {
            $DB->get_manager()->add_field($table, $createdby_field);
        }
        
        /// Define field timecreated to be added to report_builder_schedule
        $timecreated_field = new xmldb_field('timecreated');
        $timecreated_field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0'); 
        /// Add field timecreated
        if (!$DB->get_manager()->field_exists($table, $timecreated_field)) {
            $DB->get_manager()->add_field($table, $timecreated_field);
        }

        $onbehalfof_field = new xmldb_field('onbehalfof');
        $onbehalfof_field->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        /// Launch add field onbehalfof
        if (!$DB->get_manager()->field_exists($table, $onbehalfof_field)) {
            $DB->get_manager()->add_field($table, $onbehalfof_field);        
        }
    }
    
    if ($result && $oldversion < 2012101800) {
         $sql = "UPDATE {$CFG->prefix}config_plugins
                SET value = '0'
                WHERE plugin = 'reportbuilder' and name = 'cron_lock'";
         
        $result = $DB->execute($sql);
    }
    
    if ($result && $oldversion < 2012102501) {
         // add scorm summary table
        $table = new xmldb_table('report_builder_scorm_completions');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('scormid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('scoid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('attempt', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('score', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('starttime', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('totaltime', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('use_scorm_sco_att_uix',XMLDB_INDEX_UNIQUE, array('userid','scormid','scoid','attempt'));
            
        if (!$DB->get_manager()->table_exists($table)) {
            $DB->get_manager()->create_table($table);
        }
    }
    
    if ($result && $oldversion < 2012110100) {
        // Holding table for course completion data and use in course_users source
        $table = new xmldb_table('report_builder_course_comp');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('completion_status', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('timeaccess', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('timestarted', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('course_ix', XMLDB_INDEX_NOTUNIQUE, array('course'));
        $table->add_index('user_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_index('completion_status_ix', XMLDB_INDEX_NOTUNIQUE, array('completion_status'));
        
        if (!$DB->get_manager()->table_exists($table)) {
            $DB->get_manager()->create_table($table);
        }
    }
    
    if ($result && $oldversion < 2012110600) {        
        // add timecomplete field to report_builder_scorm_completions
        $table = new xmldb_table('report_builder_scorm_completions');
        
        $field = new xmldb_field('timecomplete');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'totaltime');
        
        if (!$DB->get_manager()->field_exists($table, $field)) {
            $DB->get_manager()->add_field($table, $field);
        }
    }
    
    if ($result && $oldversion < 2012110900) {
        set_config('coursecompletionprocessed', 0, 'reportbuilder');   // Default to 0
    }
    
    if ($result && $oldversion < 2012111500) {        
        // Add completion_enabled field to report_builder_module_comp
        // (Previously courses that didn't have completion tracking enabled
        // were not being returned)
        $table = new xmldb_table('report_builder_module_comp');
        
        $field = new xmldb_field('completionenabled');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, '0', 'timemodified');
        
        if (!$DB->get_manager()->field_exists($table, $field)) {
            $DB->get_manager()->add_field($table, $field);
        }
        
        // Add scorm_status to report_builder_scorm_completions table
        // This will be used to hold the status extracted from the status from 
        // the SCORM records directly
        $table = new xmldb_table('report_builder_scorm_completions');
        
        $field = new xmldb_field('status');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null);
        
        if (!$DB->get_manager()->field_exists($table, $field)) {
            $DB->get_manager()->add_field($table, $field);
        }
    }
    
    
    if ($result && $oldversion < 2012120401) {       
        $table = new xmldb_table('report_builder_scorm_completions');
        
        $field = new xmldb_field('lastaccess');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'status');
        
        if (!$DB->get_manager()->field_exists($table, $field)) {
            $DB->get_manager()->add_field($table, $field);
        }
    }
    
    if ($result && $oldversion < 2013011701) {
        echo $OUTPUT->notification('Updating vw_lp_course_students view...','notifysuccess');
        
        $sql = "DROP VIEW IF EXISTS vw_lp_course_students";
    
        $result = $DB->execute($sql);

        $sql = "CREATE VIEW vw_lp_course_students
                AS
                SELECT ue.id, 
                    e.courseid, 
                    ue.userid, 
                    ue.timecreated,
                    ue.timestart,
                    ue.timeend,
                    h.hierarchyid, 
                    h.level1 AS `level1`,
                    h.level2 AS `level2`,
                    h.level3 AS `level3`,
                    h.level4 AS `level4`,
                    h.level5 AS `level5`,
                    u.deleted 
                FROM {$CFG->prefix}enrol e
                INNER JOIN {$CFG->prefix}user_enrolments ue ON ue.enrolid = e.id
                INNER JOIN vw_lp_user_hierarchy h ON h.userid = ue.userid
                INNER JOIN {$CFG->prefix}user u ON u.id = ue.userid
                WHERE e.roleid = 5
                    AND ue.timeend = 0";
    
        $result = $DB->execute($sql);
    }
        
    if ($result && $oldversion < 2013011802) {
        // Default the date/time formats
        set_config('defaultdateformat', '%d %b %Y', 'reportbuilder');
        set_config('defaultdatetimeformat', '%d %b %Y at %H:%M', 'reportbuilder');
        set_config('defaulttimeformat', '%H:%M', 'reportbuilder');
    }
    
    if ($result && $oldversion < 2013012803) {       
        $table = new xmldb_table('report_builder_columns');
        
        $field = new xmldb_field('customheading');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        
        if (!$DB->get_manager()->field_exists($table, $field)) {
            $DB->get_manager()->add_field($table, $field);
        }
    }
    
    if ($result && $oldversion < 2013022200) {
        echo $OUTPUT->notification('Updating vw_lp_course_students view...','notifysuccess');
        
        $sql = "DROP VIEW IF EXISTS vw_lp_course_students";
    
        $result = $DB->execute($sql);

        $sql = "CREATE VIEW vw_lp_course_students
                AS
                SELECT ue.id, 
                    e.courseid, 
                    e.enrol,
                    ue.userid, 
                    ue.timecreated,
                    ue.timestart,
                    ue.timeend,
                    h.hierarchyid, 
                    h.level1 AS `level1`,
                    h.level2 AS `level2`,
                    h.level3 AS `level3`,
                    h.level4 AS `level4`,
                    h.level5 AS `level5`,
                    u.deleted 
                FROM {$CFG->prefix}enrol e
                INNER JOIN {$CFG->prefix}user_enrolments ue ON ue.enrolid = e.id
                INNER JOIN vw_lp_user_hierarchy h ON h.userid = ue.userid
                INNER JOIN {$CFG->prefix}user u ON u.id = ue.userid
                WHERE e.roleid = 5
                    AND ue.timeend = 0";
    
        $result = $DB->execute($sql);
    }
    
    if ($result && $oldversion < 2013042903) {        
        $table = new xmldb_table('report_builder_hierarchy');

        $index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch add the unique index
        if (!$DB->get_manager()->index_exists($table, $index)) {
            $DB->get_manager()->add_index($table, $index);
        }
    }
    
    if ($result && $oldversion < 2013071700) {
        $table_hierarchy_access = new xmldb_table('report_builder_hierarchy_access');
        
        if ($DB->get_manager()->table_exists($table_hierarchy_access)) {
            // Rename 'report_builder_hierarchy_access' to 'report_builder_hierarchy'
            $DB->get_manager()->rename_table($table_hierarchy_access, 'report_builder_hierarchy', true, true);
        }
        
        $table_module_completions = new xmldb_table('report_builder_module_completions');
        
        if ($DB->get_manager()->table_exists($table_module_completions)) {
            // Rename 'report_builder_module_completions' to 'report_builder_module_comp'
            $DB->get_manager()->rename_table($table_module_completions, 'report_builder_module_comp', true, true);
        }
        
        $table_course_completion = new xmldb_table('report_builder_course_completion');
        
        if ($DB->get_manager()->table_exists('report_builder_course_completion')) {
            // Rename 'report_builder_course_completion' to 'report_builder_course_comp'
            $DB->get_manager()->rename_table($table_course_completion, 'report_builder_course_comp', true, true);
        }
    }
    
    return $result;
}
