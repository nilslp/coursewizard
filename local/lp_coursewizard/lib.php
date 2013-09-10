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
 * Library of interface functions and constants for lp_webservices
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the spacedpractice specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    local
 * @subpackage lp_coursewizard
 * @copyright  2013 Declan McDonough/Tony Finlay
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
    
global $CFG;
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_self.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_date.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_unenrol.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_activity.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_duration.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_grade.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_role.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_course.php');
require_once $CFG->libdir.'/gradelib.php';
require_once($CFG->dirroot.'/course/completion_form.php');
require_once($CFG->dirroot.'/local/lp_enrolment_manager/locallib.php');
require_once($CFG->dirroot . "/course/modlib.php");

/**
 * This function loads the necessary javascript
 * @global type $CFG
 * @global type $USER
 * @global type $PAGE
 */
function local_lp_coursewizard_init(){
    global $CFG,$USER,$PAGE;
    $stage = optional_param('stage', 0, PARAM_INT);
    $id = optional_param('id', 0, PARAM_INT);
    $jsconfig = array(
        'name' => 'local_lp_coursewizard',
        'fullpath' => '/local/lp_coursewizard/coursewizard.js',
        'requires' => array('node','selector-css3','io','json-parse','json-stringify','widget')
    );

    $PAGE->requires->js_init_call('M.local_lp_coursewizard.init', array('sesskey'=>$USER->sesskey,'siteurl'=>$CFG->wwwroot,'courseId'=>$id), false, $jsconfig);
}

/**
  * Returns a button that will open or close the course wizard
  * @return string the button html
  */
function local_lp_coursewizard_button_html(){
    $url = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    $output = '';
    $output .= html_writer::start_tag('a', array('id' => 'wizard-toggle-button', 'class'=>''));
    if (false !== strpos($url,'/course/view.php')) {
        $output .= get_string('wizardbuttonedittext', 'local_lp_coursewizard');
    }else{
        $output .= get_string('wizardbuttontext', 'local_lp_coursewizard');
    }
    $output .= html_writer::end_tag('a');
    return $output;
}

/**
 * This will output the course wizard html
 */
function local_lp_coursewizard_html() {
    
    $url = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    
    $html = '';
    $saved_state = false;
    $coursewiz = false;
    $title = '';
    $desc = '';
    $summary = '';
   
    if (false !== strpos($url,'/course/view.php')) {
        //we are on a course page. 
        $courseid   = required_param('id', PARAM_INT);
        $coursewiz  = new lp_coursewizard_course($courseid);
        
        if($coursewiz){
            $title      = $coursewiz->get_course_name();
            $desc       = $coursewiz->get_course_shortname();
            $summary    = $coursewiz->get_course_desc();
        }
        
        $saved = optional_param('wizard', false, PARAM_BOOL);
        if($saved){
            $html = 'class="visible"';
        }
    }
?>
    <div id="wizard-wrapper" <?php echo $html;?>>

        <ul id="tabs-visibility-controls">
            <li id="hide"><span>Hide</span></li>
            <li id="close"><span>Close</span></li>
        </ul>

        <div id="tabs-container">
            <ul class="tabs steps">
                <?php if(!$coursewiz){?>
                <!--<li id="thetab1"><a href="#tab1"><span class="step-number"></span>Overview</a></li>-->
                <li id="tab1" class="selected"><a href="#tab1">Create a Course</a></li>
                <?php }else{ ?>
                    <li id="tab2" class="selected"><a href="#tab2"><span class="step-number">1</span>Add an Activity or Resource</a></li>
                    <li id="tab3"><a href="#tab3"><span class="step-number">2</span>Course Completion</a></li>
                    <li id="tab4"><a href="#tab4"><span class="step-number">3</span>Enrolment</a></li>
                    <li id="tab5"><a href="#tab5"><span class="step-number">4</span>Publish</a></li>
                <?php } ?>
                <!-- style="display:none;"-->
            </ul>
            <div class="tabs-content">
                <?php 
                //echo local_lp_coursewizard_overview_tab();
                if(!$coursewiz){
                    echo local_lp_coursewizard_createcourse_tab($title, $desc, $summary, $saved_state);
                }
                else{
                    //add activity tab
                    $section_select = $coursewiz->render_section_select();
                    $resource_table = $coursewiz->render_resource_table();
                    echo local_lp_coursewizard_addactivity_tab($section_select,$resource_table);
                    
                    //course completion tab
                    $completionagg = $coursewiz->render_completion_requirements();
                    $activitycompletion = $coursewiz->render_activity_completion_requirements();
                    echo local_lp_coursewizard_completion_tab($completionagg, $activitycompletion);
                    
                    $enrolledusers = $coursewiz->render_users_table(true);
                    $unenrolledusers = $coursewiz->render_users_table(false);
                    $cats = $coursewiz->render_publish_cats();
                    echo local_lp_coursewizard_enrolement_tab($enrolledusers['message'], $unenrolledusers['message']);
                    echo local_lp_coursewizard_publish_tab($cats);
                }
                ?>
            </div>
            <!----------------- End tabs content ------------------->
        </div>
        <!----------------- End tabs container ------------------->

    </div><!--End wizard wrapper-->
<?php
}

function local_lp_coursewizard_get_sections($courseid){
    global $CFG,$DB;
    $results = array();
    $sections = $DB->get_records_sql('SELECT * FROM {course_sections} WHERE course = ?', array($courseid));
    
    foreach ($sections as $sec){
        $results[$sec->section]['name'] = $sec->name;
        if ($sec->sequence && $sec->sequence!=''){
            $resources = explode(",",$sec->sequence);
            $mods = array();
            foreach ($resources as $rid){
                $resource = $DB->get_record_sql(
                            'SELECT {course_modules}.module, {course_modules}.instance, {course_modules}.section, {modules}.name
                             FROM {course_modules}
                             LEFT JOIN {modules} ON {course_modules}.module = {modules}.id
                             WHERE {course_modules}.id = ?',
                             array($rid));
                if($resource){
                    $instance = $DB->get_record_sql('Select * from '.$CFG->prefix . $resource->name.' WHERE id = ?',array($resource->instance));
                    if($instance){
                        $mod = array();
                        $mod['type'] = $resource->name;
                        $mod['name'] = $instance->name;
                        $mod['id']   = $rid;
                        $mods[] = $mod;
                    }
                }
            }
            $results[$sec->section]['mods'] = $mods;
        }
    }
    return $results;
}

/**
 * Tab HTML Output
 */

function local_lp_coursewizard_overview_tab(){
    $output = '';
    $output .= '
        <!----------------- Overview Tab -------------------->
        <div class="tab-page" id="tab1">
            
            <div class="region region-one first">
                 <h2>Overview</h2>
                 <ul>
                     <li>Create the course using an existing backup. If you don\'t have one, ask support@learningpool.com for help.</li>
                     <li>Ensure that your course is hidden and is located in the review area on your DLE.</li>
                     <li>Once the course is set up, we recommend you complete the following checks:</li>
                 </ul>
            </div>

            <div class="region region-two">
                <h3>Progress</h3>
                <table>
                    <colgroup id="1">
                        <col class="numbers" span="1">
                        <col class="content" span="7">
                    </colgroup>
                    <thead>
                        <tr>
                            <th></th>
                            <th>a</th>
                            <th>b</th>
                            <th>c</th>
                            <th>d</th>
                            <th>e</th>
                            <th>f</th>
                            <th>g</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="number">1</span></td>
                            <td>?</td>
                            <td>?</td>
                            <td>?</td>
                            <td>?</td>
                            <td>?</td>
                            <td>?</td>
                            <td>?</td>
                        </tr>
                        <tr>
                            <td><span class="number">2</span></td>
                            <td>?</td>
                            <td>?</td>
                            <td class="incomplete">X</td>
                            <td>?</td>
                            <td>?</td>
                            <td>?</td>
                            <td>?</td>
                        </tr>
                        <tr>
                            <td><span class="number">3</span></td>
                            <td>?</td>
                            <td class="incomplete">X</td>
                            <td>?</td>
                            <td>?</td>
                            <td>?</td>
                            <td>?</td>
                            <td>?</td>
                        </tr>
                        <tr>
                            <td><span class="number">4</span></td>
                            <td>?</td>
                            <td>?</td>
                            <td>?</td>
                            <td>?</td>
                            <td class="incomplete">X</td>
                            <td>?</td>
                            <td>?</td>
                        </tr>
                    </tbody>  
                </table>
            </div>

            <div class="region region-three">
                <h3>Icons Explained</h3>
                <ul>
                    <li><span class="incomplete">X</span>Step Incomplete</li>
                    <li><span class="complete">?</span>Step Complete</li>
                    <li><span class="attn-required">Attention Required</span></li>
                    <li><span class="unpublished">Unpublished</span></li>
                    <li><span class="published">Published</span></li>
                </ul>
            </div>

        </div>
        <!----------------- END Overview Tab -------------------->
    ';
    return $output;
}
function local_lp_coursewizard_createcourse_tab($title, $desc, $summary, $saved_state){
    global $CFG;
    
    $output = '';
    $output .= '
        <!----------------- Create a course tab ----------------->
        <div class="tab-page" id="tab1">

            <div class="region region-one first">
                <h2>'.get_string('createcourseheading','local_lp_coursewizard').'</h2>
                <p>'.get_string('createcoursedescription','local_lp_coursewizard').'</p>                        
            </div>

            <div class="region region-two">
                <div class="form-field">
                    <span class="form-step">A</span>
                    <a class="help">?<span>'.get_string('createcoursefulltitlehelp','local_lp_coursewizard').'</span></a>
                    <input type="text" id="full-course-title" value="'.$title.'" placeholder="Add a full title">
                </div>

                <div class="form-field">
                    <a class="help">?<span>'.get_string('createcourseshorttitlehelp','local_lp_coursewizard').'</span></a>
                    <span class="form-step">B</span>
                    <input type="text" id="short-course-title" value="'.$desc.'" placeholder="Add a short title">
                </div>
            </div>

            <div class="region region-three">
                <div class="form-field">
                    <a class="help">?<span>'.get_string('createcoursefulltitlehelp','local_lp_coursewizard').'</span></a>
                    <span class="form-step">C</span>
                    <textarea id="course-summary" placeholder="Add a short summary">'.$summary.'</textarea>
                </div> 
                <div class="form-field">
                    <span class="form-step">D</span>
                    <input type="button" class="submit ajax_starter_createcourse" id="create-course" value="'.get_string('createcoursebuttontext','local_lp_coursewizard').'">
                    <span class="hide ajax_message_createcourse"><img src="'.$CFG->wwwroot.'/pix/i/loading_small.gif"/>Creating course ...</span>
                    <span class="hide ajax_notification_createcourse"></span>
                </div>
            </div>

        </div>
        <!----------------- END Create a course tab ----------------->
    ';
    return $output;
}

function local_lp_coursewizard_addactivity_tab($section_select, $resource_table){
    global $CFG;
    
    $output = '';
    $output .= '
         <!----------------- Add and activity or resource tab ----------------->
        <div class="tab-page" id="tab2" role="tabpanel">

                <div class="region region-one first">
                    <h2>'.get_string('addresourceheading','local_lp_coursewizard').'</h2>
                    <p>'.get_string('addresourceselectintro','local_lp_coursewizard').$section_select.'</p>
                    
                    <ul role="tablist" class="tabs"> 
                        <li id="theTabScorm"><a>'.get_string('addresourcescormtab','local_lp_coursewizard').'</a></li>
                        <li id="theTabFile"><a>'.get_string('addresourcefiletab','local_lp_coursewizard').'</a></li>
                    </ul>
                    <div class="hide form-field ajax_starter_uploadscorm" id="scormuploadcontainer">
                        <a class="help">?<span>'.get_string('addresourcescormhelp','local_lp_coursewizard').'</span></a>
                        <span class="form-step">A</span>
                        <form id="upload_scorm" enctype="multipart/form-data" method="post">
                            <label for="upload-scorm">'.get_string('addresourcescormupload','local_lp_coursewizard').'</label>
                            <input class="ajax_starter_upload" id="upload-scorm" type="file" name="repo_upload_file" value="uploadSCORM">
                        </form>
                    </div>
                    <div class="hide form-field ajax_starter_uploadfile" id="fileuploadcontainer">
                        <a class="help">?<span>'.get_string('addresourcefilehelp','local_lp_coursewizard').'</span></a>
                        <span class="form-step">A</span>
                        <form id="upload_resource" enctype="multipart/form-data" method="post">
                            <label for="upload-resource">'.get_string('addresourcefileupload','local_lp_coursewizard').'</label>
                            <input class="" id="upload-resource" type="file" name="repo_upload_file" value="uploadFile"/>
                            <input name="" type="hidden"/>
                        </form>
                    </div>
                    <span class="hide ajax_message_upload"><img src="'.$CFG->wwwroot.'/pix/i/loading_small.gif"/>Uploading file...</span>
                    <span class="hide ajax_notification_upload"></span>
                </div>
                <!--End region 1-->

                <div class="region region-two" id="region-two">
                    <h3>Current Modules</h3>
                    '.$resource_table.'
                </div>
                <!--End region 2-->
                
                <div class="region region-three last">
                
                    <div id="scorm-content" class="hide">
                        <div class="sub-region sub-region-one">
                            <div class="form-field">
                                <h3>'.get_string('addresourcecompletionheading','local_lp_coursewizard').'</h3>
                                <div id="scorm-details-container" >
                                    <!--shit will be added here-->
                                </div>
                            </div>
                        </div>
                        <!--end scorm sub-region 1-->
                        
                        <div class="sub-region sub-region-two">
                            <div class="form-field">
                                <a class="help">?<span>'.get_string('addresourcecompletionhelp','local_lp_coursewizard').'</span></a>
                                <h3>'.get_string('addresourcecompletionheading','local_lp_coursewizard').'</h3>
                                <span class="form-step">D</span>
                                <div id="scorm-completion-container">
                                <!--shit will be added here-->
                                </div>
                            </div>
                            <div class="form-field">
                                <span class="form-step">E</span>
                                <input type="button" class="submit ajax_starter_saveresource btn_update_resource" id="update-scorm" value="Update SCORM">
                                <span class="hide ajax_message_saveresource"><img src="'.$CFG->wwwroot.'/pix/i/loading_small.gif"/>Saving...</span>
                                <span class="hide ajax_notification_saveresource"></span>
                            </div>
                        </div>
                        <!--end scorm sub-region 2-->
                        
                    </div>
                    <!-- end scorm content -->
                    
                    <div id="resource-content" class="hide">
						
                        <div id="resource-details-container" class="sub-region sub-region-one">
                            <!--shit will be added here-->
                        </div>
                        <!--end file sub-region 1-->
						
                        <div class="sub-region sub-region-two">
                            <div class="form-field">
                                <a class="help">?<span>'.get_string('addresourcefilehelp','local_lp_coursewizard').'</span></a>
                                <h3>'.get_string('addresourcedetailsheading','local_lp_coursewizard').'</h3>
                                <span class="form-step">D</span>
                                <div id="resource-completion-container">
                                <!--shit will be added here-->
                                </div>
                            </div>
                        </div>
                        <!--end file sub-region 2-->

                        <div class="sub-region sub-region-three last">
                            <div class="form-field">
                                <span class="form-step">E</span>
                                <input type="button" class="submit ajax_starter_saveresource btn_update_resource" id="update-resource" value="Update Resource">
                                <span class="hide ajax_message_saveresource"><img src="'.$CFG->wwwroot.'/pix/i/loading_small.gif"/>Saving...</span>
                                <span class="hide ajax_notification_saveresource"></span>
                            </div>
                        </div>
                        <!--end file sub-region 3-->
						
                    </div>
                    <!-- end file content -->
                    
                </div>
                <!--End region 3-->
                
            </div>
            <!----------------- END Add and activity or resource tab ----------------->
        
    ';
    return $output;
}
function local_lp_coursewizard_completion_tab($overallagg, $activitycompletion){
    $output = '';
    $output .= '
        <!----------------- Course completion tab ----------------->
        <div class="hide tab-page" id="tab3" role="tabpanel">
            <div class="region region-one first">
                <h2>'.get_string('coursecompletionheading','local_lp_coursewizard').'</h2>
                <p>'.get_string('coursecompletiondescription','local_lp_coursewizard').'</p>
                '.$overallagg.'
            </div>

            <div class="region region-two">
                <div class="form-field" id="activity-completion-container">
                    <a class="help">?<span>'.get_string('coursecompletiondescription','local_lp_coursewizard').'</span></a>
                    <h3>'.get_string('coursecompletionactivityheader','local_lp_coursewizard').'</h3>
                    <span class="form-step">A</span>
                    '.$activitycompletion.'
               </div>
            </div>

            <div class="region region-four last">
                <div class="form-field">
                   <span class="form-step">C</span>
                   <input type="button" class="submit ajax_starter_savecompletion" id="save-completion" value="Save Completion">
                   <span class="hide ajax_message_savecompletion"><img src="'.$CFG->wwwroot.'/pix/i/loading_small.gif"/>Saving...</span>
                   <span class="hide ajax_notification_savecompletion"></span>
                </div>
            </div>

        </div>
        <!----------------- END Course completion tab ----------------->
    ';
    return $output;
}

function local_lp_coursewizard_enrolement_tab($enrolledusers, $unenrolledusers){
    $output = '';
    $output .= '
        <!----------------- Enrolement tab ----------------->
        <div class="hide tab-page" id="tab4" role="tabpanel">

            <div class="region region-one first">
                <h2>'.get_string('enrolementheading','local_lp_coursewizard').'</h2>
                <p>'.get_string('enrolementdescription','local_lp_coursewizard').'</p>
            </div>

            <div class="region region-two">
                <h3>'.get_string('enrolementenrolledusers','local_lp_coursewizard').'</h3>
                <div id="enrolled-users-container">
                    '.$enrolledusers.'<!--This will contain the current users table-->
                </div>
            </div>

            <div class="region region-two last">
                <h3>'.get_string('enrolementunenrolledusers','local_lp_coursewizard').'</h3>
                <div id="unenrolled-users-container">
                    '.$unenrolledusers.'<!--This will contain the current users table-->
                </div>
            </div>

        </div>
        <!----------------- END Enrolement tab ----------------->
    ';
    return $output;
}
function local_lp_coursewizard_publish_tab($category_select=""){
    global $CFG;
    $output = '';
    $output .= '
        <!----------------- Publish tab ----------------->
        <div class="hide tab-page" id="tab5" role="tabpanel">

            <div class="region region-one first">
                <h2>'.get_string('publishheading','local_lp_coursewizard').'</h2>
                <p>'.get_string('publishdescription','local_lp_coursewizard').'</p>
            </div>

            <div class="region region-two">
                <div class="form-field">
                    <h3>Select Course category</h3>
                    '.$category_select.'
                </div>
            </div>

            <div class="region region-three last">
                <h3>Publish Course</h3>
                <div class="form-field">
                    <a href="#" id="publish-button" class="publish-button ajax_starter_publishcourse">Publish Course</a>
                    <span class="hide ajax_message_publishcourse"><img src="'.$CFG->wwwroot.'/pix/i/loading_small.gif"/>Publishing...</span>
                    <span class="hide ajax_notification_publishcourse"></span>
                   
                </div>
            </div>
        </div>
        <!----------------- END Publish tab ----------------->
    ';
    return $output;
}

class lp_coursewizard_course{
    
    private $course;
    private $completionrequirements;
    private $completioncriteria;
    private $activitycompletionrequirements;
    private $sections;
    private $completion;
    private $enrolement_manager;
    
    
    public function __construct($courseid){
        $this->set_course($courseid);
        $this->set_sections_with_modules();
        $this->set_completion();
        $this->set_completion_criteria();
        $this->set_completion_aggregation();
        $this->set_activity_completion_aggregation();
        $this->set_enrolement_manager();
    }
    public function get_course_name(){
        return $this->course->fullname;
    }
    public function get_course_shortname(){
        return $this->course->shortname;
    }
    public function get_course_desc(){
        return $this->course->summary;
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
    
    private function set_sections_with_modules(){
        global $CFG,$DB;
        $results = array();
        $sections = $DB->get_records_sql('SELECT * FROM {course_sections} WHERE course = ?', array($this->course->id));
        $info = get_fast_modinfo($this->course);
        foreach ($sections as $sec){
            $results[$sec->section]['name'] = $sec->name;
            if ($sec->sequence && $sec->sequence!=''){
                $resources = explode(",",$sec->sequence);
                $mods = array();
                foreach ($resources as $rid){
                    $resource = $DB->get_record_sql(
                                'SELECT {course_modules}.module, {course_modules}.instance, {course_modules}.section, {modules}.name
                                 FROM {course_modules}
                                 LEFT JOIN {modules} ON {course_modules}.module = {modules}.id
                                 WHERE {course_modules}.id = ?',
                                 array($rid));
                    if($resource){
                        $icon = $info->get_cm($rid)->get_icon_url()->out();
                        $instance = $DB->get_record_sql('Select * from '.$CFG->prefix . $resource->name.' WHERE id = ?',array($resource->instance));
                        if($instance){
                            $mod = array();
                            $mod['type'] = $resource->name;
                            $mod['name'] = $instance->name;
                            $mod['id']   = $rid;
                            $mod['icon'] = $icon;
                            $mods[] = $mod;
                        }
                    }
                }
                $results[$sec->section]['mods'] = $mods;
            }
        }
        $this->sections = $results;
    }
    private function set_completion(){
        $this->completion = new completion_info($this->course);
    }
    private function set_completion_criteria(){
        //we only want activities so we'll set COMPLETION_CRITERIA_TYPE_ACTIVITY
        $this->completioncriteria = $this->completion->get_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY);
    }
    private function set_completion_aggregation(){
        
        // Get array of all available aggregation methods.
        $aggregation_methods = $this->completion->get_aggregation_methods();

        // Map aggregation methods to context-sensitive human readable dropdown menu.
        $overallaggregationmenu = array();
        foreach ($aggregation_methods as $methodcode => $methodname) {
            if ($methodcode === COMPLETION_AGGREGATION_ALL) {
                $overallaggregationmenu[COMPLETION_AGGREGATION_ALL] = get_string('overallaggregation_all', 'core_completion');
            } else if ($methodcode === COMPLETION_AGGREGATION_ANY) {
                $overallaggregationmenu[COMPLETION_AGGREGATION_ANY] = get_string('overallaggregation_any', 'core_completion');
            } else {
                $overallaggregationmenu[$methodcode] = $methodname;
            }
        }
        $this->completionrequirements = $overallaggregationmenu;
    }
    private function set_activity_completion_aggregation(){
        
        // Get array of all available aggregation methods.
        $aggregation_methods = $this->completion->get_aggregation_methods();

        // Map aggregation methods to context-sensitive human readable dropdown menu.
        $activityaggregationmenu = array();
        foreach ($aggregation_methods as $methodcode => $methodname) {
            if ($methodcode === COMPLETION_AGGREGATION_ALL) {
                $activityaggregationmenu[COMPLETION_AGGREGATION_ALL] = get_string('activityaggregation_all', 'core_completion');
            } else if ($methodcode === COMPLETION_AGGREGATION_ANY) {
                $activityaggregationmenu[COMPLETION_AGGREGATION_ANY] = get_string('activityaggregation_any', 'core_completion');
            } else {
                $activityaggregationmenu[$methodcode] = $methodname;
            }
        }
        $this->activitycompletionrequirements = $activityaggregationmenu;
    }
    private function set_enrolement_manager(){
        $this->enrolement_manager = new local_lp_enrolment_manager_usermanager($this->course->id);
    }
    public function enrol_users($userids){
        return $this->enrolement_manager->enrolusers($userids);
    }
    public function render_users_table($enroled = true){
        if($enroled){
            $users = $this->enrolement_manager->getenrolled();
            $tableid = 'enrolledusertable';
            $button = '';
        }
        else{
            $users = $this->enrolement_manager->getindividuals();
            $buttontext = 'Enrol Selected';
            $buttonid = 'btn-enrol-users';
            $tableid = 'unenrolledusertable';
            $button = '<button id="'.$buttonid.'" type="button">'.$buttontext.'</button>';
        }
        
        if($users['success']){
            $output = '<table id="'.$tableid.'" class="generaltable">';
            $output .= '<thead>
                            <tr>
                                <th class="header"><input type="checkbox" name="chkallusr" id="chkallusr"></th>
                                <th class="header">Name</th>
                                <th class="header">'.$users['depttitle'].'</th>
                                <th class="header">Account Status</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                              <td></td>
                              <td></td>
                              <td></td>
                              <td></td>
                            </tr>
                        </tfoot>
                        <tbody>';
            if(empty($users['users'])){
                $output .= '<tr><td colspan="4">No users enrolled yet.</td></tr>';
            }
            else{
                foreach($users['users'] as $user){
                    $output .= '<tr>
                                    <td class="cell c0">
                                        <input type="checkbox" id="'.$user->id.'" class="chkusr">
                                    </td>
                                    <td class="cell c1">
                                        <span>
                                            <a href="/user/view.php?id='.$user->id.'">'.$user->fullname.'</a>
                                        </span>
                                    </td>
                                    <td class="cell c2">
                                        <span title="'.$user->fullname.'" class="meta">'.$user->hiername.'</span>
                                    </td>
                                    <td class="cell c3">
                                        <span title="Account Status" class="meta">'.$user->accountstatus.'</span>
                                    </td>
                                </tr>';
                }
            }
            $output .= '</tbody></table>';
            $output .= '<div class="form-field" id="enroll-users-btn">'.$button.'</div>';
            return array('success'=>true,'message'=>$output);
        }
        else{
            return array('success'=>false,'message'=>$users['message']);
        }
    }
    public function update_completion_requirements($data){
        
        // Delete old criteria.
        $this->completion->clear_criteria();

        // Loop through each criteria type and run its update_config() method.
        global $COMPLETION_CRITERIA_TYPES;
        foreach ($COMPLETION_CRITERIA_TYPES as $type) {
            $class = 'completion_criteria_'.$type;
            $criterion = new $class();
            $criterion->update_config($data);
        }

        // Handle overall aggregation.
        $aggdata = array(
            'course'        => $data->id,
            'criteriatype'  => null
        );
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod($data->overall_aggregation);
        $aggregation->save();

        // Handle activity aggregation.
        if (empty($data->activity_aggregation)) {
            $data->activity_aggregation = 0;
        }

        $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ACTIVITY;
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod($data->activity_aggregation);
        $aggregation->save();

        return true;
        
    }
    
    
    public function render_completion_requirements(){
        //as there is only activity compkletion in the wizard hide this
        $output = '<div class="hide form-field">';
        $output .= '<select name="overall_aggregation" id="id_overall_aggregation">';
        $selected = 'selected="selected"';
        foreach($this->completionrequirements as $key=>$val){
            $output .= '<option value="'.$key.'" '.$selected.'>'.$val.'</option>';
            $selected = '';
        }
        $output .= '</select>';
        $output .= '</div>';
        return $output;
    }
    
    public function render_activity_completion_requirements(){
        
        $output = '<div class="form-field">';
        $output .= '<select name="activity_aggregation" id="id_activity_aggregation">';
        $selected = 'selected="selected"';
        foreach($this->activitycompletionrequirements as $key=>$val){
            $output .= '<option value="'.$key.'" '.$selected.'>'.$val.'</option>';
            $selected = '';
        }
        $output .= '</select>';
        $output .= '</div>';
        
        
        $table = '<table id="resourcecompletiontable" class="generaltable">';
        $table .= '<tbody>';
        $modsfound = false;
        foreach ($this->sections as $key=>$val){
            if(isset($this->sections[$key]['mods'])){
                $modsfound = true;
                foreach($this->sections[$key]['mods'] as $mod){
                    $type = $mod['type'];
                    $name = $mod['name'];
                    $id = $mod['id'];
                    $checked = '';
                    foreach($this->completioncriteria as $crit){
                        if($crit->moduleinstance == $id){
                            $checked = 'checked="checked"';
                            break;
                        }
                    }
                    $table .= '<tr>
                                    <td><input class="'.$type.'" '.$checked.' type="checkbox" name="criteria_activity['.$id.']" value="checkbox" id="'.$id.'"></td>
                                    <td>'.$name.'</td>
                                </tr>';
                }
            }
        }
        if(!$modsfound){
            $table = '<tr><td colspan="3">No modules added yet.</td></tr>';
        }
        $table .= '</tbody></table>';
        return $output.$table;
    }
    
    public function render_section_select(){
        $output = '<select id="sectionSelect">';
        foreach($this->sections as $secid=>$secval){
            if($this->sections[$secid]['name']){
                $output .= '<option class="section-option" value="'.$secid.'">'.$this->sections[$secid]['name'].'</option>';
            }
        }
        $output .= '</select>';
        return $output;
    }
    public function render_resource_table($rowsonly=false){
        
        $tabletop = '<table id="currentresourcestable" class="generaltable">';
        $tabletop .= '<tbody>';
        $tablerows = '';
        if(!empty($this->sections)){
            $firstsection = true;
            foreach($this->sections as $secid=>$secval){
                if(isset($this->sections[$secid]['mods'])){
                    foreach($this->sections[$secid]['mods'] as $mod){
                        $style = $firstsection ? 'hide' : '';
                        $tablerows .= '<tr id="'.$mod['id'].'" class="section_'.$secid.' '.$mod['type'].' '.$style.'">
                                        <td class="td_icon"><img src="'.$mod['icon'].'" class="activityicon iconlarge"></td>
                                        <td class="mod_name">'.$mod['name'].'</td>
                                        <td><button id="'.$mod['id'].'" type="button" class="btn_edit_mod '.$mod['type'].' ajax_starter_editresource_'.$mod['id'].'">Edit</button>
                                            <span class="hide ajax_message_editresource_'.$mod['id'].'"><img src="'.$CFG->wwwroot.'/pix/i/loading_small.gif"/>Loading Resource...</span></td>
                                    </tr>';
                    }
                }
                $firstsection=false;
            }
        }
        else{
            $tablerows = '<tr><td colspan="3">No resources added yet.</td></tr>';
        }
        $tablebottom .= '</tbody></table>';
        if($rowsonly)
            return $tablerows;
        else
            return $tabletop . $tablerows . $tablebottom;
    }
    
    public function render_publish_cats(){
        global $DB;
        $output = '';
        $result = $DB->get_records_sql('SELECT * FROM {course_categories} WHERE name <> "LP Restore Course"');
        if($result){
            $output = '<select id="categorySelect">';
            foreach($result as $r){
                $output .= '<option class="category-option" value="'.$r->id.'">'.$r->name.'</option>';
            }
            $output .= '</select>';
        }
        return $output;
    }
    
    public function publish($catid){
        global $DB;
        $update = new stdClass();
        $update->id =               $this->course->id;
        $update->category =       $catid;
        if($DB->update_record('course', $update)){
            return array('success'=>true,'message'=>'Course Published');
        }
        else{
            return array('success'=>false,'message'=>'Unable to publish course');
        }
    }
}

class lp_coursewizard_module{
    
    private $module;
    private $modinfo;
    private $completion;
    private $type;
    
    public function __construct($moduleid,$courseid){
        $this->set_module($moduleid);
        $this->set_module_extras($courseid);
    }   
    
    private function set_module($moduleid){
        $this->module = get_coursemodule_from_id('', $moduleid, 0, true, MUST_EXIST);
        $this->type = $this->module->modname;
    }
    private function set_module_extras($courseid){
        global $DB,$CFG;
        $instance = $DB->get_record_sql('Select * from '.$CFG->prefix.$this->type.' WHERE id = ?',array($this->module->instance));
        if($instance){
            if($this->type == 'scorm'){
                $this->module->completionstatusrequired = $instance->completionstatusrequired;
                $this->module->completionscorerequired = $instance->completionscorerequired;
            }
            $this->module->description = $instance->intro;
        }
        $course = $DB->get_record( 'course', array( 'id' => $courseid ), '*' ); 
        $info = get_fast_modinfo($course);
        $this->modinfo = $info->get_cm($this->module->id);
    }
    public function get_module($field=false){
        if($field){
            if(isset($this->module->$field))
                return $this->module->$field;
            else
                return false;
        }
        return $this->module;
    }
    
    public function update_module($name, $description, $completion, $completionview, 
                                  $completionstatuspass = false, $completionstatuscomplete = false, 
                                  $completionscorerequired = false){
        
        global $DB, $CFG;
        $result = array();
        $result['success'] = false;
        //first update the file completion flags
        $update = new stdClass();
        $update->id =               $this->module->id;
        $update->completion =       $completion;
        $update->completionview =   $completionview;
        if($DB->update_record('course_modules', $update)){
            $update = new stdClass;
            $update->id = $this->module->instance;
            $update->name = $name;
            $update->intro = $description;
            if($this->type == 'scorm'){
                if(!$completionstatuspass && !$completionstatuscomplete){
                    $update->completionstatusrequired = null;
                }elseif($completionstatuspass && !$completionstatuscomplete){
                    $update->completionstatusrequired = 2;
                }elseif(!$completionstatuspass && $completionstatuscomplete){
                    $update->completionstatusrequired = 4;
                }elseif($completionstatuspass && $completionstatuscomplete){
                    $update->completionstatusrequired = 6;
                }

                if($completionscorerequired){
                    $update->completionscorerequired = $completionscorerequired;
                }
            }
            
            if($DB->update_record($this->type, $update)){
                $result['success'] = true;
                $result['name'] = $name;
                return $result;
            }else{
                $result['message'] = "Unable to update ".$this->type." table";
                return $result;
            }
        }
        else{
            $result['message'] = "Unable to update course modules table";
            return $result;
        }
    }
    
    public function render_details(){
        $output = '';
        if($this->module->modname == 'scorm'){
            $el = '<a class="help">?<span>This is the name of the SCORM</span></a>';
            $el .= '<span class="form-step">A</span>';
            $el .= '<input type="text" id="scorm-title" name="mod_name" value="'.$this->module->name.'">';
            $output .= $this->div_wrap_elements($el,'form-field');

            $el = '<a class="help">?<span>Create a short description detailing what the SCORM is about</span></a>';
            $el .= '<span class="form-step">B</span>';
            $el .= '<textarea id="scorm-summary" name="mod_desc">'.$this->module->description.'</textarea>';
            $output .= $this->div_wrap_elements($el,'form-field');
        }
        else if($this->module->modname == 'resource'){
            $el = '<a class="help">?<span>This is the name of the Resource</span></a>';
            $el .= '<span class="form-step">A</span>';
            $el .= '<input type="text" id="scorm-title" name="mod_name" value="'.$this->module->name.'">';
            $output .= $this->div_wrap_elements($el,'form-field');

            $el = '<a class="help">?<span>Create a short description detailing what the Resource is</span></a>';
            $el .= '<span class="form-step">B</span>';
            $el .= '<textarea id="scorm-summary" name="mod_desc">'.$this->module->description.'</textarea>';
            $output .= $this->div_wrap_elements($el,'form-field');
        }
        
        return $output;
    }
    
    public function render_completion_options(){
        $output = '';
        //activity completion dropdown
        $completion_vals = array(COMPLETION_TRACKING_NONE=>get_string('completion_none', 'completion'),
                                 COMPLETION_TRACKING_MANUAL=>get_string('completion_manual', 'completion'),
                                 COMPLETION_TRACKING_AUTOMATIC=>get_string('completion_automatic', 'completion'));
        $el = '<select class="sel_completion" name="completion">';
        foreach($completion_vals as $key=>$val){
            $el .= ($key == $this->module->completion) ? '<option value="'.$key.'" selected="selected">' : '<option value="'.$key.'">';
            $el .= $val;
            $el .= '</option>';
        }
        $el .= '</select>';
        $output .= $this->div_wrap_elements($el,'form-field');
        
        //disable all inputs unless COMPLETION_TRACKING_AUTOMATIC is set
        $disabled = $this->module->completion == COMPLETION_TRACKING_AUTOMATIC ? '' : 'disabled="disabled"';
        
        //all mods have require view option
        $checked = '';
        if($this->module->completionview)
            $checked = 'checked="checked"';
        $el = '<label><input type="checkbox" '.$disabled.' '.$checked.' name="completionview">' . get_string('completionview', 'completion');
        $el .= '</input></label>';
        $output .= $this->div_wrap_elements($el,'form-field');
        
        //scorm specific fields
        if($this->module->modname == 'scorm'){
            $passchecked = '';
            $completedchecked = '';
            $minscore = empty($this->module->completionscorerequired) ? '' : $this->module->completionscorerequired;
            if($this->module->completionstatusrequired == 2){
                $passchecked = 'checked="checked"';
            }else if($this->module->completionstatusrequired == 4){
                $completedchecked = 'checked="checked"';
            }else if($this->module->completionstatusrequired == 6){
                $passchecked = 'checked="checked"';
                $completedchecked = 'checked="checked"';
            }
            $el = '<label><input type="checkbox" name="completionstatuspass" '.$disabled.' '.$passchecked.'>'.get_string('completionstatus_passed', 'scorm').'</input></label>';
            $el .= '<label><input type="checkbox" name="completionstatuscomplete" '.$disabled.' '.$completedchecked.'>'.get_string('completionstatus_completed', 'scorm').'</input></label>';
            $output .= $this->div_wrap_elements($el,'form-field');
            
            $el = '<label><input type="text" value="'.$minscore.'" name="completionscorerequired" '.$disabled.'>'.get_string('completionscorerequired', 'scorm').'</input></label>';
            $output .= $this->div_wrap_elements($el,'form-field');
        }    
        return $output;
    } 
    private function div_wrap_elements($html,$class){
        $output = '<div class="'.$class.'">';
        $output .= $html;
        $output .= '</div>';
        return $output;
    }
}
