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
 * @subpackage lp_enrolment_manager
 * @copyright  2011 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * RRenderer class for the lp_enrolment_manager plugin
 *
 * @copyright  2011 Learning Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_lp_enrolment_manager_renderer extends plugin_renderer_base {

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
        $output []= $this->heading(get_string('pluginname', 'local_lp_enrolment_manager'));
        $output []= $this->course_list();
        $output []= $this->panels();
        $output []= $this->email_editor();
        $output []= $this->enrol_summary();
        $output []= $this->footer();
        return implode(' ', $output);
    }
    
    public function js() {
        return html_writer::tag('script', 
            'var enrol_cfg = { email_enabled: '.(true ? 'true' : 'false').' };', // @TODO substitute $DLE[24][status] equivalent config
            array('type' => 'text/javascript'));
    }
    
    public function loading_tag() {
        return html_writer::tag('div', '', array('class' => 'loading'));
    }
    
    /**
     * renders a div to display our course tree 
     * @return string the markup for the div
     */
    public function course_list() {
        $output = array();
                
        $output []= html_writer::start_tag('div', array( 'id' => 'lpcourselist', 'class' => 'course-list panel' ));
        $output []= html_writer::tag('div', 
            html_writer::tag('h2', get_string('courselistheader', 'local_lp_enrolment_manager')),
            array('class' => 'header'));
        
        $output []= $this->course_tree(local_lp_getcoursetree());
        $output []= html_writer::end_tag('div');
        
        return implode(' ', $output);
    }   
    
    /**
     * Renders a collapsable tree of course leaves in category branches.
     * Clicking on a course will show the enrolled users and teachers (behavior dependant
     * on local/lp_enrolment_manager/yui.tree.js) 
     * @param hierarchy_tree $tree list of courses arranged by category
     * @return string output html
     */
    public function course_tree( hierarchy_tree $tree ) {
        global $OUTPUT;        
        $output = array();
        
        if (!(empty($tree) && empty($tree->nodes->children))) {
            $output []= html_writer::start_tag('div', array('id' => 'lpcoursetree', 'class' => 'hide'));
            $output []= $this->render_nodes($tree->nodes->children);
            $output []= html_writer::end_tag('div');   
        } else {
            $output []= $OUTPUT->error_text(get_string('nocoursesfound', 'local_lp_enrolment_manager'));
        }
                
        return implode(' ', $output);
    }
    
    /**
     * recursive function to render hierarchy_tree_node objects - branches and leaves
     * @param array $nodes - the children of the current node
     * @param array $atts - attributes of branch nodes
     * @return string markup for the node/nodelist
     */
    public function render_nodes ( $nodes, $atts = null) {
        $output = array();
     
        $output []= html_writer::start_tag('ul', $atts ? $atts : array());
        foreach ($nodes as $n){
            if ((isset($n->children) && count($n->children) > 0) || (!isset($n->category))){
                if (!isset($n->data)) {
                    $n->data = array('visible'=>false);
                }
                $output []= html_writer::start_tag('li', array('class'=> ($n->data['visible'] ? '' : 'hidden')));
                $output []= $n->name . ($n->data['visible'] ? '' : ' (hidden)');
                $output []= $this->render_nodes($n->children);
                $output []= html_writer::end_tag('li');   
            } else {
                $output []= html_writer::tag('li', html_writer::tag('div',
                        html_writer::tag('span',$n->name.($n->visible ? '' : ' (hidden)' ), array( 'id' => $n->category . '_' . $n->id )),
                        array('class' => 'lpcoursetreeleaf'.($n->visible ? '' : ' hidden'))));
            }
        }
        $output []= html_writer::end_tag('ul'); 
        return implode(' ', $output);
    } 
    
    /**
     * renders a div that will display information about users that are enrolled/can be enrolled
     * @return string markup for the div
     */
    public function panels() {
        $output =  array();
        
        $output []= html_writer::tag('div', get_string('getstarted','local_lp_enrolment_manager'), array( 'class' => 'right-panel init' ));   
        $output []= html_writer::start_tag('div', array( 'class' => 'right-panel data hide' ));
        
        $output []= html_writer::tag('ul',
            html_writer::tag('li', get_string('coursedescheader','local_lp_enrolment_manager'), array('id' => 'showdesc','name' => 'showdesc', 'class'=>'active')) .
            html_writer::tag('li', get_string('showenrolled','local_lp_enrolment_manager'), array('id' => 'showenrolled','name' => 'showenrolled')) .
            html_writer::tag('li', get_string('showadmins','local_lp_enrolment_manager'), array('id' => 'showadmins','name' => 'showadmins')) .
            html_writer::tag('li', get_string('enrolgroup','local_lp_enrolment_manager'), array('id' => 'enrolgroup','name' => 'enrolgroup')) .
            html_writer::tag('li', get_string('enrolindividual','local_lp_enrolment_manager'), array('id' => 'enrolindividual','name' => 'enrolindividual')),
            array('class'=>'tabs')
        );
        
        // form stuff        
        $output []= html_writer::start_tag('form', array( 'id' => 'enrolform', 'name' => 'enrolform', 'class' => 'enrolform' ));
        
        $output []= html_writer::tag('fieldset',
            #html_writer::tag('label', get_string('formsearchusers','local_lp_enrolment_manager'),array( 'for' => 'srch' )) .
            html_writer::tag('input', '', array( 'id' => 'srch', 'name' => 'srch', 'type' => 'text', 'placeholder'=>get_string('searchsubmit', 'local_lp_enrolment_manager'),'class'=>'' )) .
            html_writer::tag('input', '', array( 'id' => 'dosrch', 'name' => 'dosrch', 'type' => 'submit', 'value'=>'','title' => get_string('searchsubmit', 'local_lp_enrolment_manager'), 'class'=>'search' )).
            html_writer::tag('input', '', array( 'id' => 'clearsrch', 'name' => 'clearsrch', 'type' => 'button', 'title' => get_string('clearsearchinput','local_lp_enrolment_manager'), 'class'=>'reset')).
            html_writer::tag('input','', array('type'=>'hidden', 'id'=>'sesskey', 'name'=>'sesskey', 'value' => sesskey(), 'class'=>'')),
            array('id'=>'searchfields', 'class'=>'search')
        );
        $output []= html_writer::tag('div', '', array( 'id' => 'hiddenfields' ) );    
        
        $output []= html_writer::start_tag('div', array( 'id' => 'detailpanel', 'class'=>'panel'));           
        #$output []= html_writer::tag('div', '', array( 'id' => 'lpuserlist', 'class' => 'whitebg ygtv-checkbox' ));   
        #$output []= html_writer::tag('div', html_writer::tag('h2', get_string('userlistheader', 'local_lp_enrolment_manager')), array('class' => 'header'));
        $output []= html_writer::end_tag('div'); 
        
        // end form
        $output []= html_writer::end_tag('form');
        
        $output []= html_writer::end_tag('div');
        
        return implode(' ', $output);
    }

    /**
     * renders a div that will contain the email editor
     * @return string markup for the div
     */
    public function email_editor() {
        $output =  array();
        
        $output []= html_writer::start_tag('div', array( 'id' => 'lpemaildiv', 'class' => 'hide' ));
        $output []= html_writer::tag('div', get_string('emaileditheader', 'local_lp_enrolment_manager'), array('class'=>'hd'));
        
        $output []= html_writer::tag('div', '', array('id' => 'lpemaileditor','class'=>'bd'));
        $output []= html_writer::end_tag('div'); 
        
        return implode(' ', $output);
    }
    
    /**
     * renders a div that will contain the summary of the enrolment
     * @return string markup for the div
     */
    public function enrol_summary() {
        $output =  array();
        
        $output []= html_writer::start_tag('div', array( 'id' => 'lpsummarydiv', 'class' => 'hide' ));
        $output []= html_writer::tag('div', get_string('summaryheader', 'local_lp_enrolment_manager'), array('class'=>'hd'));
        
        $output []= html_writer::tag('div', '', array('id' => 'lpsummary','class'=>'bd'));
        $output []= html_writer::end_tag('div'); 
        
        return implode(' ', $output);
    }
    
    /**
     * Render the result page.
     * @param hierarchy_tree $coursetree list of courses arranged in categories
     * @return string html to output.
     */
    public function result_page() {
        $output =  array();
        $output []= $this->header();    
        $output []= $this->heading(get_string('enrolresultheader', 'local_lp_enrolment_manager'));
        $output []= $this->enrolment_result();
        $output []= $this->footer();
        return implode(' ', $output);
    }
    
    /**
     * renders a div that details the result of the enrolment
     * @return string markup for the div
     */
    public function enrolment_result() {
        $num_enrolled = optional_param('enrolled', 0, PARAM_INT);     
        $output =  array();   
        
        $output []= html_writer::start_tag('div', array('class'=>'generalbox'));
        $output []= $this->notification(str_replace('{num_users}',  $num_enrolled, get_string('numusersenrolled', 'local_lp_enrolment_manager')), 'notifysuccess');
        $output []= html_writer::tag('div', $this->continue_button(new moodle_url('/local/lp_enrolment_manager/index.php')), array('class'=>'buttons'));
        $output []= html_writer::end_tag('div');
        
        return implode(' ', $output);
    }
}
