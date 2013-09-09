<?php
/**
 *  CLI Script to fix deleted users ...
 */

define('CLI_SCRIPT',true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG, $DB;
require_once($CFG->libdir.'/grouplib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/message/lib.php');
require_once($CFG->dirroot.'/tag/lib.php');

/**
 * Restore helper
 * 
 * @global moodle_database $DB
 * @param type $table
 * @param type $userid
 * @param type $source
 * @param type $target
 * @param type $userfield 
 */
function restore_user_records( $table, $userid, $source, $target, $userfield='userid' ) {
    global $DB,$CFG;
    
    $records = $DB->get_records_sql("SELECT * FROM ".$source.".".$CFG->prefix.$table." WHERE $userfield=?", array($userid));
    foreach ($records as $rec) {
        $props = get_object_vars($rec);
        $columns = array();
        $values = array();
        foreach ($props as $k=>$v) {
            $columns []= $k;
            $values []= empty($v) ? "'".intval($v)."'" : "'".str_replace('?', '', $v)."'";  // for comma separated values, remove question marks, handle nulls
        }
        $columns = implode(',',$columns);
        $values = implode(',',$values);
        $sql = "REPLACE INTO ".$target.".".$CFG->prefix.$table." ( $columns ) VALUES ( $values )";
        $DB->execute($sql);
    }
}

/**
 * 
 * @global moodle_database $DB
 * @param object $user - the user from the source db
 * @param string $source - the source from which to retrieve records
 * @param string $target - the target (current) into which to insert records
 * @return type 
 */
function undelete($user,$source,$target) {
    global $DB;
    echo "\nRestoring user $user->id, $user->firstname $user->lastname ... ";
    
    $time = time();
    $transaction = $DB->start_delegated_transaction(); // wrap up in a transaction
    
    try {
        // auth email and auth manual don't implement user_delete ...

        // revert user record
        // get a safe delete email
        $delemail = addslashes("{$user->email}.{$time}");
        while ($DB->record_exists('user', array('email' => $delemail))) { 
            $delemail++;
        }
        
        // bit whacky - some username.DELETED records with different user ids already exist (!?)
        $propname = addslashes("{$user->username}.DELETED");     
        $delname = $propname;
        $count = 0;
        while ($DB->record_exists('user', array('username' => $delname))) { 
            $count++;
            $delname = $propname.$count;
        }
        
        $updateuser = new stdClass();
        $updateuser->id           = $user->id;
        $updateuser->username     = $delname;  
        $updateuser->email        = $delemail;
        $updateuser->idnumber     = $user->idnumber;                  
        $updateuser->timemodified = $time;

        if (!$DB->update_record('user', $updateuser)) {
            throw new Exception('Could not update user for userid: '.$user->id);
        }    

        // restore contexts
        $context = $DB->get_record_sql("SELECT * FROM {$source}.{context} WHERE contextlevel=? AND instanceid=?" ,array(CONTEXT_USER,$user->id));
        if ($context && !$DB->execute('REPLACE INTO {context} VALUES (?,?,?,?,?)',
                array(
                    $context->id,
                    $context->contextlevel,
                    $context->instanceid,
                    $context->path,
                    $context->depth
                    )
                )) {
            throw new Exception('Could not insert context for userid: '.$user->id);
        }
        
                
        // reauthorise the user for all services
        restore_user_records('external_services_users', $user->id, $source, $target);

        // restore all user tokens
        restore_user_records('external_tokens', $user->id, $source, $target);

        // restore last access
        restore_user_records('user_lastaccess', $user->id, $source, $target);

        // restore user extra profile info
        restore_user_records('user_info_data', $user->id, $source, $target);

        // restore user preference
        restore_user_records('user_preferences', $user->id, $source, $target);

        // restore enrolments
        restore_user_records('user_enrolments', $user->id, $source, $target);

        // restore all groups
        restore_user_records('groups_members', $user->id, $source, $target);

        // restore all cohorts
        restore_user_records('cohort_members', $user->id, $source, $target);
        
        // restore role assignments
        restore_user_records('role_assignments', $user->id, $source, $target);
        
        // reenrol in all courses
        restore_user_records('user_enrolments', $user->id, $source, $target);
                
        // restore all grades 
        restore_user_records('grade_grades', $user->id, $source, $target);
        
        $transaction->allow_commit();      
        
        echo "complete!";
        
    } catch (Exception $e) {
        $transaction->rollback($e);
    }
}

$source = 'moodle_restore_users'; // @CHANGE
$target = $CFG->dbname;
$mysqluser = "root";        
$mysqlhost = "192.168.100.50";   // @CHANGE
$mysqlpass = "i6cWPJQV";    // @CHANGE

// backup db
system("mysqldump -u$mysqluser -p$mysqlpass -h$mysqlhost $target > $target.bak.sql");

// what backups are we going to need?
$backupsql = "SELECT GROUP_CONCAT(id) AS userids,REPLACE(SUBSTRING(FROM_UNIXTIME(timemodified-86400),1,10),'-','') AS dday FROM $target.{user} WHERE deleted = ? AND LOCATE('.DELETED',username) = ? AND FROM_UNIXTIME(timemodified) > ? GROUP BY dday ORDER BY dday ASC";
$backupdays = $DB->get_records_sql($backupsql,array(1,0,'2012-04-01 00:00:00'));
foreach ($backupdays as $backupday) {
    echo "\n\nDDAY: $backupday->dday, USERS: $backupday->userids\n";
    
    // grab the required backup file
    // $backupfile = "{$target}.sql.gz"; 
    $ext = ".gz";
    $unzip = "gzip";
    if (intval($backupday->dday) > 20120530) {
        $ext = ".xz";
        $unzip = "xz";
    }
    $backupfile = "{$target}.sql".$ext; // @CHANGE
    $backuppath = "/mnt/nfs/mysql-backups/{$backupday->dday}/{$backupfile}";
    
    // copy the file and run the script against the backup database
    system("cp $backuppath $backupfile"); // @CHANGE
    system("$unzip -cd $backupfile | pv | mysql -u$mysqluser -p$mysqlpass -h$mysqlhost $source");
    
    // @NB - we iterate the user records from the source database NOT the current database ($target)
    $users = $DB->get_records_sql("SELECT * FROM $source.{user} WHERE id IN ( $backupday->userids )");
    foreach ($users as $user) {
        undelete($user,$source,$target);
    }
    
}

echo "\n\nDone.";

exit ;




