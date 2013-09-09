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
 * @subpackage undelete users
 * @copyright  2012 Learning Pool
 * @author     Pete Smith
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/user/filters/lib.php');


/**
 * Custom user undelete function. Reverses a soft delete of the user.
 *
 * @global type $CFG
 * @global type $DB
 * @param type $user
 * @return boolean
 */
function dlt_undelete_user($user) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/grouplib.php');
    require_once($CFG->libdir . '/gradelib.php');
    
    $synchierarchy = (int)get_config('local_moderngovernor', 'synchierarchy');

    $uname = str_replace(".DELETED", "", $user->username);
    $email =  substr($user->email, 0, strrpos($user->email, '.') );
    //check that the proposed new username doesn't already exist
    $already_exists  =     $DB->record_exists_select('user', ' username = ? OR email = ? ',  array( $uname, $email ));
     
    if($already_exists && !$synchierarchy){
        return "Cannot undelete. Username or email already in use";
    }

    $hashedpassword = hash_internal_user_password('welcome');
    // undelete internal user record with new email and username
    $updateuser = new stdClass();
    $updateuser->id = $user->id;
    $updateuser->deleted = 0;
    $updateuser->idnumber = $user->idnumber;
    $updateuser->timemodified = time();
    if (!$already_exists) {
        $updateuser->username = $uname;
        $updateuser->email = $email;
        $updateuser->password = $hashedpassword; 
    }   
    
    if ($DB->update_record('user', $updateuser)) {
        // if moderngovenor synch user is switched on, also undelete user in global table.
        if ($synchierarchy) {
            $mgpath = $CFG->dirroot.'/local/moderngovernor/lib.php';
            if (file_exists($mgpath)) {
                require_once($mgpath); 
                return moderngovernor_undelete_user($user->id);
            }
        }
        return true;
    }
    //force password change
    set_user_preference('auth_forcepasswordchange', 1, $user);
    
    return "Undelete failed";
}




/**
 * straight re-work of get_users_listing() to change deleted = 1 instead of 0
 *
 * @param string $sort An SQL field to sort by
 * @param string $dir The sort direction ASC|DESC
 * @param int $page The page or records to return
 * @param int $recordsperpage The number of records to return per page
 * @param string $search A simple string to search for
 * @param string $firstinitial Users whose first name starts with $firstinitial
 * @param string $lastinitial Users whose last name starts with $lastinitial
 * @param string $extraselect An additional SQL select statement to append to the query
 * @param array $extraparams Additional parameters to use for the above $extraselect
 * @param object $extracontext If specified, will include user 'extra fields'
 *   as appropriate for current user and given context
 * @return array Array of {@link $USER} records
 */
function get_deleted_users_listing($sort='lastaccess', $dir='ASC', $page=0, $recordsperpage=0,
                           $search='', $firstinitial='', $lastinitial='', $extraselect='',
                           array $extraparams=null, $extracontext = null) {
    global $DB;

    if ((int)get_config('local_moderngovernor','synchierarchy')) {
        // we need to get deleted users from moodleadmin for this instance!
        $cfg = get_moderngovernor_config();   
        
        $fullname  = $DB->sql_fullname();
        $select = " gu.username LIKE '%DELETED'";
        $params = array();
        
        if (!empty($search)) {
            $search = trim($search);
            $select .= " AND (". $DB->sql_like("$fullname", ':search1', false, false).
                    " OR ". $DB->sql_like('u.email', ':search2', false, false).
                    " OR u.username = :search3)";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
            $params['search3'] = "$search";
        }
        
        if ($extraselect) {
            $select .= " AND $extraselect";
            $params = $params + (array)$extraparams;
        }
        
        if ($sort) {
            $sort = " ORDER BY $sort $dir";
        }
        
        $lealist = get_moderngovernor_lea_insql();
                
        return $DB->get_records_sql("SELECT u.id, u.username, u.email, u.firstname, u.lastname, u.city, u.country,
                                        u.lastaccess, u.confirmed, u.mnethostid, u.suspended 
                                   FROM {user} u
                                   INNER JOIN `{$cfg->db}`.`{$cfg->table}` gu
                                        ON gu.email=u.email AND gu.lea_id IN ($lealist)
                                  WHERE $select
                                  $sort", $params, $page, $recordsperpage);
    } else {
        $fullname  = $DB->sql_fullname();

        $select = "deleted = 1 AND username LIKE '%DELETED'";
        $params = array();

        if (!empty($search)) {
            $search = trim($search);
            $select .= " AND (". $DB->sql_like($fullname, ':search1', false, false).
                    " OR ". $DB->sql_like('email', ':search2', false, false).
                    " OR username = :search3)";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
            $params['search3'] = "$search";
        }

        if ($firstinitial) {
            $select .= " AND ". $DB->sql_like('firstname', ':fni', false, false);
            $params['fni'] = "$firstinitial%";
        }
        if ($lastinitial) {
            $select .= " AND ". $DB->sql_like('lastname', ':lni', false, false);
            $params['lni'] = "$lastinitial%";
        }

        if ($extraselect) {
            $select .= " AND $extraselect";
            $params = $params + (array)$extraparams;
        }

        if ($sort) {
            $sort = " ORDER BY $sort $dir";
        }

        // If a context is specified, get extra user fields that the current user
        // is supposed to see.
        $extrafields = '';
        if ($extracontext) {
            $extrafields = get_extra_user_fields_sql($extracontext, '', '',
                    array('id', 'username', 'email', 'firstname', 'lastname', 'city', 'country',
                    'lastaccess', 'confirmed', 'mnethostid'));
        }

        // warning: will return UNCONFIRMED USERS
        return $DB->get_records_sql("SELECT id, username, email, firstname, lastname, city, country,
                                            lastaccess, confirmed, mnethostid, suspended $extrafields
                                    FROM {user}                                   
                                    WHERE $select
                                    $sort", $params, $page, $recordsperpage);
    }

}



/**
 * re-work of get_users to show deleted users
 *
 * @global object
 * @uses DEBUG_DEVELOPER
 * @uses SQL_PARAMS_NAMED
 * @param bool $get If false then only a count of the records is returned
 * @param string $search A simple string to search for
 * @param bool $confirmed A switch to allow/disallow unconfirmed users
 * @param array $exceptions A list of IDs to ignore, eg 2,4,5,8,9,10
 * @param string $sort A SQL snippet for the sorting criteria to use
 * @param string $firstinitial Users whose first name starts with $firstinitial
 * @param string $lastinitial Users whose last name starts with $lastinitial
 * @param string $page The page or records to return
 * @param string $recordsperpage The number of records to return per page
 * @param string $fields A comma separated list of fields to be returned from the chosen table.
 * @return array|int|bool  {@link $USER} records unless get is false in which case the integer count of the records found is returned.
  *                        False is returned if an error is encountered.
 */
function get_deleted_users($get=true, $search='', $confirmed=false, array $exceptions=null, $sort='firstname ASC',
                   $firstinitial='', $lastinitial='', $page='', $recordsperpage='', $fields='*', $extraselect='', array $extraparams=null) {
    global $DB, $CFG;

    if ($get && !$recordsperpage) {
        debugging('Call to get_users with $get = true no $recordsperpage limit. ' .
                'On large installations, this will probably cause an out of memory error. ' .
                'Please think again and change your code so that it does not try to ' .
                'load so much data into memory.', DEBUG_DEVELOPER);
    }
    
    if ((int)get_config('local_moderngovernor','synchierarchy')) {
        // we need to get deleted users from moodleadmin for this instance!
        $cfg = get_moderngovernor_config();   
        
        $fullname  = $DB->sql_fullname();
        $select = " gu.id <> :guestid AND gu.username LIKE '%DELETED'";
        $params = array('guestid'=>$CFG->siteguest);
        
        if (!empty($search)) {
            $search = trim($search);
            $select .= " AND (". $DB->sql_like($fullname, ':search1', false, false).
                    " OR ". $DB->sql_like('u.email', ':search2', false, false).
                    " OR u.username = :search3)";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
            $params['search3'] = "$search";
        }

        if ($confirmed) {
            $select .= " AND gu.confirmed = 1";
        }
        
        if ($extraselect) {
            $select .= " AND $extraselect";
            $params = $params + (array)$extraparams;
        }

        if (!empty($sort)) {
            $sort = "ORDER BY u.$sort";
        }
        
        $lealist = get_moderngovernor_lea_insql();
        
        if ($get) {
            return $DB->get_records_sql("SELECT u.id, u.username, u.email, u.firstname, u.lastname, u.city, u.country,
                                        u.lastaccess, u.confirmed, u.mnethostid, u.suspended 
                                   FROM {user} u
                                   INNER JOIN `{$cfg->db}`.`{$cfg->table}` gu
                                        ON gu.email=u.email AND gu.lea_id IN ($lealist)
                                  WHERE $select
                                  $sort", $params, $page, $recordsperpage);
        } else {
            return $DB->count_records_sql("SELECT count('x')
                                   FROM {user} u
                                   INNER JOIN `{$cfg->db}`.`{$cfg->table}` gu
                                        ON gu.email=u.email AND gu.lea_id IN ($lealist)
                                  WHERE $select
                                  $sort", $params);
        }        
    } else {

        $fullname  = $DB->sql_fullname();

        $select = " id <> :guestid AND deleted = 1 AND username LIKE '%DELETED' ";
        $params = array('guestid'=>$CFG->siteguest);

        if (!empty($search)){
            $search = trim($search);
            $select .= " AND (".$DB->sql_like($fullname, ':search1', false)." OR ".$DB->sql_like('email', ':search2', false)." OR username = :search3)";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
            $params['search3'] = "$search";
        }

        if ($confirmed) {
            $select .= " AND confirmed = 1";
        }

        if ($exceptions) {
            list($exceptions, $eparams) = $DB->get_in_or_equal($exceptions, SQL_PARAMS_NAMED, 'ex', false);
            $params = $params + $eparams;
            $select .= " AND id $exceptions";
        }

        if ($firstinitial) {
            $select .= " AND ".$DB->sql_like('firstname', ':fni', false, false);
            $params['fni'] = "$firstinitial%";
        }
        if ($lastinitial) {
            $select .= " AND ".$DB->sql_like('lastname', ':lni', false, false);
            $params['lni'] = "$lastinitial%";
        }

        if ($extraselect) {
            $select .= " AND $extraselect";
            $params = $params + (array)$extraparams;
        }

        if ($get) {
            return $DB->get_records_select('user', $select, $params, $sort, $fields, $page, $recordsperpage);
        } else {
            return $DB->count_records_select('user', $select, $params);
        }
    }
}
