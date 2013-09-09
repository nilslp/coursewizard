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
 * @subpackage dlelegacytools
 * @copyright  2012 Learning Pool
 * @author     Dennis Heaney 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/mod/scorm/locallib.php');
#include_once($CFG->dirroot.'/local/learningpool/utils.php');

/**
 * Scorm settings form class
 */
class local_dlt_scormsettingsform extends moodleform {
    function definition() {
        global $CFG;
        
        $mform = &$this->_form;

        $mform->addElement('header', 'settings', get_string('scormsettings', 'local_dlelegacytools'));
        
        $mform->addElement(
                'select',
                'scormpopup',
                get_string('scormpopup', 'local_dlelegacytools'),
                array(
                    'show'=>get_string('show', 'local_dlelegacytools'),
                    'hide'=>get_string('hide', 'local_dlelegacytools')
                ));

        $popup = get_config('local/dlelegacytools', 'scormpopup');
        if ($popup) {
            $mform->setDefault('scormpopup', $popup);
        }
        
        $mform->addElement(
                'select',
                'scormscoremessage',
                get_string('scormscoremsg', 'local_dlelegacytools'),
                array(
                    'noscore'=>get_string('noscore', 'local_dlelegacytools'),
                    'scoreonly'=>get_string('scoreonly', 'local_dlelegacytools'),
                    'scoreandmax'=>get_string('scoreandmax', 'local_dlelegacytools')
                ));
        $scoremsg = get_config('local/dlelegacytools', 'scormscoremessage');
        if ($scoremsg) {
            $mform->setDefault('scormscoremessage', $scoremsg);
        }
        
        $mform->addElement(
                'select',
                'scormredirect',
                get_string('scormredirect', 'local_dlelegacytools'),
                array(
                    'off'=>get_string('off', 'local_dlelegacytools'),
                    'homepage'=>get_string('homepage', 'local_dlelegacytools'),
                    'coursetopic'=>get_string('coursetopic', 'local_dlelegacytools'),
                    'coursecatlist'=>get_string('coursecatlist', 'local_dlelegacytools'),
                    'coursecatpage'=>get_string('coursecatpage', 'local_dlelegacytools')
                ));
        $scormredirect = get_config('local/dlelegacytools','scormredirect');
        if ($scormredirect) {
            $mform->setDefault('scormredirect', $scormredirect);
        }
        
        $mform->addElement(
                'select',
                'scormhideenterbtn',
                get_string('scormhideenterbtn', 'local_dlelegacytools'),
                array(
                    'off'=>get_string('off', 'local_dlelegacytools'),
                    'on'=>get_string('on', 'local_dlelegacytools')
                ));
        $hideenterbtn   = get_config('local/dlelegacytools', 'scormhideenterbtn');
        if ($hideenterbtn) {
            $mform->setDefault('scormhideenterbtn', $hideenterbtn);
        }
        
        $this->add_action_buttons();
        
    }
    
    function process(){     
        global $OUTPUT; 
        $formdata = $this->get_data();
        
        if (!$formdata){
            return;
        }
        
        if(empty($formdata->submitbutton)) {
            echo $OUTPUT->notification(get_string('update_fail','local_dlelegacytools'), 'notifyfailure');
            return;
        }        
        
        $success = false;         
        if (isset($formdata->scormpopup)) {
            $success = set_config('scormpopup', $formdata->scormpopup, 'local/dlelegacytools');
        }
        
        if (isset($formdata->scormscoremessage)) {
            $success = set_config('scormscoremessage', $formdata->scormscoremessage, 'local/dlelegacytools');
        }
        
        if (isset($formdata->scormredirect)){
            $success = set_config('scormredirect', $formdata->scormredirect, 'local/dlelegacytools');
        }
        
        if (isset($formdata->scormhideenterbtn)){
            $success = set_config('scormhideenterbtn', $formdata->scormhideenterbtn, 'local/dlelegacytools');
        }
        
        if ($success){
            echo $OUTPUT->notification(get_string('update_success','local_dlelegacytools'), 'notifysuccess');            
        } else {
            echo $OUTPUT->notification(get_string('update_fail','local_dlelegacytools'), 'notifysuccess');
        }
    }

}

function configure_scorm_behavior(){
    global $PAGE,$DB,$USER;
    
    if (!isset($PAGE) || !$PAGE->has_set_url()){
        // some core moodle stuff doesn't follow the rules :-O
        return;
    }
    
    if (false !== strpos($PAGE->url,'/mod/scorm/')){
        // have to double check the scorm itself :-/
        $params = array();
        if (false !== strpos($PAGE->url,'/mod/scorm/view.php')){
            $id = optional_param('id', 0, PARAM_INT);
        } else {
            $id = optional_param('cm', 0, PARAM_INT);
        }
        $a = optional_param('a', 0, PARAM_INT);  
        $display = optional_param('display', '', PARAM_ALPHA);  
        $scoid = optional_param('scoid', 0, PARAM_INT);  
        $scorm = false;
        
        if (!empty($id)){
            $cm = get_coursemodule_from_id('scorm', $id);
            $scorm = $DB->get_record("scorm", array("id"=>$cm->instance));
        } else if (!empty($a)) {
            $scorm = $DB->get_record("scorm", array("id"=>$a));
        }
        
        if (!empty($scorm)){
            $params []= ($display == 'popup' ? 'popup=1' : 'popup=0');
            $params []= "course=".$scorm->course;
            $params []= 'scormtitle='.$scorm->name; 
            
            // send the name for the scorm window
            $name = preg_replace("/[^A-Za-z0-9]/", "", $scorm->name);
            if (!$name) {
                $name = 'DefaultPlayerWindow';
            }
            $name = 'scorm_'.$name;
            $params []= 'obj='.$name;     
            
            if (empty($scoid)){ // chance to save ...
                try {
                    $scoid = $DB->get_field('scorm_scoes','id',array('scorm'=>$scorm->id, 'scormtype'=>'sco'),MUST_EXIST);      
                } catch (Exception $e){
                    // do nothing!
                }
            }
            
            if ($scoid){
                // user tracking
                $attempt = scorm_get_attempt_count($USER->id, $scorm);
                $usertrack = (array)scorm_get_tracks($scoid, $USER->id, $attempt);
                if ($usertrack && !empty($usertrack['cmi.core.score.max'])){
                    $params []= 'score='.$usertrack['cmi.core.score.raw'];            
                    $params []= 'scoremax='.$usertrack['cmi.core.score.max'];            
                }
            }
            $params []= 'toc='.$scorm->displaycoursestructure;
        }
        
        $PAGE->requires->css('/local/dlelegacytools/scorm/scorm.style.css.php'.(empty($params)?'':'?'.implode('&',$params)));
        $PAGE->requires->js('/local/dlelegacytools/scorm/scorm.behaviors.js.php'.(empty($params)?'':'?'.implode('&',$params)));
    }
}