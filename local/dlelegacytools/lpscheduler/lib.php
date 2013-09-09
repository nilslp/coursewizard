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
 * Library of interface functions and constants for module lpscheduler
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the lpscheduler specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package   mod_lpscheduler
 * @copyright 2010 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** example constant */
//define('lpscheduler_ULTIMATE_ANSWER', 42);

/**
 * If you for some reason need to use global variables instead of constants, do not forget to make them
 * global as this file can be included inside a function scope. However, using the global variables
 * at the module level is not a recommended.
 */
//global $lpscheduler_GLOBAL_VARIABLE;
//$lpscheduler_QUESTION_OF = array('Life', 'Universe', 'Everything');

/**
 * Description: Grabs the array of parameters and 
 * returns them as an array of objects so they're 
 * compatible for db submission.
 * 
 * @param associative array : an array of variables
 * @return object : values returned as an array of objects
 */
global $CFG;
require_once($CFG->dirroot.'/lib/deprecatedlib.php');

class lpscheduler_lib {


    function lpscheduler_get_instance_record($row_id=null){
            global $DB;

            if($row_id != null){
                    $results = $DB->get_records('config_plugins',array('id'=>$row_id));
            }else{
                    $results = $DB->get_records('config_plugins');
            }

            if(!empty($results)){
                    return $results;
            }
    }
    /***
        * Get the SCHEDULER records.
        * @param optional array record id to get single record, or function will reurn all records/ 
        * @return array of objects
        * @return serailized arrays are course_category_ids, course_ids, hierarchyids 
        * 
        */

    function lpscheduler_get_instance_records(array $row_id=null){
            global $DB;

            if($row_id != null){

                    foreach($row_id as $row){
                            $results = $DB->get_record('config_plugins',array('id'=>$row));
                    }

            }else{
                    $where = "plugin = 'lpscheduler'";
                    $results = $DB->get_records('config_plugins',array('plugin'=>'lpscheduler'));
            }

            //$records = lpscheduler_lib::get_unserialize_records($results);

            return $results;
    }
    
    function lpscheduler_get_configs(){
        global $CFG;
        $configs = $this->lpscheduler_get_instance_records();
        
        if(!empty($configs)){
            //$output = print_r($configs);
          
         
            foreach($configs as $con){
                $config_list = new stdClass();
                //echo $con->name;
                $config_list->id = $con->id;
                $config_list->name = $con->name;
                $config_list->value = $con->value;
                
                $config_objects[]=$config_list;
		unset($config_list);
            }
            //return $CFG;  
        }  
        return $config_objects;
     }

    /**
        * Given an object containing all the necessary data,
        * (defined by the form in mod_form.php) this function
        * will update an existing instance with new data.
        *
        * @param object $lpscheduler An object from the form in mod_form.php
        * @return boolean Success/Fail
        */ 

    function lpscheduler_update_instance($vars) {
        global $DB;
              
      if( !empty($vars)){
        
          $configs = $this->lpscheduler_get_configs();
          
        
            $lpscheduler = new stdClass();
            
            foreach($vars as $key=>$value){
                
   
                foreach($configs as $con){
                    
                    if ( $con->name == $key ){
                        $id = $con->id;
                    }
                }
         
                $lpscheduler->id = $id;
                $lpscheduler->plugin = 'lpscheduler';
                $lpscheduler->name = $key;
                $lpscheduler->value = $value;       

                # You may have to add extra stuff in here #
                    #$transaction->
                if ($key != 'submitbutton'){
                    $update = $DB->update_record('config_plugins', $lpscheduler,true);
                }
            }
            if(!$update){
                return false;
            }else{
            return true;   
            }
        }
    }

    /**
    * Given an ID of an instance of this module,
    * this function will permanently delete the instance
    * and any data that depends on it.
    *
    * @param int $id Id of the module instance
    * @return boolean Success/Failure
    */

    function lpscheduler_delete_instance($id) {
        global $DB;

        if (! $lpscheduler = $DB->get_record('lpscheduler', array('id' => $id))) {
            return false;
        }

        # Delete any dependent records here #

        $DB->delete_records('lpscheduler', array('id' => $lpscheduler->id));

        return true;
    }

    /**
        * Function to be run periodically according to the moodle cron
        * 
        *
        * @return boolean
        * @todo Finish documenting this function
        **/
    function lpscheduler_cron () {
        
        global $CFG;

        mtrace('dle scheduler cron started ...');

        $lpsceduler_lib = new lpscheduler_lib();

        $configs = $lpsceduler_lib->lpscheduler_get_configs();

        if(!empty($configs)){
            foreach ($configs as $con){
                $CFG->$con->name = $con->value;

                echo $CFG->$con->name;
            }
        }

    // moodle 2 crons
    /* $CFG->longtimenosee 
        $CFG->deleteunconfirmed 
            //$CFG->deleteIncompleteProfile
        $CFG->deleteincompleteusers
        $CFG->loglifetime 
        $CFG->disablestatsprocessing 

        // LP Custom functions
        $CFG->runExtras 
        $CFG->disablescheduledbackups 
        $CFG->removeExpiredEnrolments 
        $CFG->notifyloginfailures		
        $CFG->sendNewPasswords
        $CFG->forceContext
        $CFG->runEnrolment
        $CFG->runAuth
        $CFG->sendForumEmails
        $CFG->forumEmailFrom */

    //$CFG->runExtras
        if($CFG->runExtras){
            lpscheduler_runExtras($CFG); // run occasional clean-up tasks
        } 
        //$CFG->disablescheduledbackups
        if ($CFG->disablescheduledbackups == false) {   // Defined in config.php

            //Execute backup's cron
            //Perhaps a long time and memory could help in large sites
            @set_time_limit(0);
            @raise_memory_limit("192M");

            if (function_exists('apache_child_terminate')) {
                // if we are running from Apache, give httpd a hint that 
                // it can recycle the process after it's done. Apache's 
                // memory management is truly awful but we can help it.
                @apache_child_terminate();
            }
            if (file_exists("$CFG->dirroot/backup/backup_scheduled.php") and
                file_exists("$CFG->dirroot/backup/backuplib.php") and
                file_exists("$CFG->dirroot/backup/lib.php") and
                file_exists("$CFG->libdir/blocklib.php")) {
                include_once("$CFG->dirroot/backup/backup_scheduled.php");
                include_once("$CFG->dirroot/backup/backuplib.php");
                include_once("$CFG->dirroot/backup/lib.php");
                require_once ("$CFG->libdir/blocklib.php");
                mtrace("Running backups if required...");

                if (! schedule_backup_cron()) {
                    mtrace("ERROR: Something went wrong while performing backup tasks!!!");
                } else {
                    mtrace("Backup tasks finished.");
                }
            }
        }

        if (!empty($CFG->enablerssfeeds)) {  //Defined in admin/variables page
            include_once("$CFG->libdir/rsslib.php");
            mtrace("Running rssfeeds if required...");

            if ( ! cron_rss_feeds()) {
                mtrace("Something went wrong while generating rssfeeds!!!");
            } else {
                mtrace("Rssfeeds finished");
            }
        }

        /// Run the auth cron, if any
        //rwm 26/10/09 added manual switch for auth plugins
        if($CFG->runAuth){
            $auths = get_enabled_auth_plugins();
            mtrace("Running auth crons if required...");
            foreach ($auths as $auth) {
                $authplugin = get_auth_plugin($auth);
                if (method_exists($authplugin, 'cron')) {
                    mtrace("Running cron for auth/$auth...");
                    $authplugin->cron();
                    if (!empty($authplugin->log)) {
                        mtrace($authplugin->log);
                    }
                }
                unset($authplugin);
            }
        }

        if (!empty($CFG->enablestats) && $CFG->disablestatsprocessing == false) {
            require_once($CFG->dirroot.'/lib/statslib.php');
            // check we're not before our runtime
            $timetocheck = stats_get_base_daily() + $CFG->statsruntimestarthour*60*60 + $CFG->statsruntimestartminute*60;

            if (time() > $timetocheck) {
                // process max 31 days per cron execution
                if (stats_cron_daily(31)) {
                    if (stats_cron_weekly()) {
                        if (stats_cron_monthly()) {
                            stats_clean_old();
                        }
                    }
                }
                @set_time_limit(0);
            } else {
                mtrace('Next stats run after:'. userdate($timetocheck));
            }
        }


        /// Run the enrolment cron, if any
        // rwm 26/10/09 added manual switch for enrolment plugins
    /*  if($CFG->runEnrolment){
            if (!($plugins = explode(',', $CFG->enrol_plugins_enabled))) {
                $plugins = array($CFG->enrol);
            }
            require_once($CFG->dirroot .'/enrol/enrol.class.php'); // not there in moodle2
            foreach ($plugins as $p) {
                $enrol = enrolment_factory::factory($p);
                if (method_exists($enrol, 'cron')) {
                    $enrol->cron();
                }
                if (!empty($enrol->log)) {
                    mtrace($enrol->log);
                }
                unset($enrol);
            }
        }
    */
    }


    function lpscheduler_runExtras($CFG){
        global $DB;
            mtrace("Running clean-up tasks...");

            /// Unenrol users who haven't logged in for $CFG->longtimenosee

            if ($CFG->longtimenosee == true) { // value in days
                $cuttime = $timenow - ($CFG->longtimenosee * 3600 * 24);
                $rs = $DB->get_recordset_sql ("SELECT id, userid, courseid
                                            FROM {$CFG->prefix}user_lastaccess
                                        WHERE courseid != ".SITEID."
                                            AND timeaccess < $cuttime ");
                while ($assign = rs_fetch_next_record($rs)) {
                    if ($context = get_context_instance(CONTEXT_COURSE, $assign->courseid)) {
                        if (role_unassign(0, $assign->userid, 0, $context->id)) {
                            mtrace("Deleted assignment for user $assign->userid from course $assign->courseid");
                        }
                    }
                }
                rs_close($rs);
            
            /// Execute the same query again, looking for remaining records and deleting them
            /// if the user hasn't moodle/course:view in the CONTEXT_COURSE context (orphan records)
                $rs = $DB->get_recordset_sql ("SELECT id, userid, courseid
                                            FROM {$CFG->prefix}user_lastaccess
                                        WHERE courseid != ".SITEID."
                                            AND timeaccess < $cuttime ");
                while ($assign = rs_fetch_next_record($rs)) {
                    if ($context = get_context_instance(CONTEXT_COURSE, $assign->courseid)) {
                        if (!has_capability('moodle/course:view', $context, $assign->userid)) {
                            $DB->delete_records('user_lastaccess', array ('userid'=>$assign->userid, 'courseid' =>$assign->courseid ));
                            mtrace("Deleted orphan user_lastaccess for user $assign->userid from course $assign->courseid");
                        }
                    }
                }
                rs_close($rs);
            }
            flush();


            /// Delete users who haven't confirmed within required period

            if ($CFG->deleteunconfirmed == true) {
                $cuttime = $timenow - ($CFG->deleteunconfirmed * 3600);
                $rs = $DB->get_recordset_sql ("SELECT id, firstname, lastname
                                            FROM {$CFG->prefix}user
                                        WHERE confirmed = 0
                                            AND firstaccess > 0
                                            AND firstaccess < $cuttime");
                while ($user = rs_fetch_next_record($rs)) {
                    if ($DB->delete_records('user', array ('id'=>$user->id))) {
                        mtrace("Deleted unconfirmed user for ".fullname($user, true)." ($user->id)");
                    }
                }
                rs_close($rs);
            }
            flush();


            /// Delete users who haven't completed profile within required period

            if ($CFG->deleteincompleteusers == true) { //$CFG->deleteIncompleteProfile
                $cuttime = $timenow - ($CFG->deleteincompleteusers * 3600);
                $rs = $DB->get_recordset_sql ("SELECT id, username
                                            FROM {$CFG->prefix}user
                                        WHERE confirmed = 1
                                            AND lastaccess > 0
                                            AND lastaccess < $cuttime
                                            AND deleted = 0
                                            AND (lastname = '' OR firstname = '' OR email = '')");
                while ($user = rs_fetch_next_record($rs)) {
                    if ($DB->delete_records('user', array('id'=>$user->id))) {
                        mtrace("Deleted not fully setup user $user->username ($user->id)");
                    }
                }
                rs_close($rs);
            }
            flush();


            /// Delete old logs to save space (this might need a timer to slow it down...)

            if ($CFG->loglifetime == true) {  // value in days
                $loglifetime = $timenow - ($CFG->loglifetime * 3600 * 24);
                if ($DB->delete_records_select("log", "time < '$loglifetime'")) {
                    mtrace("Deleted old log records");
                }
            }
            flush();


            /// Delete old cached texts

            if ($CFG->cachetext == true) {   // Defined in config.php
                $cachelifetime = time() - $CFG->cachetext - 60;  // Add an extra minute to allow for really heavy sites
                if ($DB->delete_records_select('cache_text', "timemodified < '$cachelifetime'")) {
                    mtrace("Deleted old cache_text records");
                }
            }
            flush();

            if ($CFG->notifyloginfailures == true) {
                notify_login_failures();
                mtrace('Notified login failured');
            }
            flush();

            sync_metacourses();
            mtrace('Synchronised metacourses');

            //
            // generate new password emails for users 
            //
                    //rwm 26/10/09 added manual switch for new passwords
            if($CFG->sendNewPasswords == true){
                            mtrace('checking for create_password');
                    if ($DB->count_records('user_preferences', array ( 'name'=> 'create_password', 'value'=> '1'))) {
                        mtrace('creating passwords for new users');
                        $newusers = $DB->get_records_sql("SELECT  u.id as id, u.email, u.firstname, 
                                                            u.lastname, u.username,
                                                            p.id as prefid 
                                                    FROM {$CFG->prefix}user u 
                                                        JOIN {$CFG->prefix}user_preferences p ON u.id=p.userid
                                                    WHERE p.name='create_password' AND p.value=1 AND u.email !='' ");

                        foreach ($newusers as $newuserid => $newuser) {
                            $newuser->emailstop = 0; // send email regardless
                            // email user                               
                            if (setnew_password_and_mail($newuser)) {
                                // remove user pref
                                $DB->delete_records('user_preferences', array ('id'=>$newuser->prefid));
                            } else {
                                trigger_error("Could not create and mail new user password!");
                            }
                        }
                    }
                    }

            if(!empty($CFG->usetags)){
                require_once($CFG->dirroot.'/tag/lib.php');
                tag_cron();
                mtrace ('Executed tag cron');
            }

            // Accesslib stuff
            cleanup_contexts();
            mtrace ('Cleaned up contexts');
            gc_cache_flags();
            mtrace ('Cleaned cache flags');
            // If you suspect that the context paths are somehow corrupt
            // replace the line below with: build_context_path(true); 
            build_context_path($CFG->forceContext == true);
            mtrace ('Built context paths');

            mtrace("Finished clean-up tasks...");



            return true;
    }


    /**
        * Execute post-uninstall custom actions for the module
        * This function was added in 1.9
        *
        * @return boolean true if success, false on error
        */
    function lpscheduler_uninstall() {
        return true;
    }
} // End Class

require_once ($CFG->dirroot.'/lib/formslib.php');
//require_once ( $CFG->dirroot.'/calendar/lib.php' );
//require_once ( $CFG->dirroot.'blocks/lp_reportbuilder/scheduled.php');

class local_lpscheduler_index_form extends moodleform {
        
    function definition(){
       global $REPORT_BUILDER_SCHEDULE_OPTIONS, $CFG;
       
    
        //$configs = $lpscheduler_lib->lpscheduler_get_instance_records();
        $configs = get_config('lpscheduler');
        $mform = &$this->_form;
        $mform->addElement('header', 'scheduler-heading', get_string('lpscheduler_menuitem','local_dlelegacytools'));
        
        //print_r($configs);
        //continue;
        $attr = new stdClass();
        
        if(!empty($configs)){
            
        echo html_writer::start_tag('div',array('class'=>'form-setting'));         
         
        // for each config item apply its current setting and build its label and description for user.
            foreach($configs as $key => $item){
                
                if ('blockwelcomeemail' == $key) {
                    // DPMH - plugin is deprecated. See local/welcome_email instead.
                    $newurl = $CFG->wwwroot.'/local/welcome_email/index.php';
                    $mform->addElement('html',"<div><em>Welcome Emails are now managed <a href=\"{$newurl}\">here</a>.</em></div>");
                    continue;
                }

                // set the value and name fields of the specific configs aswell.

                if($key == 'notifyloginfailures'){                                     
                    $attr->options = array('nonotifications'=>'no notifications',
                                            'mainadminonly'=>'main admin',
                                            'alladmins' => 'all admins');                                                                           
                }else{

                    $attr->options = array('false'=>'off','true'=>'on');
                }

                $mform->addElement('select',$key,get_string($key,'local_dlelegacytools'),$attr->options);
                $mform->setDefault($key, $item);

            }
          echo   html_writer::end_tag('div');
            $this->add_action_buttons();
        }
        

     }
}
