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
 * Lib functions (cron) 
 *
 * @package    local
 * @subpackage lp_courseaccessmanager
 * @copyright  2012 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')){
    define('MOODLE_INTERNAL', true);
}
#require_once(dirname(__FILE__) . '/../../lib/dml/moodle_database.php');
#require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/../../lib/moodlelib.php');
require_once(dirname(__FILE__) . '/../../lib/dml/mysqli_native_moodle_database.php');
require_once(dirname(__FILE__) . '/../../local/learningpool/utils.php');

/**
 * Standard cron function
 */
function local_lp_courseaccessmanager_cron() {
    local_lp_courseaccessmanager_trace('local_lp_courseaccessmanager_cron() started at '. date('H:i:s'));
    try {
        local_lp_courseaccessmanager_process();
    } catch (Exception $e) {
        local_lp_courseaccessmanager_trace('local_lp_courseaccessmanager_cron() failed with an exception:');
        local_lp_courseaccessmanager_trace($e->getMessage());
    }
    local_lp_courseaccessmanager_trace('local_lp_courseaccessmanager_cron() finished at ' . date('H:i:s'));
}

/**
 * This function does the cron process within the time range according to settings.
 */
function local_lp_courseaccessmanager_process() {    
    // migrate course restrictions if we haven't already done so
    if (!get_config('local_lp_courseaccessmanager','migrated')){
        local_lp_cam_migrate_access();
    }
}

/**
 * helper function to print our messages consistently
 */
function local_lp_courseaccessmanager_trace($msg) {
    mtrace('lp_courseaccessmanager: ' . $msg);    
}

/**
 * Maintenance function - migrates old style course access restrictions and
 * adds them in the new access manager style
 * 
 * @global type $CFG
 * @global type $DB 
 */
function local_lp_cam_migrate_access() {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/local/learningpool/utils.php');
    
    $count = 0;
    $curtime = time();
    $map = local_lp_build_hierarchy_map();
    $records = $DB->get_records('lp_course_restriction');    
    
    // loop old restrictions and perform translation
    foreach ($records as $rec){
        $context = $DB->get_field('context','id',array('instanceid'=>$rec->lngcategoryid,'contextlevel'=>CONTEXT_COURSECAT));
        if (!$context){
            continue;
        }
        ++$count;
        // create the new rule
        $rule = new stdClass();
        $rule->name = 'LegacyAccess'.$count;
        $rule->active = 1;
        $rule->allowdeeplinking = 0;
        $rule->timemodified = $curtime;
        $ruleid = $DB->insert_record('lp_access_rule',$rule,true);
        
        if (!$ruleid) {
            continue;
        }
        
        $ctx = new stdClass();
        $ctx->rule = $ruleid;
        $ctx->context = $context;
        if (!$DB->insert_record('lp_access_context',$ctx)){
            continue;
        }
        
        // set up an assignment object for repeated insertion
        $assignment = new stdClass;
        $assignment->assignmentcontext = 20;
        $assignment->timemodified = $curtime;
        $assignment->rule = $ruleid;
        
        // invert the access rules
        $restricted = array();
        
        // old form: category,directorate,department,subdepartment
        $dirs = explode(',',$rec->memdirectorate);
        foreach ($dirs as $dir){
            if (isset($map['directorate'][$dir])){
                $restricted []=  $map['directorate'][$dir];
            }
        }
        
        $deps = explode(',',$rec->memdepartment);
        foreach ($deps as $dep){
            if (isset($map['department'][$dep])){
                $restricted [] = $map['department'][$dep];
            }
        }
        
        $subs = explode(',',$rec->memsubdepartment);
        foreach ($subs as $sub){
            if (isset($map['subdepartment'][$sub])){
                $restricted [] = $map['subdepartment'][$sub];
            }
        }
        
        $all = $DB->get_records('lp_hierarchy',null,'id ASC', 'id');
        foreach ($all as $hier){
            if (!in_array($hier->id, $restricted)){
                $assignment->assigned = $hier->id;
                $DB->insert_record('lp_access_assignment',$assignment,false,true);
            }
        }
    }
    
    // set config so we don't have to do this again
    set_config('migrated', 1,'local_lp_courseaccessmanager');
}

// end cron functions

function local_lp_cam_filter_keywords($str){
    return preg_replace('/[\W]+|from|where|join|in|\(/','',strtolower($str));
}

function local_lp_cam_filter_sql($sql){    
    global $CFG;
    // check for course query
    if (strpos($sql,'{course}') !== false){
       /* if (has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))){
            return $sql;
        } */
        $cpattern = "\{course\}|".$CFG->prefix."course";
        $pattern = '/('.$cpattern.')(\W+AS)?(^WHERE|JOIN|LEFT|RIGHT|OUTER|INNER|NATURAL|CROSS|STRAIGHT_JOIN)?(\W+[a-z]+)?/';
        $matches = array();
        preg_match($pattern,$sql,$matches);
        $sql = preg_replace($pattern,local_lp_cam_get_course_select($matches),$sql);
    }
    
    // check for category query
    if (strpos($sql,'{course_categories}') !== false){
        if (CLI_SCRIPT || has_capability('local/lp_courseaccessmanager:manageaccess', get_context_instance(CONTEXT_SYSTEM))){
            return $sql;
        } 
        $cpattern = "\{course_categories\}|".$CFG->prefix."course_categories";
        $pattern = '/('.$cpattern.')(\W+AS)?(^WHERE|JOIN|LEFT|RIGHT|OUTER|INNER|NATURAL|CROSS|STRAIGHT_JOIN)?(\W+[a-z]+)?/';
        $matches = array();
        preg_match($pattern,$sql,$matches);
        $sql = preg_replace($pattern,local_lp_cam_get_category_select($matches),$sql);        
    }
    
    return $sql;
}

function local_lp_cam_get_course_select($matches){
    global $USER, $DB;     
    static $sql;
    $alias = 'filter_c';
	$userid = -1;
	$userhier = -1;

    if (isset($matches[4])){
        $matches[4] = trim($matches[4]);
        if (!empty($matches[4])){
            $alias = $matches[4];
        }
    }
        
    if (CLI_SCRIPT || has_capability('local/lp_courseaccessmanager:manageaccess', get_context_instance(CONTEXT_SYSTEM))){
        return "{course} $alias";
    } 

    if (isset($USER->profile) && isset($USER->profile['hierarchyid']) && !empty($USER->profile['hierarchyid'])){
        $userhier = $USER->profile['hierarchyid'];
	}

    if (isset($USER->id) && !empty($USER->id)){
        $userid = $USER->id;
    }
    
    if (isset($sql)){
        return "$sql $alias";
    }
    
    $inpage = (preg_match('/(^\/course\/view\.php\?|^\/mod\/|^\/pluginfile\.php\/)/',$_SERVER['REQUEST_URI']) > 0);
    $defaultview = (get_config('local/lp_courseaccessmanager', 'defaultview') == 'show' ? 1 : 0);

    $acsql = 'SELECT act.id,ct.contextlevel,ct.instanceid,acr.id AS rule,acr.allowdeeplinking FROM {context} ct 
            JOIN {lp_access_context} act 
                ON act.context=ct.id 
            JOIN {lp_access_rule} acr 
                ON acr.id=act.rule AND acr.active=1
            GROUP BY act.id';
    // get any accessrules in the course context
    $accesslist = $DB->get_records_sql($acsql);

    // get access grants for the user
    $useraccess = $DB->get_records_sql(
            'SELECT DISTINCT(rule) FROM {lp_access_assignment}
                WHERE 
                    (assignmentcontext=10 AND assigned='.$userid.') 
                OR  
                    (assignmentcontext=20 AND assigned IN ('.$userhier.')) 
                GROUP BY rule');

    if (empty($useraccess)){
        $useraccess=array();
    }

    // we need to know which courses the user definitely does NOT have access to ($excluded*)
    // so we can rule them out when we look at the default view
    $excludedcourses = array('-1'=>1);
    $excludedcategories = array('-2'=>1);
    $includedcourses = array('1'=>1);
    $includedcategories = array('0'=>1);
    foreach ($accesslist as $ac){
        $iscourse = ($ac->contextlevel == CONTEXT_COURSE);
        if (($iscourse && isset($includedcourses[$ac->instanceid])) ||
                (!$iscourse && isset($includedcategories[$ac->instanceid]))){
            continue;
        }        
        
        if (isset($useraccess[$ac->rule]) || ($ac->allowdeeplinking && $inpage)){
            if ($iscourse){
                $includedcourses[$ac->instanceid]=1;
            } else {
                $includedcategories[$ac->instanceid]=1;
            }
            continue;
        }
        
        // switch to allow deeplinked courses...
        if ($ac->allowdeeplinking && $inpage){
            continue;
        }
        
        if ($iscourse){
            $excludedcourses[$ac->instanceid]=1;            
        } else {
            $excludedcategories[$ac->instanceid]=1;
        }
    }
    
    
    $includedcourses = array_keys($includedcourses);
    $includedcategories = array_keys($includedcategories);
    
    $excludedcourses = array_diff(array_keys($excludedcourses),$includedcourses);
    $excludedcategories = array_keys($excludedcategories);
    
    // workaround for issue when show unmanaged courses is active and no rules exist for categories/children
    // that sit somewhere under restricted categories
    $excludedchildren = array();
    foreach ($excludedcategories as $exc){
        $excludedchildren = array_merge($excludedchildren,local_lp_get_related_categories($exc));
    }
    $excludedcategories = array_unique(array_merge($excludedcategories,$excludedchildren));
    $excludedcategories = array_diff($excludedcategories,$includedcategories);

    $includeclause = '(sub_c.id IN ('.implode(',',$includedcourses).') AND sub_c.category IN ('.implode(',',$includedcategories).'))';
    $excludeclause = '((sub_c.id NOT IN ('.implode(',',$excludedcourses).') AND (sub_c.category NOT IN ('.implode(',',$excludedcategories).')) AND '.$defaultview.'=1))';
    $sql = "(SELECT sub_c.* FROM {course} sub_c WHERE $includeclause OR $excludeclause)";
    return "$sql $alias";
}

function local_lp_cam_get_category_select($matches){
    global $USER, $DB;       
    static $sql;
    $alias = 'filter_cat';
	$userid = -1;
	$userhier = -1;
   
    if (isset($matches[4])){
        $matches[4] = trim($matches[4]);
        if (!empty($matches[4])){
            $alias = $matches[4];
        }
    }   
 
    if (CLI_SCRIPT || has_capability('local/lp_courseaccessmanager:manageaccess', get_context_instance(CONTEXT_SYSTEM))){
        return "{course_category} $alias";
    } 

    if (isset($USER->profile) && isset($USER->profile['hierarchyid']) && !empty($USER->profile['hierarchyid'])){
        $userhier = $USER->profile['hierarchyid'];
	}

    if (isset($USER->id) && !empty($USER->id)){
        $userid = $USER->id;
    }
    
    if (isset($sql)){
        return "$sql $alias";
    }
    
    $inpage = (preg_match('/(^\/course\/view\.php\?|^\/mod\/|^\/pluginfile\.php\/)/',$_SERVER['REQUEST_URI']) > 0);
    $defaultview = (get_config('local/lp_courseaccessmanager', 'defaultview') == 'show' ? 1 : 0);

    // get any accessrules in the category context
    $accesslist = $DB->get_records_sql(
       'SELECT act.id,ct.instanceid,acr.id AS rule,acr.allowdeeplinking FROM {context} ct 
            JOIN {lp_access_context} act 
                ON act.context=ct.id 
            JOIN {lp_access_rule} acr 
                ON acr.id=act.rule AND acr.active=1 
            WHERE ct.contextlevel='.CONTEXT_COURSECAT. 
            ' GROUP BY act.id');

    // get access grants for the user
    $useraccess = $DB->get_records_sql(
            'SELECT DISTINCT(rule) FROM {lp_access_assignment}
                WHERE 
                    (assignmentcontext=10 AND assigned='.$userid.') 
                OR  
                    (assignmentcontext=20 AND assigned IN ('.$userhier.')) 
                GROUP BY rule');

    if (empty($useraccess)){
        $useraccess=array();
    }

    // we need to know which categories the user definitely does NOT have access to ($excluded*)
    // so we can rule them out when we look at the default view
    $excludedcategories = array('-1'=>1);
    $includedcategories = array('0'=>1);
    foreach ($accesslist as $ac){
        if (isset($includedcategories[$ac->instanceid])){
            continue;
        }
        if (isset($useraccess[$ac->rule]) || ($ac->allowdeeplinking && $inpage)){
            $includedcategories[$ac->instanceid]=1;            
            continue;
        }
        if ($ac->allowdeeplinking && $inpage){
            continue;
        }
        $excludedcategories[$ac->instanceid]= 1;
    }
    
    $includedcategories = array_keys($includedcategories);
    $excludedcategories = array_keys($excludedcategories);
    
    // workaround for issue when show unmanaged courses is active and no rules exist for categories/children
    // that sit somewhere under restricted categories
    $excludedchildren = array();
    foreach ($excludedcategories as $exc){
        $excludedchildren = array_merge($excludedchildren,local_lp_get_related_categories($exc));
    }
    $excludedcategories = array_unique(array_merge($excludedcategories,$excludedchildren));
    $excludedcategories = array_diff($excludedcategories,$includedcategories);

    $exstring = implode(',',$excludedcategories);
    $includeclause = '(sub_cat.id IN ('.implode(',',$includedcategories).'))';
    $excludeclause = '(sub_cat.id NOT IN ('.$exstring.') AND '.$defaultview.'=1)';
    $sql = "(SELECT sub_cat.* FROM {course_categories} sub_cat WHERE $includeclause OR $excludeclause)";
    return "$sql $alias";
}

/**
 * Sets up global $DB moodle_database instance
 *
 * @global object
 * @global object
 * @return void
 */
function local_lp_cam_setup_DB() {
    global $CFG, $DB;

    if (isset($DB)) {
        return;
    } 

    if (!isset($CFG->dbuser)) {
        $CFG->dbuser = '';
    }

    if (!isset($CFG->dbpass)) {
        $CFG->dbpass = '';
    }

    if (!isset($CFG->dbname)) {
        $CFG->dbname = '';
    }

    if (!isset($CFG->dblibrary)) {
        $CFG->dblibrary = 'native';
        // use new drivers instead of the old adodb driver names
        switch ($CFG->dbtype) {
            case 'postgres7' :
                $CFG->dbtype = 'pgsql';
                break;

            case 'mssql_n':
                $CFG->dbtype = 'mssql';
                break;

            case 'oci8po':
                $CFG->dbtype = 'oci';
                break;

            case 'mysql' :
                $CFG->dbtype = 'mysqli';
                break;
        }
    }

    if (!isset($CFG->dboptions)) {
        $CFG->dboptions = array();
    }

    if (isset($CFG->dbpersist)) {
        $CFG->dboptions['dbpersist'] = $CFG->dbpersist;
    }

    $DB = local_lp_cam_get_driver_instance($CFG->dbtype, $CFG->dblibrary);
    if (!$DB && !($DB = moodle_database::get_driver_instance($CFG->dbtype, $CFG->dblibrary))) {
        throw new dml_exception('dbdriverproblem', "Unknown driver $CFG->dblibrary/$CFG->dbtype");
    }

    try {
        $DB->connect($CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->dbname, $CFG->prefix, $CFG->dboptions);
    } catch (moodle_exception $e) {
        if (empty($CFG->noemailever) and !empty($CFG->emailconnectionerrorsto)) {
            if (file_exists($CFG->dataroot.'/emailcount')){
                $fp = @fopen($CFG->dataroot.'/emailcount', 'r');
                $content = @fread($fp, 24);
                @fclose($fp);
                if((time() - (int)$content) > 600){
                    //email directly rather than using messaging
                    @mail($CFG->emailconnectionerrorsto,
                        'WARNING: Database connection error: '.$CFG->wwwroot,
                        'Connection error: '.$CFG->wwwroot);
                    $fp = @fopen($CFG->dataroot.'/emailcount', 'w');
                    @fwrite($fp, time());
                }
            } else {
               //email directly rather than using messaging
               @mail($CFG->emailconnectionerrorsto,
                    'WARNING: Database connection error: '.$CFG->wwwroot,
                    'Connection error: '.$CFG->wwwroot);
               $fp = @fopen($CFG->dataroot.'/emailcount', 'w');
               @fwrite($fp, time());
            }
        }
        // rethrow the exception
        throw $e;
    }

    $CFG->dbfamily = $DB->get_dbfamily(); // TODO: BC only for now

    return true;
}

 /**
 * Loads and returns a database instance with the specified type and library.
 * @param string $type database type of the driver (mysqli, pgsql, mssql, sqldrv, oci, etc.)
 * @param string $library database library of the driver (native, pdo, etc.)
 * @param boolean $external true if this is an external database
 * @return moodle_database driver object or null if error
 */
function local_lp_cam_get_driver_instance($type, $library, $external = false) {
    global $CFG;

    $classname = 'local_lp_cam_'.$type.'_'.$library.'_database';

    $instance = new $classname($external);
    
    return $instance;
}

class local_lp_cam_mysqli_native_database extends mysqli_native_moodle_database {
    
    public function __construct($external = false) {
        parent::__construct($external);
    }
     
    public function get_recordset_sql($sql, array $params = null, $limitfrom = 0, $limitnum = 0) {    
        return parent::get_recordset_sql(local_lp_cam_filter_sql($sql), $params, $limitfrom, $limitnum);
    }
    
    public function get_fieldset_sql($sql, array $params = null) {    
        return parent::get_fieldset_sql(local_lp_cam_filter_sql($sql), $params);
    }
    
    public function get_records_sql($sql, array $params=null, $limitfrom=0, $limitnum=0){   
        return parent::get_records_sql(local_lp_cam_filter_sql($sql), $params, $limitfrom, $limitnum);
    }
    
    public function override_get_records_sql($sql, array $params=null, $limitfrom=0, $limitnum=0){   
        return parent::get_records_sql($sql, $params, $limitfrom, $limitnum);
    }
    
    /**
     * Get a single database record as an object using a SQL statement.
     *
     * The SQL statement should normally only return one record.
     * It is recommended to use get_records_sql() if more matches possible!
     * 
     * 2012-11-27 - added redirect when a course/category record is not found to exist when a result of course access rules. DPMH
     *
     * @param string $sql The SQL string you wish to be executed, should normally only return one record.
     * @param array $params array of sql parameters
     * @param int $strictness IGNORE_MISSING means compatible mode, false returned if record not found, debug message if more found;
     *                        IGNORE_MULTIPLE means return first, ignore multiple records found(not recommended);
     *                        MUST_EXIST means throw exception if no record or multiple records found
     * @return mixed a fieldset object containing the first matching record, false or exception if error not found depending on mode
     * @throws dml_exception if error
     */
    public function get_record_sql($sql, array $params=null, $strictness=IGNORE_MISSING) {
        $strictness = (int)$strictness; // we support true/false for BC reasons too
        if ($strictness == IGNORE_MULTIPLE) {
            $count = 1;
        } else {
            $count = 0;
        }
        if (!$records = $this->get_records_sql($sql, $params, 0, $count)) {
            // not found
            if ($strictness == MUST_EXIST) {
                if (0 === preg_match('/\{course\}|\{course_categories\}/',$sql)) {
                    throw new dml_missing_record_exception('', $sql, $params);
                } else {
                    $this->do_access_redirect();
                }
            }
            return false;
        }

        if (count($records) > 1) {
            if ($strictness == MUST_EXIST) {
                throw new dml_multiple_records_exception($sql, $params);
            }
            debugging('Error: mdb->get_record() found more than one record!');
        }

        $return = reset($records);
        return $return;
    }
    

    /**
     * Get a single database record as an object which match a particular WHERE clause.
     *
     * @param string $table The database table to be checked against.
     * @param string $select A fragment of SQL to be used in a where clause in the SQL call.
     * @param array $params array of sql parameters
     * @param int $strictness IGNORE_MISSING means compatible mode, false returned if record not found, debug message if more found;
     *                        IGNORE_MULTIPLE means return first, ignore multiple records found(not recommended);
     *                        MUST_EXIST means throw exception if no record or multiple records found
     * 
     * 2012-11-27 - added redirect when a course/category record is not found to exist when a result of course access rules. DPMH
     * 
     * @return mixed a fieldset object containing the first matching record, false or exception if error not found depending on mode
     * @throws dml_exception if error
     */
    public function get_record_select($table, $select, array $params=null, $fields='*', $strictness=IGNORE_MISSING) {
        if ($select) {
            $select = "WHERE $select";
        }
        try {            
            $ret = $this->get_record_sql("SELECT $fields FROM {" . $table . "} $select", $params, $strictness);
            if (empty($ret) && ($table === 'course' || $table === 'course_categories') && MUST_EXIST == $strictness) {                
                $this->do_access_redirect(); 
            }
            return $ret;
        } catch (dml_missing_record_exception $e) {
            // create new exception which will contain correct table name
            throw new dml_missing_record_exception($table, $e->sql, $e->params);
        }
    }
    
    protected function do_access_redirect() {
        global $CFG; 
        
        if (isloggedin() && !isguestuser()) {
            // redirect to new access message
            redirect("{$CFG->wwwroot}/local/lp_courseaccessmanager/accessdenied.php");
            exit;
        } else {
            redirect("{$CFG->wwwroot}/login/index.php");
            exit;
        } 
    }

}


