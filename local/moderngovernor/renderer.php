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
 * @subpackage moderngovernor
 * @copyright  2012 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once( $CFG->dirroot.'/blocks/lp_hierarchy/lib.php' );
require_once( 'lib.php' );
require_once( 'formslib.php' );

/**
 * RRenderer class for the moderngovernor plugin
 *
 * @copyright  2012 Learning Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_moderngovernor_renderer extends plugin_renderer_base {

    private $CSS = array(
        
    );

    public function print_school_admin() {
        global $CFG, $DB, $OUTPUT, $PAGE;
        
        $columns = array(
            array('key'=>'id', 'label'=> '', 'sortable'=>false, 'hidden'=>true),
            array('key'=>'parentid', '', 'sortable'=>false, 'hidden'=>true),
            array('key'=>'checkbox', 'label'=> get_string('select','local_moderngovernor'), 'sortable'=>false),
            array('key'=>'school', 'label'=>get_string('schoolname','local_moderngovernor'), 'sortable'=>true),
            array('key'=>'lea', 'label'=>get_string('leaname','local_moderngovernor'), 'sortable'=>true),
            array('key'=>'status', 'label'=>get_string('status','local_moderngovernor'), 'sortable'=>true),
            array('key'=>'options', 'label'=>get_string('options'), 'sortable'=>false)
        );
        
        $perpage = 40;
                
        $ajaxconfig = array(
            'name' => 'local_moderngovernor_ajax',
            'fullpath' => '/local/moderngovernor/js/ajax.js',       
            'requires' => array(
                'node',
                'event',
                'selector-css3',
                'yui2-datatable', 
                'yui2-datasource',
                'yui2-paginator',
                'io',
                'json-encode',
                'json',
                'panel',
                'yui2-connection'
            ),
            'strings'=> array(
                array('noresults', 'local_moderngovernor'),
                array('xrecords', 'local_moderngovernor'),
                array('loading', 'local_moderngovernor'),
                array('noschoolsselected','local_moderngovernor'),
                array('combineschools','local_moderngovernor'),
                array('combinex','local_moderngovernor'),
                array('combine','local_moderngovernor'),
                array('confirmdisable','local_moderngovernor'),
                array('confirmenable','local_moderngovernor'),
                array('confirmcombine','local_moderngovernor'),
                array('notenoughschools','local_moderngovernor'),
                array('noschoolnamespecified','local_moderngovernor'),
                array('nonewleaspecified','local_moderngovernor')
            )
        );
          
        $PAGE->requires->js_init_call(
                'M.local_moderngovernor_ajax.init', 
                array(
                    'columns' => $columns, 
                    'perpage' => $perpage, 
                    'defaultSortColumn' => ''
                ),
                false,
                $ajaxconfig
                );
        
        echo $this->header();
        
        $sform = new moderngovernorschoolsearchform();
        $sform->display();
        
        $this->print_export_options($CFG->wwwroot.'/local/moderngovernor/report.php?type=school', 'Schools');
        
        echo html_writer::tag('div', get_string('loading','local_moderngovernor'), array('id'=>'schools-table'));        
        
        echo $this->footer();
                
        // capture combine form in a panel
        echo html_writer::start_tag('div', array('id' => 'combine-panel'));
        $aform = new moderngovernorschooladminform();
        $aform->display();
        echo html_writer::end_tag('div');
    }     
     
    public function print_user_admin() {
        global $CFG, $DB, $OUTPUT, $PAGE;
        
        $columns = array(
            array('key'=>'id', 'label'=> '', 'sortable'=>false, 'hidden'=>true),
            array('key'=>'username', 'label'=> get_string('username'), 'sortable'=>true),
            array('key'=>'email', 'label'=> get_string('email'), 'sortable'=>true),
            array('key'=>'school', 'label'=> get_string('schoolname', 'local_moderngovernor'), 'sortable'=>true),
            array('key'=>'lea', 'label'=> get_string('leaname', 'local_moderngovernor'), 'sortable'=>true),
            array('key'=>'status', 'label'=> get_string('status', 'local_moderngovernor'), 'sortable'=>true),
            array('key'=>'options', 'label'=>get_string('options'), 'sortable'=>false)
        );
        
        $perpage = 40;
                
        $ajaxconfig = array(
            'name' => 'local_moderngovernor_users',
            'fullpath' => '/local/moderngovernor/js/users.js',       
            'requires' => array(
                'node',
                'event',
                'selector-css3',
                'yui2-datatable', 
                'yui2-datasource',
                'yui2-paginator',
                'io',
                'json-encode',
                'json',
                'yui2-connection'
            ),
            
            'strings'=> array(
                array('noresults', 'local_moderngovernor'),
                array('xrecords', 'local_moderngovernor'),
                array('loading', 'local_moderngovernor'),
                array('confirmconfirm','local_moderngovernor'),
                array('confirmreset', 'local_moderngovernor')
            )
        );
          
        $PAGE->requires->js_init_call(
                'M.local_moderngovernor_users.init', 
                array(
                    'columns' => $columns, 
                    'perpage' => $perpage, 
                    'defaultSortColumn' => ''
                ),
                false,
                $ajaxconfig
                );
        
        echo $this->header();
        
        $sform = new moderngovernorusersearchform();
        $sform->display();
        
        $this->print_export_options($CFG->wwwroot.'/local/moderngovernor/report.php?type=user', 'Users');
        
        echo html_writer::tag('div', get_string('loading','local_moderngovernor'), array('id'=>'users-table'));        
        
        echo $this->footer();
    }         
       
     
    public function print_demo_account_admin() {
        global $CFG, $DB, $OUTPUT, $PAGE;
        
        echo $this->header();
        
        $sform = new moderngovernordemouserform();
        if ($sform->is_submitted() && $sform->is_validated()) {
            if ($sform->process()) {
                $this->print_back_button('/local/moderngovernor/addaccount.php', get_string('useraddedsuccessfully', 'local_moderngovernor'));
            }
        } else {
            $sform->display();
        }
        
        echo $this->footer();
    }         
    
    function print_export_options($link, $component, $return = false) {
        $output = array();
        $output [] = html_writer::start_tag('div');
        $output [] = html_writer::start_tag('div', array('class' => 'icon-container', 'id' => 'export'));

        $output [] = html_writer::tag('span', get_string('exportoptions', 'local_moderngovernor'), array('class' => 'options'));
        $output [] = html_writer::empty_tag('br');
        $icon = html_writer::tag('img', '', array('src' => $this->pix_url('icon_xls', 'local_moderngovernor')), get_string('downloadreport', 'local_moderngovernor', $component));
        $output [] = html_writer::link($link, $icon, array('title' => get_string('downloadreport', 'local_moderngovernor', $component)));

        $output [] = html_writer::end_tag('div');
        $output [] = html_writer::end_tag('div');
        $output [] = html_writer::empty_tag('br');

        $output = implode('', $output);
        if ($return) {
            return $output;
        }

        echo $output;
    }
    
    function print_back_button($url, $message, $return = false, $notify = 'notifysuccess') {
        $output = array();
        
        $output []= html_writer::start_tag('div', array('class'=>'generalbox'));
        $output []= $this->notification($message, $notify);
        $output []= html_writer::tag('div', $this->continue_button(new moodle_url($url)), array('class'=>'buttons'));
        $output []= html_writer::end_tag('div');
        
        $output = implode('', $output);
        if ($return) {
            return $output;
        }
        
        echo $output;
    }
}
