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
 * Renderer class for this plugin
 *
 * @package    local
 * @subpackage lp_courseaccessmanager
 * @copyright  2012 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * RRenderer class for the lp_courseaccessmanager plugin
 *
 * @copyright  2012 Learning Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_lp_courseaccessmanager_renderer extends plugin_renderer_base {

    private $CSS = array(
        'course_list' => 'lpcourselist',
    );

    /**
     * Render the index page.
     * @param hierarchy_tree $coursetree list of courses arranged in categories
     * @return string html to output.
     */
    public function index_page() {
        $output =  array();
        $output []= $this->header();    
        $output []= $this->js();   
        $output []= $this->heading(get_string('pluginname', 'local_lp_courseaccessmanager'));
        $output []= $this->rule_list();
        $output []= $this->access_form();
        $output []= $this->courses();
        $output []= $this->users();
        $output []= $this->footer();
        return implode(' ', $output);
    }
    
    public function js() {
        return html_writer::tag('script', 
            '// global vars here?',
            array('type' => 'text/javascript'));
    }
    
    public function loading_tag() {
        return html_writer::tag('div', '', array('class' => 'loading'));
    }
    
      /**
     * renders a div to display our current rules
     * @return string the markup for the div
     */
    public function rule_list() {
        $output = array();
                
        $output []= html_writer::start_tag('div', array('class' => 'current-rules panel'));
        $output []= html_writer::tag('h3', get_string('rulelistheader', 'local_lp_courseaccessmanager'));
        $output []= html_writer::tag('div','',array('class'=>'container', 'id'=>'rulelist'));
        $output []= html_writer::tag('div',
                html_writer::tag(
                    'button',
                    get_string('btnnewrule','local_lp_courseaccessmanager'),
                    array('id'=>'btn_newrule','class'=>'new', 'disabled'=>'disabled')
                ),
                array('class'=>'new')
            );
        $output []= html_writer::end_tag('div');
       
        return implode(' ', $output);
    }  
    
    /**
     * renders a form for search etc
     * @return string the markup for the div
     */
    public function access_form() {
        $output = array();
        
        $output []= html_writer::start_tag('form', array('class' => 'form hide','id'=>'accessform'));
        $output []= html_writer::tag('input','', array('type'=>'hidden', 'id'=>'sesskey', 'name'=>'sesskey', 'value' => sesskey()));
        $output []= html_writer::tag('input','', array('type'=>'hidden', 'id'=>'rule_name', 'name'=>'rule_name', 'value' => ''));
        $output []= html_writer::tag('input','', array('type'=>'hidden', 'id'=>'hierids', 'name'=>'hierids', 'value' => ''));
        $output []= html_writer::tag('input','', array('type'=>'hidden', 'id'=>'courseids', 'name'=>'courseids', 'value' => ''));
        $output []= html_writer::tag('input','', array('type'=>'hidden', 'id'=>'userids', 'name'=>'userids', 'value' => ''));
        $output []= html_writer::end_tag('form');
        
        return implode(' ', $output);
    }
    
    /**
     * renders a div to display our courses
     * @return string the markup for the div
     */
    public function courses() {
        $output = array();
        
        $output []= html_writer::start_tag('div', array('class' => 'course-list panel hide'));
        $output []= html_writer::tag('h3', get_string('courselistheader', 'local_lp_courseaccessmanager'));
        $output []= html_writer::tag('div','',array('class' => 'whitebg', 'id'=>'coursetree'));
        $output []= html_writer::end_tag('div');
        
        return implode(' ', $output);
    }   
    
    /**
     * renders a div to display our courses
     * @return string the markup for the div
     */
    public function users() {
        $output = array();
        
        $output []= html_writer::start_tag('div', array('class' => 'students panel hide'));
        $output []= html_writer::tag('h3', get_string('tabhierarchy', 'local_lp_courseaccessmanager'));
        /*$output []= html_writer::tag(
                'ul',
                html_writer::tag('li',get_string('tabhierarchy','local_lp_courseaccessmanager'),array('class'=>'active','id'=>'tab_hierarchy')) . 
                html_writer::tag('li',get_string('tabindividual','local_lp_courseaccessmanager'),array('id'=>'tab_individual')),
                array('class'=>'tabs')
                );*/
        $output []= html_writer::tag('div',html_writer::tag('div','',array('id'=>'hiertree','class' => 'whitebg')),array('class'=>'hierarchy panel', 'id'=>'panel_hierarchy'));
        #$output []= html_writer::tag('div',html_writer::tag('div','',array('id'=>'userlist')),array('class'=>'individual panel', 'id'=>'panel_individual'));
        $output []= html_writer::end_tag('div');
        
        return implode(' ', $output);
    }   
    
    public function enrolled_user_form() {
        $output = array();
        
        $output []= html_writer::start_tag('form', array( 'id' => 'grantform', 'name' => 'grantform', 'class' => 'grantform' ));
        $output []= html_writer::tag('div',html_writer::tag('input', '', array('id' => 'grantgroup','name' => 'grantgroup','type' => 'button','value' => get_string('grantgroup','local_lp_courseaccessmanager') )) .
            html_writer::tag('input', '', array('id' => 'grantindividual','name' => 'grantindividual','type' => 'button','value' => get_string('grantindividual','local_lp_courseaccessmanager') ))
        );
        $output []= html_writer::tag('div',
            html_writer::tag('label', get_string('formsearchusers','local_lp_courseaccessmanager')) .
            html_writer::tag('input', '', array( 'id' => 'srch', 'name' => 'srch', 'type' => 'text' )) .
            html_writer::tag('input', '', array( 'id' => 'dosrch', 'name' => 'dosrch', 'type' => 'submit', 'value' => get_string('searchsubmit', 'local_lp_courseaccessmanager') )).
            html_writer::tag('input', '', array( 'id' => 'clearsrch', 'name' => 'clearsrch', 'type' => 'button', 'value' => get_string('clearsearchinput','local_lp_courseaccessmanager') )),
            array( 'for' => 'srch' )
        );
        $output []= html_writer::tag('div', '', array( 'id' => 'hiddenfields' ) );    
        $output []= html_writer::tag('div', html_writer::tag('input','', array('type'=>'hidden', 'id'=>'sesskey', 'name'=>'sesskey', 'value' => sesskey())) );      
        $output []= html_writer::tag('div', '', array( 'id' => 'lpuserstats' ));    
        $output []= $this->loading_tag();    
        $output []= html_writer::tag('div', '', array( 'id' => 'lpuserlist', 'class' => 'whitebg ygtv-checkbox' ));      
        $output []= html_writer::end_tag('form');
        
        return implode(' ', $output);
    }
    
    /**
     * Render the result page.
     * @return string html to output.
     */
    public function result_page() {
        $output =  array();
        $output []= $this->header();    
        $output []= $this->heading(get_string('grantresultheader', 'local_lp_courseaccessmanager'));
        $output []= $this->access_result();
        $output []= $this->footer();
        return implode(' ', $output);
    }
    
    /**
     * renders a div that details the result of the access modification
     * @return string markup for the div
     */
    public function access_result() {
        $num_granted = optional_param('granted', 0, PARAM_INT);     
        $output =  array();   
        
        $output []= html_writer::start_tag('div', array('class'=>'generalbox'));
        $output []= $this->notification(str_replace('{num_users}',  $num_enrolled, get_string('numusersenrolled', 'local_lp_courseaccessmanager')), 'notifysuccess');
        $output []= html_writer::tag('div', $this->continue_button(new moodle_url('/local/lp_courseaccessmanager/index.php')), array('class'=>'buttons'));
        $output []= html_writer::end_tag('div');
        
        return implode(' ', $output);
    }
    
    public function access_denied() {
        global $OUTPUT,$CFG;
        
        $output = array();
        $output []= $OUTPUT->header();
        $output []= $OUTPUT->heading(get_string('accessdenied','local_lp_courseaccessmanager'));
        $output []= $OUTPUT->box(get_string('accessdeniedmessage','local_lp_courseaccessmanager'), 'generalbox', 'notice');
        $output []= $OUTPUT->continue_button($CFG->wwwroot);
        $output []= $OUTPUT->footer();
        
        return implode(' ', $output);
    }
}
