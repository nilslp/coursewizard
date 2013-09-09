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
 * Library code for Learning Pool Enrolment Manager
 *
 * @package    local
 * @subpackage lp_enrolment_manager
 * @copyright  2011 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
    
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/lib/deprecatedlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/enrol/locallib.php');
include_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');
include_once($CFG->dirroot.'/local/learningpool/utils.php');
include_once($CFG->libdir.'/coursecatlib.php');

defined('MOODLE_INTERNAL') || die();

// local defines
define('LOCAL_LP_ENROLMENT_MANAGER_USER_LIMIT', 200);
define('LOCAL_LP_ENROLMENT_MANAGER_EMAIL_BATCH_SIZE', 1000);

/**
 * Get the URL of a script within this plugin.
 * @param string $script the script name, without .php. E.g. 'index'.
 * @param array $params URL parameters (optional).
 */
function local_lp_enrolment_manager_url($script, $params = array()) {
    return new moodle_url('/local/lp_enrolment_manager/' . $script . '.php', $params);
}

/**
 * Helper function calls print_r on an object and adds a line break
 * @param object $obj the object to print
 * @param boolean|optional whether to echo the result, default is false
 * @return string the result of the print_r
 */
function local_lp_enrolment_manager_printobj($obj, $echo = false) {
    $str = print_r($obj, true) . '<br />';
    if ($echo){
        echo $str;  
    }  
    return $str;
}

function local_lp_enrolment_manager_sendqueuedemail($cron=false) {
    global $DB, $CFG;
    
    $msg = array();
    $sent = array();
    
    $sql = 'SELECT  `q`.`id` AS qid, 
            `q`.`template`, 
            `u`.*, 
            CONCAT(`u`.`firstname`, " ", `u`.`lastname`) as fullname,             
            `e`.`sender`, 
            `e`.`subject`,  
            `e`.`body`, 
            `e`.`course` 
        FROM {user} u 
        JOIN {lp_enrol_email_queue} q  
            ON `u`.`id` = `q`.`userid`
        JOIN {lp_enrol_email} e 
            ON `e`.`id` = `q`.`template` 
        WHERE `q`.`sent` = 0  
        ORDER BY `q`.`template`  
        LIMIT '.(int)LOCAL_LP_ENROLMENT_MANAGER_EMAIL_BATCH_SIZE.';';
    
    $recipients = $DB->get_records_sql($sql);
    
    if (count($recipients) == 0){
        // nothing to do 
        return 'local_lp_enrolment_manager_sendqueuedemail() - no users in queue.';
    }
        
    foreach ($recipients as $r) {       
        if (!isset($mgr) || (empty($mgr->course)) || ($mgr->course->id != $r->course)){
            $mgr = new local_lp_enrolment_manager_usermanager($r->course);
        }
      
        if ($cron) {
            cron_setup_user($r);
        }
       
	    $plaintext = $mgr->body_from_template($r->body);
		$htmltext = nl2br($plaintext);
        email_to_user($r, $r->sender, $r->subject, $plaintext, $htmltext);        
            
        $sent []= $r->qid;
    }
    
    $DB->execute(
        'UPDATE '.$DB->get_prefix().'lp_enrol_email_queue 
        SET sent=?,timemodified=? 
        WHERE id IN('.implode(',',$sent).')',
        array(1,time()) 
    );
    
    $msg []= 'Successfully emailed '.count($recipients).' users.';
    
    return implode(',', $msg);
}

class local_lp_enrolment_manager_usermanager {
    
    private $course;
    
    public function __construct($courseid){
        $this->set_course($courseid);
    }
    
    private function set_course($courseid){     
        global $DB;
        $this->course = $DB->get_record( 'course', array( 'id' => $courseid ), '*' ); 
        if (!empty($this->course)){
            $this->course->startdate = date('j F Y H:i:s',(int)$this->course->startdate );
            $this->course->timecreated = date('j F Y H:i:s',(int)$this->course->timecreated);
            //$this->course->category = get_course_category($this->course->category);
            $this->course->category = coursecat::get($this->course->category);
            $this->course->category = $this->course->category->name ? $this->course->category->name : get_string('unknowncategory', 'local_lp_enrolment_manager');        
        }
    }
    
    private function get_lp_hier() {
        global $CFG;
        if (empty($CFG->block_lp_hierarchy_excluded)){
            return -1;
        }
        return $CFG->block_lp_hierarchy_excluded;
    }
    
    private function get_replacements() {
        global $CFG, $USER;
        $replacements = array(
            'Profile url'       => array('tag' => 'profileurl', 'val' => $CFG->wwwroot.'/user/view.php?id='.$USER->id.'&course='.$this->course->id),
            'Username'          => array('tag' => 'username', 'val' => $USER->username),
            'Full name'         => array('tag' => 'fullname', 'val' => $USER->firstname.' '.$USER->lastname),
            'Course name'       => array('tag' => 'coursename', 'val' => $this->course->fullname),
            'Course URL'        => array('tag' => 'courseurl', 'val' => $CFG->wwwroot.'/course/view.php?id='.$this->course->id),
            'My email'          => array('tag' => 'myemail', 'val' => $USER->email)
        );
        // any new fields can be added here ...
        return $replacements;
    }

    private function get_course_url() {
        global $CFG, $DB;
        
        $sco = $DB->get_record_sql('SELECT id,scorm  
                FROM {scorm_scoes}  
                WHERE scorm IN( 
                    SELECT DISTINCT(id) 
                    FROM {scorm} 
                    WHERE course='.$this->course->id.' 
                ) 
                AND scormtype = "sco"  
                ORDER BY id 
                LIMIT 1;');
                            
        if(!empty($sco)){
            return $CFG->wwwroot.'/mod/scorm/player.php?a='.$sco->scorm.'&scoid='.$sco->id;               
        }

        return $CFG->wwwroot.'/course/view.php?id='.$this->course->id;
    }
    
    private function replace_tags( $body ) {
        $replacements = $this->get_replacements();
        foreach ( $replacements as $r ) {
            $body = preg_replace('/\{'.$r['tag'].'\}/', $r['val'], $body);
        }
        return $body;
    }
    
    private function get_users_by_hierarchy( $hierarchyids ) {
        global $DB;
        
        if (empty($hierarchyids)){
            $hierarchyids = '-1';
        }
        
        $exclusionselect = 'SELECT userid FROM {lp_user_hierarchy} WHERE hierarchyid IN ( '.$hierarchyids.' );';
            
        $result = $DB->get_records_sql($exclusionselect);      
          
        if (!empty($result)){
            $result = implode(',', array_keys($result));
        }
        
        return $result;
    }
    
    private function get_unenrolled_by_hierarchy_string( $hierarchyids, $courseid ) {
        global $DB;
        
        $instring = '\'' . preg_replace('/-/', '\',\'', $hierarchyids) . '\'';
       
        if (empty($instring)){
            $instring = '-1';
        }
        
        // exclude users who are already enrolled
        $sql = 'SELECT u.id FROM {user} u
            JOIN {lp_user_hierarchy} muh
            ON u.id=muh.userid
            JOIN {user_enrolments} ue 
                ON ue.userid = u.id
            JOIN {enrol} e
            ON e.id=ue.enrolid
            WHERE e.courseid = '.(int)$courseid .
            ' AND u.deleted = 0' .
            ' AND muh.hierarchyid IN ('.$instring.');';
        
        $excludedusers = $DB->get_records_sql($sql);
       
        $excludesql = '';
        if (!empty($excludedusers)) {
            $excludesql = ' AND u.id NOT IN(\''.implode('\',\'', array_keys($excludedusers)).'\')';
        }
        
        // get users to enrol
        $sql = 'SELECT u.id FROM {user} u 
            JOIN {lp_user_hierarchy} muh 
            ON u.id=muh.userid 
            WHERE muh.hierarchyid IN ('.$instring.') AND u.deleted <> 1 '.$excludesql.';';
        
        return $DB->get_records_sql($sql);
    }
    
    private function get_admin_emails() {
        if (empty($this->course)) {
            return '';
        }
        
        $excludeusers = $this->get_users_by_hierarchy($this->get_lp_hier());
        $context = get_context_instance(CONTEXT_COURSE, $this->course->id);
        $admins = get_users_by_capability($context, 'moodle/course:update','u.id,u.email','','','','',$excludeusers);
        $emails = array();
        foreach ($admins as $admin){
            $emails []= $admin->email;
        }
        
        return array_unique($emails);
    }
        
    private function email_later( $users, $sender, $subject, $body ) {
        global $DB;
        
        if (empty($this->course) || count($users) == 0) {
            // nothing to do
            return;
        }
        
        // first insert the template and get the id
        $data = new stdClass();
        $data->sender       = $sender;
        $data->subject      = $subject;
        $data->body         = $body;
        $data->course      = $this->course->id;
        $tid = $DB->insert_record('lp_enrol_email', $data, true);
        
        $user = new stdClass();
        $user->template     = $tid;
        $user->sent         = 0;
        $user->timemodified = time();
        foreach ($users as $uid) {
            $user->userid       = $uid;
            $DB->insert_record('lp_enrol_email_queue', $user);
        }
    }
    
    public function body_from_template( $body ) {
        return $this->replace_tags($body);
    }
        
    public function gethierarchy() {        
        $result = array();
        
        if (empty($this->course)) {
            $result['success'] = false;
            $result['message'] = get_string('nocoursesfound','local_lp_enrolment_manager');
            return $result;
        }
        
        if (!class_exists('hierarchy')) {
            $result['success'] = false;
            $result['message'] = get_string('hierarchynotinstalled','local_lp_enrolment_manager');
            return $result;
        }
        
        $hierarchy = Hierarchy::get_instance();
        $hierarchy = $hierarchy->build_hierarchy_tree( explode(',',$this->get_lp_hier()) );
        
        
        $result['success']   = true;
        $result['hierarchy'] = $hierarchy->nodes;
        $result['course']    = $this->course;
        $result['heading']  = get_string('enrolgroup','local_lp_enrolment_manager');
        
        return $result;
    }
    
    public function enrolhierarchy() {
        global $DB;
        
        $hierarchyids = optional_param('hierids', '', PARAM_ALPHANUMEXT);    
        if (empty($hierarchyids)) {        
            $result['success'] = false;
            $result['message'] = get_string('pleaseselecthierarchy','local_lp_enrolment_manager');
            return $result;
        }
        
        if (empty($this->course)) {
            $result['success'] = false;
            $result['message'] = get_string('nocoursesfound','local_lp_enrolment_manager');
            return $result;
        }
        
        $userlist = $this->get_unenrolled_by_hierarchy_string($hierarchyids, $this->course->id);
        if (!empty($userlist)) {
            $userlist = '\''.implode('\',\'', array_keys($userlist)).'\'';
        }
        
        $result                 = $this->enrolusers($userlist);
        #$result['tobeenrolled'] = $userlist;
        #$result['excluded']     = $excludedusers;
        
        return $result;
    }
    
    public function getadmins() {
        global $DB;
        $page = optional_param('p', 1, PARAM_INT);
        $perpage = optional_param('pp', 30, PARAM_INT);
        $limitstart = (int)(($page-1) * $perpage);
        $result = array();
        
        if (empty($this->course)) {
            $result['success'] = false;
            $result['message'] = get_string('nocoursesfound','local_lp_enrolment_manager');
            return $result;
        }
        
        $excludeusers = $this->get_users_by_hierarchy($this->get_lp_hier());
        $context = get_context_instance(CONTEXT_COURSE, $this->course->id);
        $admins = get_users_by_capability($context, 'moodle/course:update','u.id,CONCAT(u.lastname,", ",u.firstname) AS fullname, IF(confirmed,"confirmed","unconfirmed") AS accountstatus ','','','','',$excludeusers);    
        
        if (!empty($admins)){            
            // need to augment admins with lp_hierarchy data
            $adminids = implode(',', array_keys($admins));
            $sql = 'SELECT `muh`.`userid` AS id,`mh`.`id` AS hierid,`mh`.`fullname` AS hiername
                    FROM {lp_hierarchy} mh
                    JOIN {lp_user_hierarchy} muh
                    ON `mh`.`id`=`muh`.`hierarchyid`
                    WHERE `muh`.`userid` IN ('.$adminids.');';
            $hierdata = $DB->get_records_sql($sql);
            
            if (!empty($hierdata)) {
                foreach ($hierdata as $k => $data)  {
                    $admins[$k]->hierid = $data->hierid;
                    $admins[$k]->hiername = $data->hiername;
                }            
            }
            $admins = array_slice($admins,0);
        }
        
        $result['totaldesc'] = get_string('totaladmins', 'local_lp_enrolment_manager');
        $result['totalusers']= empty($admins) ? 0 : count($admins);  
        $result['page']      = $page;
        $result['perpage']   = $perpage;
        $result['users']     = $admins;
        $result['success']   = true;
        $result['depttitle'] = 'Department';    // @TODO - should reflect the title of the nth depth of the hierarchy 
        $result['course']    = $this->course;
        $result['heading']  = get_string('courseadmins','local_lp_enrolment_manager');
        
        return $result;
    }

    public function enrolsummary() {
        global $USER;       
        $result = array();        
        
        if (empty($this->course)) {
            $result['success'] = false;
            $result['message'] = get_string('nocoursesfound','local_lp_enrolment_manager');
            return $result;
        }
        
        $sender     = optional_param('sender', '', PARAM_EMAIL);
        $subject    = optional_param('subject', '', PARAM_TEXT);
        $body       = optional_param('body', '', PARAM_TEXT);
        $preview    = optional_param('preview', '', PARAM_TEXT);
        $userids    = optional_param('userids', '', PARAM_ALPHANUMEXT);
        $hierids    = optional_param('hierids', '', PARAM_ALPHANUMEXT);
        
        $sender     = empty($sender) ? $USER->email : $sender;
        $subject    = empty($subject) ? get_string('defaultemailsubject', 'local_lp_enrolment_manager').$this->course->fullname : $subject;
        $preview    = ($preview === $body) ? $this->replace_tags($body) : $preview; 
        
        if (!empty($userids)){
            $numusers = count(explode('-',$userids));
        } else if (!empty($hierids)) {
            $userlist = $this->get_unenrolled_by_hierarchy_string($hierids, $this->course->id);
            $numusers = count($userlist);
            $userids = implode('-',array_keys($userlist));
        }
        
        $message = str_replace('{num_users}', $numusers, get_string('enrollingnumusers', 'local_lp_enrolment_manager'));
        $warning = '';
        if ($numusers > LOCAL_LP_ENROLMENT_MANAGER_USER_LIMIT) {
            $warning = get_string('warningmanyusers', 'local_lp_enrolment_manager');
        }
        
        $result['success']   = true;
        $result['sender']    = $sender;
        $result['subject']   = $subject;
        $result['body']      = $body;
        $result['preview']   = $preview;
        $result['numusers']  = $numusers;
        $result['users']     = $userids; 
        $result['message']   = $message;
        $result['warning']   = $warning;
        $result['course']    = $this->course;
        
        return $result;        
    }
    
     public function getcoursedesc() {
        $result = array();
        
        if (empty($this->course)) {
            $result['success'] = false;
            $result['message'] = get_string('nocoursesfound','local_lp_enrolment_manager');
            return $result;
        }      
       
        $result['heading'] = get_string('coursedescheader','local_lp_enrolment_manager');
        $result['success'] = true;
        $result['course']  = $this->course;  
        
        return $result;
    }
        
    public function getenrolled() {
        global $DB;
        $page = optional_param('p', 1, PARAM_INT);
        $perpage = optional_param('pp', 30, PARAM_INT);
        $srch = optional_param('srch', '', PARAM_TEXT);
        $limitstart = (int)(($page-1) * $perpage);
        $result = array();
        
        if (empty($this->course)) {
            $result['success'] = false;
            $result['message'] = get_string('nocoursesfound','local_lp_enrolment_manager');
            return $result;
        }
        
        if (!empty($srch)){
            $srch = ' AND CONCAT(u.firstname," ",u.lastname," ",u.firstname) LIKE "%'.preg_replace('/\W+/', '%', $srch).'%" ';            
        }
        
        $excludeusers = $this->get_users_by_hierarchy($this->get_lp_hier());
        if (empty($excludeusers)){
            $excludeusers = '';
        } else {
            $excludeusers = ' AND u.id NOT IN ('.$excludeusers.') ';
        }
        
        $usersql = 'SELECT `u`.`id`,
                CONCAT(`u`.`lastname`, ", ", `u`.`firstname`) as fullname,
                `mh`.`id` as hierid,
                `mh`.`fullname` as hiername, 
                IF(confirmed,"confirmed","unconfirmed") AS accountstatus ';
        $countsql = 'SELECT COUNT(*) AS total ';
        $filtersql = 'FROM {user} `u` 
            JOIN {lp_user_hierarchy} `muh`
                ON `muh`.`userid` = `u`.`id`
            JOIN {lp_hierarchy} `mh`
                ON `muh`.`hierarchyid` = `mh`.`id`
            JOIN {user_enrolments} ue 
                ON ue.userid = u.id
            JOIN {enrol} e ON e.id=ue.enrolid
            WHERE e.courseid = '.(int)$this->course->id .
            ' AND u.deleted = 0 ' .
            $excludeusers .
            $srch;
                
        $count = $DB->get_record_sql($countsql.$filtersql.';');
        $enrolled = $DB->get_records_sql($usersql.$filtersql.' LIMIT '.$limitstart.','.$perpage.';');
        
        $result['totaldesc'] = empty($srch) ? get_string('totalenrolledusers', 'local_lp_enrolment_manager') : get_string('enrolledusersfound', 'local_lp_enrolment_manager');
        $result['totalusers']= $count->total;  
        $result['page']      = $page;
        $result['perpage']   = $perpage;
        $result['users']     = array_slice($enrolled,0);
        $result['success']   = true;
        $result['depttitle'] = 'Department';    // @TODO - should reflect the title of the nth depth of the hierarchy 
        $result['heading']  = get_string('userlistheader','local_lp_enrolment_manager');
        $result['course']    = $this->course;  
        
        return $result;
    }

    public function getindividuals() {
        global $DB;
        $page = optional_param('p', 1, PARAM_INT);
        $perpage = optional_param('pp', 30, PARAM_INT);
        $srch = optional_param('srch', '', PARAM_TEXT);
        $limitstart = (int)(($page-1) * $perpage);
        $result = array();
                
        if (empty($this->course)) {
            $result['success'] = false;
            $result['message'] = get_string('nocoursesfound','local_lp_enrolment_manager');
            return $result;
        }
        
        if (!empty($srch)){
            $srch = 'AND CONCAT(u.firstname," ",u.lastname," ",u.firstname) LIKE "%'.preg_replace('/\W+/', '%', $srch).'%" ';            
        }
        
        // get users already enrolled for exclusion
        $excludeusers = get_enrolled_users(get_context_instance(CONTEXT_COURSE, $this->course->id),'',0,'u.id');
        $excludeusers = implode(',',array_keys($excludeusers));
        
        // ignore learningpool users
        $lpusers = $this->get_users_by_hierarchy($this->get_lp_hier());
        if (!empty($lpusers)){
            $excludeusers .= (empty($excludeusers) ? '' : ',' ) . $lpusers;
        }
        
        if (empty($excludeusers)){
            $excludeusers = -1;
        }
        
        $usersql = 'SELECT `u`.`id`,
                CONCAT(`u`.`lastname`, ", ", `u`.`firstname`) as fullname,
                `mh`.`id` as hierid,
                `mh`.`fullname` as hiername, 
                IF(confirmed,"confirmed","unconfirmed") AS accountstatus ';
        $countsql = 'SELECT COUNT(*) AS total ';
        $filtersql = 'FROM {user} `u` 
            JOIN {lp_user_hierarchy} `muh`
                ON `muh`.`userid` = `u`.`id`
            JOIN {lp_hierarchy} `mh`
                ON `muh`.`hierarchyid` = `mh`.`id` '.
                'AND u.id NOT IN ('.$excludeusers.') ' .
            'WHERE u.deleted = 0 ' .
            $srch;
                
        $count = $DB->get_record_sql($countsql.$filtersql.';');
        $users = $DB->get_records_sql($usersql.$filtersql.'LIMIT '.$limitstart.','.$perpage.';');
        
        $result['totaldesc']    = empty($srch) ? get_string('totalavailableusers', 'local_lp_enrolment_manager') : get_string('availableusersfound', 'local_lp_enrolment_manager');
        $result['success']      = true;
        $result['depttitle']    = 'Department';    // @TODO - should reflect the title of the nth depth of the hierarchy 
        $result['course']       = $this->course;
        $result['users']        = array_slice($users, 0);
        $result['totalusers']   = $count->total;
        $result['page']         = $page;
        $result['perpage']      = $perpage;
        $result['checkable']    = true;
        $result['heading']  = get_string('enrolindividual','local_lp_enrolment_manager');
        
        return $result;
    }
    
    public function enrolusers( $userids = null ) {
        global $PAGE, $USER, $CFG;
                     
        $result = array();
        
        try {
            require_sesskey();
        } 
        catch (Exception $e) {
            $result['success']      = false;
            $result['message']      = get_string('sessionerroruser', 'error');
            $result['redirect']     = new moodle_url('/login/index.php');
            return $result;            
        }
        
        if (empty($this->course)) {
            $result['success'] = false;
            $result['message'] = get_string('nocoursesfound','local_lp_enrolment_manager');
            return $result;
        }
        
        $userids    = optional_param('users', '', PARAM_ALPHANUMEXT);
        $body       = optional_param('body', get_string('defaultemailbody', 'local_lp_enrolment_manager'), PARAM_TEXT);
        $subject    = optional_param('subject', get_string('defaultemailsubject', 'local_lp_enrolment_manager'), PARAM_TEXT);
        $sendemail  = optional_param('sendemail',1,PARAM_INT);
        $sender     = optional_param('sender', $USER->email, PARAM_TEXT);
        if (!empty($userids)){
            $userids = explode('-', $userids);
        }
        
        // enrol code heavily based on cohort enrolment code 
        $manager = new course_enrolment_manager($PAGE, $this->course);        
        $context = $manager->get_context();          
        $instance = false;
        $instances = $manager->get_enrolment_instances();
        foreach ($instances as $i) {
            if ($i->enrol == 'manual') {
                $instance = $i;
                break;
            }
        }
        
        $plugin = enrol_get_plugin('manual');
        if (!$instance || !$plugin || !$plugin->allow_enrol($instance)) {
            $result['success'] = false;
            $result['message'] = get_string('warningmanualenrol', 'local_lp_enrolment_manager');
            return $result;
        }
                
        $count = 0;
        if (!empty($userids)) {
            foreach ($userids as $uid) {
                $count++;
                $plugin->enrol_user($instance, $uid, 5);
            }
        
            if ($sendemail){
                // retain large numbers of users for emailing in batches later
                $this->email_later($userids, $sender, $subject, $body);
            }
            
            // send the first batch immediately (keeps logging and audit trail)
            // @NB disabled because mail failures echo out messages which contaminates
            // our json object - DH 30-11-2011
            // local_lp_enrolment_manager_sendqueuedemail();
        }
        
        $result['success']   = true;
        $result['redirect']  = $CFG->wwwroot.'/local/lp_enrolment_manager/result.php?enrolled='.$count;
        $result['course']    = $this->course;
        
        return $result;
    }

    public function editemail() {
        global $USER, $CFG;           
        $result = array();
        
        if (empty($this->course)) {
            $result['success'] = false;
            $result['message'] = get_string('nocoursesfound','local_lp_enrolment_manager');
            return $result;
        }
                
        $result['success']          = true;
        $result['adminemails']      = $this->get_admin_emails();
        $result['course']           = $this->course;
        $result['body']             = get_string('defaultemailbody', 'local_lp_enrolment_manager');
        $result['subject']          = get_string('defaultemailsubject', 'local_lp_enrolment_manager') . $this->course->fullname;
        $result['replacements']     = $this->get_replacements();
        
        return $result;
        
    }
    
}
