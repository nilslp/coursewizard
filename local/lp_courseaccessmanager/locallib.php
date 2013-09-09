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
 * Library code for Learning Pool Course Access plugin
 *
 * @package    local
 * @subpackage lp_courseaccessmanager
 * @copyright  2012 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/lib/deprecatedlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');
include_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');
include_once($CFG->dirroot.'/local/learningpool/utils.php');
    
defined('MOODLE_INTERNAL') || die();

define('LOCAL_CAM_CONTEXT_INDIVIDUAL',10);
define('LOCAL_CAM_CONTEXT_HIERARCHY',20);

/**
 * Get the URL of a script within this plugin.
 * @param string $script the script name, without .php. E.g. 'index'.
 * @param array $params URL parameters (optional).
 */
function local_lp_courseaccessmanager_url($script, $params = array()) {
    return new moodle_url('/local/lp_courseaccessmanager/' . $script . '.php', $params);
}

/**
 * global settings form
 */
class local_lp_courseaccessmanager_global_settings_form extends moodleform {
    function definition() {
        global $CFG;
        $mform = &$this->_form;

        $mform->addElement('header', 'settings', get_string('globalsettings', 'local_lp_courseaccessmanager'));

        $defaultview = get_config('local/lp_courseaccessmanager', 'defaultview');
        $mform->addElement(
                'select',
                'defaultview',
                get_string('defaultview', 'local_lp_courseaccessmanager'),
                array(
                    'hide'=>get_string('owncourses', 'local_lp_courseaccessmanager'),
                    'show'=>get_string('allcourses', 'local_lp_courseaccessmanager')
                ));

        if ($defaultview) {
            $mform->setDefault('defaultview', $defaultview);
        }
        
        $forceloginforcoursesearch = get_config('local/lp_courseaccessmanager', 'forceloginforcoursesearch');
        $mform->addElement('select','forceloginforcoursesearch', get_string('forceloginforcoursesearch', 'local_lp_courseaccessmanager'),array(
            0 => get_string('no'),
            1 => get_string('yes')
        ));
        $mform->setDefault('forceloginforcoursesearch', $forceloginforcoursesearch);
        $mform->addHelpButton('forceloginforcoursesearch', 'forceloginforcoursesearch', 'local_lp_courseaccessmanager');
                
        $this->add_action_buttons();
    }

}

/**
 * Class provides functionality for managing course acces,
 * lots of ajax bound functions and db wrappers 
 */
class local_lp_courseaccessmanager_manager {
            
    public function __construct(){
    }
    
    /**
     * utility for getting a definite value for excluded hierarchies
     * defaults to returning -1 to save empty checks throughout the code
     * @global object $CFG
     * @return int|string 
     */
    private function get_lp_hier() {
        global $CFG;
        if (empty($CFG->block_lp_hierarchy_excluded)){
            return -1;
        }
        return $CFG->block_lp_hierarchy_excluded;
    }
    
    private function post_process_nodes(array &$nodes){
        $assigned = 0;
        
        foreach ($nodes as &$node){
            if (isset($node->children) && !empty($node->children)){
                $count = $this->post_process_nodes($node->children);
                if ($count){
                    if ((count($node->children) === $count)){
                        $node->data['css_class'] =  'assigned';
                    } else {
                        $node->data['css_class'] = 'has-children';
                    }
                }                 
            }
            
            // record both has children and assigned states in the count 
            if (!empty($node->data['css_class'])){
                ++$assigned;
            }
        }
        
        return $assigned;
    }
    
    /**
     * Utility - grabs userids of users under the specified hierarch(y|ies)
     * @global object $DB
     * @param string $hierarchyids
     * @return string CSV of user ids 
     */
    private function get_users_by_hierarchy( $hierarchyids ) {
        global $DB;
        
        if (empty($hierarchyids)){
            $hierarchyids = '-1';
        }
        
        $exclusionselect = 'SELECT `u`.`id` 
            FROM {user} `u` 
            JOIN {user_info_data} `d`
            ON `u`.`id` = `d`.`userid`
            WHERE `d`.`fieldid` = (
                SELECT `id` 
                FROM {user_info_field} 
                WHERE `shortname`="hierarchyid"
            )
            AND '.$DB->sql_substr('`d`.`data`',1,1).' IN ( '.$hierarchyids.' );';
            
        $result = $DB->get_records_sql($exclusionselect);      
          
        if (!empty($result)){
            $result = implode(',', array_keys($result));
        }
        
        return $result;
    }
        
    /**
     * Gets all rules in the lp_access_rule table for the UI
     * @global object $DB
     * @return array json ready result
     */
    public function getrules() {
        global $DB, $CFG;
                     
        $result = array();
        
        try {
            require_sesskey();
        } 
        catch (Exception $e) {
            $result['success']      = false;
            $result['message']      = get_string('sessionerroruser', 'error');
            $result['redirect']     = $CFG->wwwroot.'/login/index.php';
            return $result;            
        }
                        
        $records = $DB->get_records('lp_access_rule',null,'id ASC','id,name,active,allowdeeplinking');
        
        $result['success'] = true;
        $result['rules'] = array_slice($records,0);        
        
        return $result;        
    }
    
    /**
     * Gets the course and category instances related to a rule
     * @global object $DB
     * @param int $ruleid the rule to check
     * @return boolean|array false on failure, otherwise an array of courses and categories
     */
    public function get_rule_context( $ruleid ){
        global $DB;
        $context = false;
        
        $sql = 'SELECT ct.id AS contextid,ct.contextlevel,ct.instanceid 
            FROM {context} ct 
            WHERE ct.id IN (
                SELECT context 
                    FROM {lp_access_context} ac
                    WHERE ac.rule=?
            )';
        $records = $DB->get_records_sql($sql,array($ruleid));
        
        if (empty($records)){
            return $context;
        }
        
        $context = array();
        
        foreach ($records as $rec){
           if ($rec->contextlevel == CONTEXT_COURSECAT){
               $category = $DB->get_record('course_categories',array('id'=>$rec->instanceid),'name');
               if ($category){
                   $context [$rec->contextid]= array('id'=>$rec->instanceid,'name'=>$category->name,'type'=>'category');
               }
           } else if ($rec->contextlevel == CONTEXT_COURSE){
               $course = $DB->get_record('course',array('id'=>$rec->instanceid),'fullname');
               if ($course){
                   $context [$rec->contextid]= array('id'=>$rec->instanceid,'name'=>$course->fullname,'type'=>'course');
               }
           }
        }
        
        return $context;
    }
    
    /**
     * Gets the assigned users/hierarchies associated with a rule
     * @global object $DB
     * @param int $ruleid
     * @return boolean|array - false on failure, otherwise an array of assignments 
     */
    public function get_rule_assignment($ruleid){
        global $DB;
        $assignment = false;
                
        $records = $DB->get_records(
                'lp_access_assignment',
                array('rule'=>$ruleid),
                'id,assigned,assignmentcontext'
                );
        
        if (empty($records)){
            return $assignment;
        }
        
        $assignment = array(
            'hierarchy' => array(),
            'users' => array()
        );
        
        
        foreach ($records as $rec){
           if ($rec->assignmentcontext == LOCAL_CAM_CONTEXT_HIERARCHY){
               $hierarchy = $DB->get_record('lp_hierarchy',array('id'=>$rec->assigned),'fullname');
               if ($hierarchy){
                   $assignment['hierarchy'] []= array('id'=>$rec->assigned,'name'=>$hierarchy->fullname);
               }
           } else if ($rec->assignmentcontext == LOCAL_CAM_CONTEXT_INDIVIDUAL){
               $user = $DB->get_record('user',array('id'=>$rec->assigned),'lastname,firstname');
               if ($user){
                   $assignment['user'] []= array('id'=>$rec->assigned,'lastname'=>$user->lastname,'firstname'=>$user->firstname);
               }
           }
        }
        
        return $assignment;
    }
    
    /**
     * STUB - possibly validate a context on rule creation
     * @global object $DB
     * @param type $context
     * @return boolean 
     */
    private function validate($context){
        global $DB;
        //@TODO stub
        return true;
    }
    
    /**
     * Creates an empty rule and sends a response back to client for context
     * and assignment definition
     * @global object $DB
     * @return array json ready result
     */
    public function createrule() {
        global $DB, $CFG;
                     
        $result = array();
        
        try {
            require_sesskey();
        } 
        catch (Exception $e) {
            $result['success']      = false;
            $result['message']      = get_string('sessionerroruser', 'error');
            $result['redirect']     = $CFG->wwwroot.'/login/index.php';
            return $result;            
        }
        
        $ruleid = $DB->get_record_sql('SELECT IFNULL(MAX(id)+1,1) AS id FROM {lp_access_rule}');
        
        if (!$ruleid){
            $result['success'] = false;
            $result['message'] = get_string('createrulefailed', 'local_lp_courseaccessmanager');
            return $result;
        }
        
        $rule = new stdClass;
        $rule->name = get_string('defaultruletitle', 'local_lp_courseaccessmanager',$ruleid);
        $rule->active = '1';
        $rule->allowdeeplinking = '0';
        
        $exclude = $this->get_lp_hier();
        $hierarchy = Hierarchy::get_instance();
        $hierarchy = $hierarchy->build_hierarchy_tree(explode(',',$exclude));
        
        $result['success'] = true;    
        $result['rule'] = $rule;
        $result['courses'] = local_lp_getcoursetree();
        $result['hierarchy'] = $hierarchy;
                
        return $result;        
    }
    
    /**
     * Saves a fully designated rule into the DB
     * @global object $DB
     * @return array json ready result
     * @throws dml_write_exception 
     */
    public function saverule() {
        global $DB,$CFG;
        
        $result = array();
        try {
            require_sesskey();
        } 
        catch (Exception $e) {
            $result['success']      = false;
            $result['message']      = get_string('sessionerroruser', 'error');
            $result['redirect']     = $CFG->wwwroot.'/login/index.php';
            return $result;            
        }
        
        $rulename = optional_param('rule_name','',PARAM_TEXT);
        $hierids = optional_param('hierids','',PARAM_TEXT);
        $courseids = optional_param('courseids','',PARAM_TEXT);
        $userids = optional_param('userids','',PARAM_TEXT);
        
        if (empty($courseids) || (empty($hierids) && empty($userids))){
            $result['success'] = false;
            $result['message'] = get_string('undefinedrule','local_lp_courseaccessmanager');
            return $result;
        }
        
        $userids = explode(',',$userids);
        $hierids = local_lp_parse_hierarchy_param($hierids);
        $context = local_lp_parse_courselist_param($courseids);
        
        // some validation
        if (!$this->validate($context)){
            $result['success'] = false;
            $result['message'] = get_string('invalidruleassignment','local_lp_courseaccessmanager');
        }
        
        
        // create the rule
        try {
            
            $time = time();
            
            // insert new rule
            $rule = $DB->insert_record(
                    'lp_access_rule',
                    array(
                        'name'=>$rulename,
                        'active'=>1,
                        'allowdeeplinking'=>0,
                        'timemodified'=>$time
                        ),
                    true
                    );
            
            // if access is granted to a course but not it's category, we need to force access to the category as well
            $coursecats = array();
            
            // insert associated course contexts
            foreach ($context['course'] as $c){
                $contextid = $DB->get_field('context','id',array('instanceid'=>$c, 'contextlevel'=>CONTEXT_COURSE), MUST_EXIST);
                if (!$contextid){
                    throw new dml_write_exception('local_lp_courseaccessmanager_manager::saverule() error getting context id');
                }
                
                $catid = $DB->get_field('course','category',array('id'=>$c));
                if ($catid){
                    $coursecats["$catid"] = 1;
                }
                
                $DB->insert_record(
                    'lp_access_context',
                    array(
                        'rule'=>$rule,
                        'context'=>$contextid
                    )
                );
            }
            
            // merge our found course categories into the context categories
            $context['category'] = array_unique(array_merge($context['category'],array_keys($coursecats)));
            
            // insert associated category contexts
            foreach ($context['category'] as $c){
                $contextid = $DB->get_field('context','id',array('instanceid'=>$c, 'contextlevel'=>CONTEXT_COURSECAT), MUST_EXIST);
                if (!$contextid){
                    throw new dml_write_exception('local_lp_courseaccessmanager_manager::saverule() error getting context id');
                }
                $DB->insert_record(
                    'lp_access_context',
                    array(
                        'rule'=>$rule,
                        'context'=>$contextid
                    )
                );
            }
            
            
            // insert assigned hierarchies
            foreach ($hierids as $hid){
                if (empty($hid)){
                    continue;
                }
                
                $DB->insert_record(
                        'lp_access_assignment',
                        array(
                            'rule'=>$rule,
                            'assigned'=>$hid,
                            'assignmentcontext'=>20,
                            'timemodified'=>$time
                        )
                    );
            }
            
            // insert assigned users
            foreach ($userids as $uid){
                if (empty($uid)){
                    continue;
                }
                
                $DB->insert_record(
                        'lp_access_assignment',
                        array(
                            'rule'=>$rule,
                            'assigned'=>$uid,
                            'assignmentcontext'=>10,
                            'timemodified'=>$time
                        )
                    );
            }
            
        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = get_string('dberror', 'local_lp_courseaccessmanager', $e->getMessage());
            return $result;
        }
        
        $result['success'] = true;
        $result['message'] = get_string('createrulesucceeded','local_lp_courseaccessmanager',$rulename);
        $result['chainaction'] = 'getrules';
        
        return $result;        
    }    
    
    /**
     * Gets the specifc assignments and contexts associated with a rule in a format
     * ready to be sent to the client as a json encoded response
     * @return array json ready result
     */
    public function viewrule() {      
        global $CFG;
        $ruleid = optional_param('rule',-1,PARAM_INT);
        $result = array();
        try {
            require_sesskey();
        } 
        catch (Exception $e) {
            $result['success']      = false;
            $result['message']      = get_string('sessionerroruser', 'error');
            $result['redirect']     = $CFG->wwwroot.'/login/index.php';
            return $result;            
        }
        
        $exclude = $this->get_lp_hier();
        
        $context = $this->get_rule_context($ruleid);
        $assignment = $this->get_rule_assignment($ruleid);
        
        $coursetree = local_lp_getcoursetree();
        foreach ($context as $ctx=>$c){
            $node = &$coursetree->locate($coursetree->nodes->children, array( 'context'=>"$ctx"  ) );            
            $node->data['css_class'] = 'assigned';
        }
        
        $hierarchy = Hierarchy::get_instance();
        $hierarchy = $hierarchy->build_hierarchy_tree(explode(',',$exclude));
        foreach ($assignment['hierarchy'] as $hier){
            $node = &$hierarchy->find_parent($hierarchy->nodes->children, $hier['id']);
            $node->data['css_class'] = 'assigned';
        }
        
        // post process on nodes
        $this->post_process_nodes($hierarchy->nodes->children);
        $this->post_process_nodes($coursetree->nodes->children);
        
        $result['success'] = true;
        $result['courses'] = $coursetree;
        $result['hierarchy'] = $hierarchy;
        $result['users'] = $assignment['users'];
        
        return $result;        
    }
    
   
    /**
     * Toggles a given rule on and off if it exists
     * @global object $DB
     * @return array json ready result 
     */
    public function togglerule() {
        global $DB, $CFG;
                     
        $ruleid = optional_param('rule',-1,PARAM_INT);
        $result = array();
        try {
            require_sesskey();
        } 
        catch (Exception $e) {
            $result['success']      = false;
            $result['message']      = get_string('sessionerroruser', 'error');
            $result['redirect']     = $CFG->wwwroot.'/login/index.php';
            return $result;            
        }
        
        $active = $DB->get_field('lp_access_rule','active',array('id'=>$ruleid));
        if ($active === false){
            $result['success'] = false;
            $result['message'] = get_string('invalidrule','local_lp_courseaccessmanager');
            return $result;
        }
        
        $active = ($active ? 0 : 1); // could use shorthand ($active=!$active) but sticking with clear integer values
        
        $result['success'] = $DB->update_record('lp_access_rule',array('id'=>$ruleid,'active'=>$active));
        
        if ($result['success']){
            $result['active'] = $active;
            $result['rule'] = $ruleid;
        } else {
            $result['message'] = get_string('unsuccessfulupdate','local_lp_courseaccessmanager');
        }
        
        return $result;        
    }
    
    /**
     * Toggles deeplinking on and off for a given rule
     * @global object $DB
     * @return array json ready result
     */
    public function deeplinkrule() {
        global $DB,$CFG;
                     
        $ruleid = optional_param('rule',-1,PARAM_INT);
        $result = array();
        try {
            require_sesskey();
        } 
        catch (Exception $e) {
            $result['success']      = false;
            $result['message']      = get_string('sessionerroruser', 'error');
            $result['redirect']     = $CFG->wwwroot.'/login/index.php';
            return $result;            
        }
        
        $allow = $DB->get_field('lp_access_rule','allowdeeplinking',array('id'=>$ruleid));
        if ($allow === false){
            $result['success'] = false;
            $result['message'] = get_string('invalidrule','local_lp_courseaccessmanager');
            return $result;
        }
        
        $allow = ($allow ? 0 : 1); // could use shorthand ($allow=!$allow) but sticking with clear integer values
        
        $result['success'] = $DB->update_record('lp_access_rule',array('id'=>$ruleid,'allowdeeplinking'=>$allow));
        
        if ($result['success']){
            $result['allowdeeplinking'] = $allow;
            $result['rule'] = $ruleid;
        } else {
            $result['message'] = get_string('unsuccessfulupdate','local_lp_courseaccessmanager');
        }
        
        return $result;        
    }    
    
    /**
     * Deletes a given rule if it exists
     * @global object $DB
     * @return array json ready result
     */
    public function deleterule() {
        global $DB,$CFG;
                     
        $result = array();
        try {
            require_sesskey();
        } 
        catch (Exception $e) {
            $result['success']      = false;
            $result['message']      = get_string('sessionerroruser', 'error');
            $result['redirect']     = $CFG->wwwroot.'/login/index.php';
            return $result;            
        }
        $ruleid = optional_param('rule',-1,PARAM_INT);
        $confirmed = optional_param('confirmed',0,PARAM_INT);
        
        if (!$confirmed){
            $result['confirm'] = get_string('confirmruledelete','local_lp_courseaccessmanager');
            $result['rule'] = $ruleid;
            return $result;
        }
        
        $result['success'] = false;    
        $result['success'] = $DB->delete_records('lp_access_assignment',array('rule'=>$ruleid));
        
        if ($result['success']){
            $result['success'] = $DB->delete_records('lp_access_context',array('rule'=>$ruleid));
        }
        
        if ($result['success']){
            $result['success'] = $DB->delete_records('lp_access_rule',array('id'=>$ruleid));
        }
               
        if ($result['success']){
            $result['rule'] = $ruleid;
            $result['chainaction'] = 'getrules';
        } else {
            $result['message'] = get_string('unsuccessfulupdate','local_lp_courseaccessmanager');
        }
        
        return $result;        
    }
    
    /**
     * @UNUSED
     * @TODO
     * Idea is to return paginated list of users who fall under a given rule
     * - not even half implemented yet. basically just some cannibalized code
     * from the enrolment manager - DPMH
     * @global object $DB
     * @return type 
     */
    public function getaccess() {
        global $DB;
        
        $page = optional_param('p', 1, PARAM_INT);
        $perpage = optional_param('pp', 30, PARAM_INT);
        $srch = optional_param('srch', '', PARAM_TEXT);
        $limitstart = (int)(($page-1) * $perpage);
        $result = array();
        
        if (empty($this->course)) {
            $result['success'] = false;
            $result['message'] = get_string('nocoursesfound','local_lp_courseaccessmanager');
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
            JOIN {lp_access_assignment} ae 
                ON ae.userid = u.id
            JOIN {lp_access_rule} a ON a.id=ae.rule
            WHERE a.context = -1 AND u.deleted = 0 ' .
            $excludeusers .
            $srch;
                
        $count = $DB->get_record_sql($countsql.$filtersql.';');
        $users = $DB->get_records_sql($usersql.$filtersql.' LIMIT '.$limitstart.','.$perpage.';');
        
        $result['totaldesc'] = empty($srch) ? get_string('totalusers', 'local_lp_courseaccessmanager') : get_string('totalusersfound', 'local_lp_courseaccessmanager');
        $result['totalusers']= $count->total;  
        $result['page']      = $page;
        $result['perpage']   = $perpage;
        $result['users']     = array_slice($users,0);
        $result['success']   = true;
        $result['depttitle'] = 'Department';    // @TODO - should reflect the title of the nth depth of the hierarchy 
        $result['course']    = $this->course;  
        $result['category']  = $this->category;  
        
        return $result;
    }
}
