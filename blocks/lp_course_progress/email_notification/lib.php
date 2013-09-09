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
 * @package    blocks
 * @subpackage lp_course_progress
 * @copyright  2012 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/lib/tablelib.php');
require_once($CFG->dirroot.'/lib/accesslib.php');
include_once($CFG->dirroot.'/local/learningpool/utils.php');
require_once($CFG->libdir.'/completionlib.php');
    
defined('MOODLE_INTERNAL') || die();

// some constants
define('LP_NOTIFICATION_DAILY','Daily');
define('LP_NOTIFICATION_WEEKLY','Weekly');
define('LP_NOTIFICATION_MONTHLY','Monthly');

/**
 * Class provides functionality for managing email notifications,
 * lots of ajax bound functions and db wrappers 
 */
class block_lp_course_progress_emailmanager {
            
    public function __construct(){
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
    
    private function calc_interval($freq){
        $interval = 86400;
        switch ($freq){
            case LP_NOTIFICATION_WEEKLY:
                $interval *= 7;
                break;
            case LP_NOTIFICATION_MONTHLY:
                $interval *= 30;
                break;
            default:
                break;
        }
        
        return $interval;
    }
        
    public function get_schedule() {
        global $DB;
                
        return $DB->get_records('lp_incomplete_notification');
    }
    
    public function print_notification_schedule(){        
        // schedules table
        echo html_writer::start_tag('div',array('class'=>'schedule'));
        echo html_writer::tag('h2',get_string('notifcationscheduleheading','block_lp_course_progress'));
        
        $schedule = $this->get_schedule();
        if (!empty($schedule)){
            $this->print_schedule_table($schedule);
        } else {
            echo html_writer::tag('div', get_string('noscheduledemails','block_lp_course_progress'), array('class'=>'noschedule'));
        }
        
        echo html_writer::end_tag('div');
    }
    
    public function print_schedule_table($schedule){
        global $CFG,$OUTPUT;
        
        $table = new flexible_table('email_notification_schedule');
        
        $table->define_columns(
                array(
                    'id',
                    'desc',
                    'start',
                    'end', 
                    'frequency', 
                    'next', 
                    'status', 
                    'action'
                    )
                );
        
        $table->define_headers(
                array(
                    get_string('notificationid','block_lp_course_progress'),
                    get_string('notificationdesc','block_lp_course_progress'),
                    get_string('notificationstart','block_lp_course_progress'),
                    get_string('notificationend','block_lp_course_progress'),
                    get_string('notificationfreq','block_lp_course_progress'),
                    get_string('notificationnext','block_lp_course_progress'),
                    get_string('notificationstatus','block_lp_course_progress'),
                    get_string('notificationaction','block_lp_course_progress')
                    )
                );
        
        $table->define_baseurl($CFG->wwwroot . '/blocks/lp_course_progress/email_notification/manage.php');
        $table->set_attribute('id', 'email_notification_schedule');
        $table->set_attribute('class', 'generaltable');
        $table->setup();

        
        foreach ($schedule as $row) {
            
            // format the dates
            $start = date('d/m/y',$row->startdate);
            $end = $row->enddate ? date('d/m/y',$row->enddate) : get_string('notificationnoend','block_lp_course_progress');
            $next = date('d/m/y',$row->next);
            
            // nice string for status
            $status = $row->status ? get_string('active') : get_string('inactive');
            
            $params = 'id='.$row->id;
            
            $editurl = $CFG->wwwroot.'/blocks/lp_course_progress/email_notification/edit.php';
            $actionlinks = html_writer::alist(
                        array(
                            html_writer::link(
                                $editurl.'?action=edit&'.$params,
                                html_writer::empty_tag(
                                    'img',
                                    array(
                                        'src'=>$OUTPUT->pix_url('icon_edit','block_lp_course_progress'),
                                        'alt'=>get_string('edit'),
                                        'title'=>get_string('editnotification','block_lp_course_progress')
                                        )
                                    )
                                ),
                            html_writer::link(
                                $editurl.'?action=delete&'.$params,
                                html_writer::empty_tag(
                                    'img',
                                    array(
                                        'src'=>$OUTPUT->pix_url('icon_delete','block_lp_course_progress'),
                                        'alt'=>get_string('delete'),
                                        'title'=>get_string('deletenotification','block_lp_course_progress')
                                        )
                                    )
                                ),
                        ),
                        array('class'=>'inline list')
                    );
            
            $table->add_data(
                    array(
                        $row->id,
                        $row->description,
                        $start,
                        $end,
                        $row->frequency,
                        $next,
                        $status,
                        $actionlinks
                    )
                );
        }
        
        $table->print_html();       
    }
    
    public function update_notification( $defaults ) {
        global $DB,$OUTPUT;
        
        $limitcourses = optional_param('courseradio',0,PARAM_INT);        
        $toggleend = optional_param('toggleend','',PARAM_ALPHA);
        
        if (!$limitcourses){
            $defaults['courses'] = '';
        } 
        
        if ($toggleend){
            $defaults['enddate'] =  0;
        }
        
        // format stuff
        $defaults['startdate'] = strtotime($defaults['startdate']);
        $defaults['enddate'] = strtotime($defaults['enddate']);        
        $defaults['next'] = time() + $this->calc_interval($defaults['frequency']);
        
        // cast the id to be sure
        $success = false;
        $defaults['id'] = intval($defaults['id']);
        if (empty($defaults['id'])){ // new record
            unset($defaults['id']);
            $success = $DB->insert_record('lp_incomplete_notification',$defaults,false);
        } else { // update the existing record
            $success = $DB->update_record('lp_incomplete_notification',$defaults);
        }
        
        if ($success){
            echo $OUTPUT->notification(get_string('successfulupdate','block_lp_course_progress'),'notifysuccess');
        } else {
            echo $OUTPUT->notification(get_string('failedupdate','block_lp_course_progress'));
        }
        
        echo html_writer::tag('div', $OUTPUT->continue_button(new moodle_url('/blocks/lp_course_progress/email_notification/manage.php')), array('class'=>'buttons'));
   }    
    
    public function delete_notification($defaults){
        global $DB,$OUTPUT;
        
        if ($DB->delete_records('lp_incomplete_notification',array('id'=>$defaults['id']))){
            echo $OUTPUT->notification(get_string('successfulupdate','block_lp_course_progress'),'notifysuccess');            
        } else {
            echo $OUTPUT->notification(get_string('failedupdate','block_lp_course_progress'));
        }
        
        echo html_writer::tag('div', $OUTPUT->continue_button(new moodle_url('/blocks/lp_course_progress/email_notification/manage.php')), array('class'=>'buttons'));
    }
    
    public function edit_notification_form(){
        global $DB;
        
        $action = required_param('action', PARAM_ALPHANUM);
        $confirmed = optional_param('confirmed',0,PARAM_INT);
        
        $defaults = array(
            'id'            => optional_param('id',0,PARAM_INT),
            'description'   => optional_param('description',get_string('defaultnotificationdesc','block_lp_course_progress'),PARAM_TEXT),
            'startdate'     => optional_param('startdate',date('d-m-Y'),PARAM_ALPHANUMEXT),
            'enddate'       => optional_param('enddate',get_string('notificationnoend','block_lp_course_progress'),PARAM_ALPHANUMEXT),
            'courses'       => optional_param('courses','',PARAM_TEXT),
            'message'       => optional_param('message',get_string('defaultnotificationmsg','block_lp_course_progress'),PARAM_TEXT),
            'frequency'     => optional_param('frequency',LP_NOTIFICATION_WEEKLY,PARAM_TEXT),
            'next'          => optional_param('status',0,PARAM_INT),
            'type'          => optional_param('status',1,PARAM_INT),
            'status'        => optional_param('status',1,PARAM_INT)
        );        
        
        // load item if it exists
        if (!$confirmed && $defaults['id']){
            $item = $DB->get_record('lp_incomplete_notification',array('id'=>$defaults['id']),implode(',',array_keys($defaults)));
            foreach ($defaults as $key=>$val){
                if (!isset($item->$key)){
                    continue;
                }
                if (in_array($key,array('startdate','enddate'))){
                    if (0 == $item->$key){
                        $item->$key = get_string('notificationnoend','block_lp_course_progress');
                    } else {
                        $item->$key = date('d-m-Y',$item->$key);
                    }
                }
                $defaults[$key] = $item->$key;
            }
        } 
                
        // perform the requested action
        switch ($action){
            case 'new':
            case 'edit':
                if (!$confirmed){
                    $this->print_notification_form($defaults,$action);
                } else {
                    $this->update_notification($defaults);
                }
                break;            
            case 'delete':
                if (!$confirmed){
                    $this->confirm_message(get_string('confirmnotificationdelete','block_lp_course_progress'), $defaults,$action);
                } else {
                    $this->delete_notification($defaults);
                }
                break;    
            default:
                break;
        }
        
    }
   
    public function confirm_message( $msg, $defaults, $action ){
        global $CFG, $OUTPUT;
        $output = array();
        $url = $CFG->wwwroot.'/blocks/lp_course_progress/email_notification/';
        
        $output []= $OUTPUT->box_start('generalbox', 'notice');
        $output []= html_writer::tag('p', $msg);
        
        $output []= html_writer::start_tag('form', array('id'=>'confirm_form','method'=>'post','action'=>$url.'edit.php'));
        
        $output []= html_writer::start_tag('fieldset');
        // relay all properties in hidden inputs
        foreach ($defaults as $key=>$value){
            $output []= html_writer::empty_tag('input',array('type'=>'hidden','id'=>$key,'name'=>$key,'value'=>$value));
        }
        $output []= html_writer::empty_tag('input',array('type'=>'hidden','id'=>'confirmed','name'=>'confirmed','value'=>1));
        $output []= html_writer::empty_tag('input',array('type'=>'hidden','id'=>'action','name'=>'action','value'=>$action));
        $output []= html_writer::end_tag('fieldset');
        
        $output []= html_writer::start_tag('fieldset');
        $output []= html_writer::empty_tag('input',array('type'=>'submit','id'=>'continue','name'=>'continue','value'=>get_string('continue')));
        $output []= html_writer::empty_tag('input',array('type'=>'button','id'=>'cancel','name'=>'cancel','onclick'=>"window.location='{$url}manage.php'",'value'=>get_string('cancel')));
        $output []= html_writer::end_tag('fieldset');
        
        $output []= html_writer::end_tag('form');
        
        $output []= $OUTPUT->box_end();
                
        echo implode('',$output);
    }
    
    public function print_notification_form( $defaults, $action ){
        global $OUTPUT,$CFG;
                
        $output = array();
        $url = $CFG->wwwroot.'/blocks/lp_course_progress/email_notification/';
        
        $output []= html_writer::start_tag('div', array('id'=>'notification'));
        $output []= html_writer::start_tag('form', array('id'=>'notification_form','method'=>'post','action'=>$url.'edit.php'));
        
        // description
        $output []= html_writer::start_tag('fieldset');
        $output []= html_writer::tag('label',get_string('notificationdesc','block_lp_course_progress'),array('for'=>'description'));
        $output []= html_writer::tag('textarea',$defaults['description'],array('id'=>'description','name'=>'description'));
        $output []= html_writer::end_tag('fieldset');
        
        // message
        $output []= html_writer::start_tag('fieldset');
        $output []= html_writer::tag('label',get_string('notificationmsg','block_lp_course_progress'),array('for'=>'message'));
        $output []= html_writer::tag('textarea',$defaults['message'],array('id'=>'message','name'=>'message'));
        $output []= html_writer::end_tag('fieldset');
        
        // status
        $output []= html_writer::start_tag('fieldset');
        $output []= html_writer::tag('label',get_string('notificationstatus','block_lp_course_progress'),array('for'=>'status'));
        $options = array(get_string('inactive'),get_string('active'));
        $output []= html_writer::select($options, 'status',$defaults['status'],false);
        $output []= html_writer::end_tag('fieldset');
        
        // courses
        $output []= html_writer::start_tag('fieldset');
        $output []= html_writer::tag('label',get_string('notificationlimitcourses','block_lp_course_progress'),array('for'=>'courseradio'));
        
        // radio for all
        $allcourses = empty($defaults['courses']);
        $attrs = array('type'=>'radio','name'=>'courseradio','value'=>0,'class'=>'limitcourse');
        if ($allcourses){
            $attrs['checked'] = 'checked';
        }
        $output []= html_writer::empty_tag('input',$attrs);        
        $output []= get_string('notificationcoursesall','block_lp_course_progress');
        
        // radio for selected
        $attrs['value'] = 1;
        if ($allcourses){
            unset($attrs['checked']);
        } else {
            $attrs['checked'] = 'checked';
        }
        $output []= html_writer::empty_tag('input',$attrs);
        $output []= get_string('notificationcourseslist','block_lp_course_progress');
        $output []= html_writer::end_tag('fieldset');
        
        // course selection thing
        $output []= $this->course_selection_widget($defaults['courses'],$allcourses);
                
        // frequency
        $output []= html_writer::start_tag('fieldset');
        $output []= html_writer::tag('label',get_string('notificationfreq','block_lp_course_progress'),array('for'=>'frequency'));
        $options = array(
            LP_NOTIFICATION_DAILY => LP_NOTIFICATION_DAILY,
            LP_NOTIFICATION_WEEKLY => LP_NOTIFICATION_WEEKLY,
            LP_NOTIFICATION_MONTHLY => LP_NOTIFICATION_MONTHLY
            
        );
        $output []= html_writer::select($options, 'frequency',$defaults['frequency'],false);
        $output []= html_writer::end_tag('fieldset');
                
        // start
        $output []= html_writer::start_tag('fieldset');
        $output []= html_writer::tag('label',get_string('notificationstart','block_lp_course_progress'),array('for'=>'startdate'));
        $output []= html_writer::empty_tag(
                'input',
                array(
                    'id'=>'startdate',
                    'name'=>'startdate',
                    'readonly'=>'readonly',
                    'type'=>'text',
                    'value'=> $defaults['startdate']
                    )
                );
        $output []= html_writer::tag(
                'button',
                html_writer::empty_tag(
                    'img',
                    array(
                        'src'=>$OUTPUT->pix_url('i/calendar'),
                        'alt'=>'calendar',
                        'title'=>get_string('calendarselectstart','block_lp_course_progress')
                    )
                ),
                array('type'=>'button','id'=>'btn_startdate','class'=>'calendar')
                );
        $output []= html_writer::end_tag('fieldset');
                
        // end
        $output []= html_writer::start_tag('fieldset');
        $output []= html_writer::tag('label',get_string('notificationend','block_lp_course_progress'),array('for'=>'enddate'));
        $noend = (0 == strtotime($defaults['enddate']));
        $output []= html_writer::checkbox('toggleend', null, $noend, get_string('noenddate','block_lp_course_progress'), array('id'=>'toggleend'));
        $output []= html_writer::empty_tag(
                'input',
                array(
                    'id'=>'enddate',
                    'name'=>'enddate',
                    'readonly'=>'readonly',
                    'type'=>'text',
                    'value'=> $defaults['enddate']
                    )
                );
        if ($noend){
            $noend = 'disabled';
        }
        $output []= html_writer::tag(
                'button',
                html_writer::empty_tag(
                    'img',
                    array(
                        'src'=>$OUTPUT->pix_url('i/calendar'),
                        'alt'=>'calendar',
                        'title'=>get_string('calendarselectend','block_lp_course_progress')
                    )
                ),
                array('type'=>'button','id'=>'btn_enddate',$noend=>$noend,'class'=>'calendar')
                );        
        $output []= html_writer::end_tag('fieldset');
        
        
        $output []= html_writer::start_tag('fieldset');
        $output []= html_writer::empty_tag('input',array('type'=>'hidden','id'=>'id','name'=>'id','value'=>$defaults['id']));
        $output []= html_writer::empty_tag('input',array('type'=>'hidden','id'=>'confirmed','name'=>'confirmed','value'=>1));
        $output []= html_writer::empty_tag('input',array('type'=>'hidden','id'=>'action','name'=>'action','value'=>$action));
        $output []= html_writer::empty_tag('input',array('type'=>'hidden','id'=>'courses','name'=>'courses','value'=>$defaults['courses']));
        $output []= html_writer::end_tag('fieldset');
        
        
        $output []= html_writer::start_tag('fieldset');
        $output []= html_writer::empty_tag('input',array('type'=>'button','id'=>'continue','name'=>'continue','onclick'=>'validateForm();','value'=>get_string('continue')));
        $output []= html_writer::empty_tag('input',array('type'=>'button','id'=>'cancel','name'=>'cancel','onclick'=>"window.location='{$url}manage.php'",'value'=>get_string('cancel')));
        $output []= html_writer::end_tag('fieldset');
        
        $output []= html_writer::end_tag('form');
        $output []= html_writer::end_tag('div');
        
        $output []= html_writer::tag('div',html_writer::tag('div','',array('id'=>'calendar')),array('id'=>'calendarpanel'));
        #$output []= html_writer::tag('div','',array('id'=>'calendarpanel'));
        
        echo implode('',$output);
    }
    
    public function course_selection_widget($selected,$hide){
        global $DB;
        
        $courses = $DB->get_records_select('course',' id <> ? ',array(SITEID),'id','id,fullname,shortname');   
        $selected = explode(',',$selected);
        $sel = array();
        $avail = array();
        
        foreach ($courses as $course){
            if (in_array($course->id,$selected)){
                $sel [$course->id]= "{$course->fullname} [{$course->shortname}]";
            } else {
                $avail [$course->id]= "{$course->fullname} [{$course->shortname}]";
            }
        }
        
        $output = array();
        $tableattrs = array('class'=>'generaltable','id'=>'course_select_table');
        if ($hide){
            $tableattrs['style'] = 'display: none;';
        }
        $output []= html_writer::start_tag('table',$tableattrs);
        
        $output []= html_writer::start_tag('tr');        
        $output []= html_writer::start_tag('td',array('align'=>'left'));
        $output []= html_writer::select($avail,'course_from','',false,array('id'=>'course_from','size'=>'15','multiple'=>'multiple','class'=>'moveselect'));
        $output []= html_writer::end_tag('td'); 
        $output []= html_writer::start_tag('td',array('align'=>'center'));
        $output []= html_writer::empty_tag('input',array('type'=>'button','value'=>get_string('add').' >>','class'=>'addcourse'));  
        $output []= html_writer::empty_tag('br');  
        $output []= html_writer::empty_tag('input',array('type'=>'button','value'=>get_string('remove').' <<','class'=>'removecourse'));        
        $output []= html_writer::end_tag('td');   
        $output []= html_writer::start_tag('td',array('align'=>'right'));
        $output []= html_writer::select($sel,'course_to','',false,array('id'=>'course_to','size'=>'15','multiple'=>'multiple','class'=>'moveselect'));
        $output []= html_writer::end_tag('td');
        $output []= html_writer::end_tag('tr');
        $output []= html_writer::start_tag('tr'); 
        $output []= html_writer::tag('td',get_string('courseselectinstr','block_lp_course_progress'),array('colspan'=>'3'));
        $output []= html_writer::end_tag('tr'); 
        
        $output []= html_writer::end_tag('table');
        
        return implode('',$output);
    }
    
    public function send_notifications(){
        global $DB,$CFG;
        
        $nummails = 0;
        $from = $CFG->noreplyaddress;
        if (empty($from)){
            $from = 'noreply@learningpool.com';
        }
        
        $curtime = time();
        $sql = 'SELECT id,message,courses,frequency,enddate FROM {lp_incomplete_notification} WHERE status <> 0 AND type=1 AND (enddate = 0 OR enddate > ?) AND next < ? ';
        $notifications = $DB->get_records_sql($sql,array($curtime,$curtime));
        foreach ($notifications as $n){
            // get users that have enrolled in specified/all courses
            $sql = "SELECT ra.id,ra.contextid,ra.userid,u.firstname,u.email,ctx.instanceid AS courseid,c.fullname 
                    FROM {role_assignments} ra 
                    LEFT JOIN {role} r 
                        ON r.id=ra.roleid 
                    LEFT JOIN {context} ctx
                        ON ctx.id=ra.contextid 
                    LEFT JOIN {course} c 
                        ON ctx.instanceid=c.id 
                    LEFT JOIN {user} u 
                        ON u.id=ra.userid 
                    WHERE 
                        ctx.contextlevel=50 
                    AND 
                        u.email <> ''  ";
            
            if (strlen($n->courses)){
                $sql .= " AND c.id IN (".$n->courses.") ";
            }
            
			$sql .= " ORDER BY userid ASC, fullname ASC; ";
            
            $records = $DB->get_records_sql($sql);
            //set up the variables for each user's cycle
            $currentUserID = NULL;
            $currentUserEmail = NULL;
            $strCourseList = "";
            $emailBody = "";
            foreach ($records as $rec){
                // most of this code taken from previous plugin
                if ($currentUserID == NULL || $currentUserID != $rec->userid) {
                    //new user! send existing email if exists and reset variables
                    if ($emailBody != "" && $strCourseList != "") {
                        $body = $emailBody . "\n" . $strCourseList;
                        if ($this->send_mail($body, $currentUserID, $currentUserEmail, $from)){
                            ++$nummails;
                        }

                        $currentUserID = NULL;
                        $currentUserEmail = NULL;
                        $strCourseList = "";
                        $emailBody = "";
                    }

                    //start new user if a valid email
                    if ($this->validate_email($rec->email)) {
                        $currentUserID = $rec->userid;
                        $currentUserEmail = $rec->email;
                        $strCourseList = "";
                        $emailBody = "Hi " . $rec->firstname . "\n\n" . $n->message . "\n\nYou are enrolled on, but have not completed the following courses:\n";
                    }
                }
                if ($currentUserID == $rec->userid) {
                    // Check this course progress
                    $stat = $this->check_course_progress($rec->userid, $rec->courseid, $rec->fullname);

                    if ('Complete' != $stat) {
                        $strCourseList .= $stat;
                    }
                }
            }//end $rec loop
            
            // we _will_ have an unsent email at end of loop, tidy up
            if ('' != $emailBody && '' != $strCourseList) {
                $body = $emailBody . "\n" . $strCourseList;
                if ($this->send_mail($body, $currentUserID, $currentUserEmail, $from)){
                    ++$nummails;
                }
            }
            
            
            // update the row with the next scheduled notification
            $next = $curtime + $this->calc_interval($n->frequency);
            $status = (0 != $n->enddate && $n->enddate < $next) ? 0 : 1;
            $DB->update_record('lp_incomplete_notification', array('id'=>$n->id, 'next'=>$next, 'status'=> $status));
            mtrace("Dispatched $nummails emails for notification {$n->id}. Status is now {$status} and next scheduled run is ".date('d/m/Y',$next));
        }
    }
    
    private function validate_email($addr) {
        $addr = trim($addr);
        
        if (empty($addr)){
            return false;
        }
        
        if (false === strpos($addr, "noreply") && false === strpos($addr, "notanemailladd")) {
            // Check form validity
            $reg = '/^([\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+\.)*[\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+@((((([a-z0-9]{1}[a-z0-9\-]{0,62}[a-z0-9]{1})|[a-z])\.)+[a-z]{2,6})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?)$/i';
            if (preg_match($reg, $addr)) {
                return true;
            } else {
                return false;
            }
        } 
        
        return false;
    }

    /**
     *
     * @global moodle_database $DB
     * @param type $user
     * @param type $course
     * @param type $coursename
     * @return string 
     */
    function check_course_progress($user, $course, $coursename) {
        global $DB;
        
        $body = "";
        $numSections = 0;
        $numIncomplete = 0;
        $notAttempted = true;
        $firstaccess = 0;
        $lastaccess = 0;
        $numComplete = 0;
        
        $course_obj = $DB->get_record('course',array('id'=>$course));
        
        // Now only runs on courses where completion is enabled.
        // See http://tech.learningpool.com/issues/5291 for details
        if($course_obj->enablecompletion) {
            $info = new completion_info($course_obj);            
            if ($info->is_course_complete($user)) {
                $body = 'Complete';                
            } else {
                $completions = $info->get_completions($user);
                
                foreach ($completions as $completion) {
                    $criteria = $completion->get_criteria();
                    if ($criteria->criteriatype != COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                        continue;
                    }
                    
                    ++$numSections;
                    $status = $criteria->get_status($completion);
                    if ('Yes' == $status) {
                        ++$numComplete;
                    }
                }
                
                $ts = intval($quiz_attempt->timestart);

                if ($firstaccess == 0 || $ts < $firstaccess) {
                    $firstaccess = $ts;
                }

                if ($lastaccess == 0 || $ts > $lastaccess) {
                    $lastaccess = $ts;
                }
                
                if ($numComplete == 0) {
                    $body .= "\nCourse name: " . $coursename . "\n";
                    $body .= "Number of Sections: " . $numSections . "\n";
                    $body .= "Status: Not Attempted\n";
                } else {
                    if ($numComplete > 0 && $numComplete < $numSections) {
                        $body .= "\nCourse name: " . $coursename . "\n";
                        $body .= "Number of Sections: " . $numSections . "\n";
                        $body .= "Number of Sections Completed: " . $numComplete . "\n";
                        #$body .= "Course Started: " . ($firstaccess == 0 ? "n/a" : date("d/m/Y", $firstaccess)) . "\n";
                        #$body .= "Course Last Accessed: " . ($lastaccess == 0 ? "n/a" : date("d/m/Y", $lastaccess)) . "\n";
                    } else {
                        $body = "Complete";
                    }
                }                
            }
        }

        return $body;
    }

    function send_mail($body, $userid, $to, $from) {
        global $DB;
        mtrace("Sending mail to $to");
        if ($this->validate_email($to)) {
            $subject = "Your Incomplete Courses";
            // $header = "From: $from";
            try {
                $user = $DB->get_record('user',array('id'=>$userid));
                email_to_user($user, $from, $subject, $body);
                // @mail($to, $subject, $body, $header);
            } catch (Exception $e){
                mtrace("sendmail - failed.");
            }
            return true;
        }
        return false;
    }
    
}
