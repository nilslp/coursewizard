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
 * @subpackage welcome_email
 * @copyright  2012 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once( 'lib.php' );
require_once( 'formslib.php' );

/**
 * Renderer class for the welcome_email plugin
 *
 * @copyright  2012 Learning Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_welcome_email_renderer extends plugin_renderer_base {

    private $CSS = array(
        
    );

    public function print_user_table() {
        global $CFG, $DB, $OUTPUT, $PAGE;
        
        $resetstatus = optional_param('resetstatus', 1, PARAM_INT);
        $resetlist = optional_param('resetlist', '', PARAM_SEQUENCE);
        $isadmin = has_capability('moodle/site:config', context_system::instance());
        
        $columns = array(
            array('key'=>'id', 'label'=> '', 'sortable'=>false, 'hidden'=>true),
            array('key'=>'checkbox', 'label'=> '', 'sortable'=>false, 'hidden' => !$isadmin),
            array('key'=>'fullname', 'label'=> get_string('fullname'), 'sortable'=>true),
            array('key'=>'email', 'label'=> get_string('email'), 'sortable'=>true),
            array('key'=>'emailstop', 'label'=> get_string('emailstop','local_welcome_email'), 'sortable'=>true),
            array('key'=>'hasloggedin', 'label'=> get_string('hasloggedin','local_welcome_email'), 'sortable'=>true),
            array('key'=>'deleted', 'label'=> get_string('deleted'), 'sortable'=>true),
            array('key'=>'timecreated', 'label'=> get_string('timecreated','local_welcome_email'), 'sortable'=>true),
            array('key'=>'status', 'label'=>get_string('status','local_welcome_email'), 'sortable'=>true),
            array('key'=>'timesent', 'label'=>get_string('timesent','local_welcome_email'), 'sortable'=>true)
        );
        
        $perpage = 40;
                
        $ajaxconfig = array(
            'name' => 'local_welcome_email_ajax',
            'fullpath' => '/local/welcome_email/js/ajax.js',       
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
                'panel'
            ),
            'strings'=> array(
                array('noresults', 'local_welcome_email'),
                array('xrecords', 'local_welcome_email'),
                array('loading', 'local_welcome_email'),
                array('withxselecteduserssetstatus', 'local_welcome_email')
            )
        );
          
        $PAGE->requires->js_init_call(
                'M.local_welcome_email_ajax.init', 
                array(
                    'columns' => $columns, 
                    'perpage' => $perpage, 
                    'defaultSortColumn' => ''
                ),
                false,
                $ajaxconfig
                );
        
        echo $this->header();
        
        $sform = new welcomeemailsearchform();
        $sform->display();
        
        // process form
        if ($sform->is_submitted() && $sform->process()) {
            echo $OUTPUT->notification(get_string('settingsupdated', 'local_welcome_email'), 'notifysuccess');
        }       
        
        // process select
        if (!empty($resetlist) && local_welcome_email_reset_status(explode(',', $resetlist), $resetstatus)) {
            echo $OUTPUT->notification(get_string('settingsupdated', 'local_welcome_email'), 'notifysuccess');            
        }
        
        echo html_writer::tag('div', get_string('loading','local_welcome_email'), array('id'=>'users-table'));        
        
        if ($isadmin) {
            $setstatusoptions = array(                
                    get_string('status:notsent','local_welcome_email'),
                    get_string('status:archived','local_welcome_email')
                );
            $select = new single_select(new moodle_url('index.php'), 'resetstatus', $setstatusoptions);
            $select->class = 'resetstatus';
            $select->method = 'post';
            $select->set_label(get_string('withxselecteduserssetstatus', 'local_welcome_email', 0));
            echo $this->render($select);
        }
        
        echo $this->footer();
    }     
     
}
