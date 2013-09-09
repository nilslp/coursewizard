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
 * Library of interface functions and constants for module lpscheduler
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the lpscheduler specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package   mod_lpscheduler
 * @copyright 2010 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** example constant */
//define('lpscheduler_ULTIMATE_ANSWER', 42);

/**
 * If you for some reason need to use global variables instead of constants, do not forget to make them
 * global as this file can be included inside a function scope. However, using the global variables
 * at the module level is not a recommended.
 */
//global $lpscheduler_GLOBAL_VARIABLE;
//$lpscheduler_QUESTION_OF = array('Life', 'Universe', 'Everything');

/**
 * Description: Grabs the array of parameters and 
 * returns them as an array of objects so they're 
 * compatible for db submission.
 * 
 * @param associative array : an array of variables
 * @return object : values returned as an array of objects
 */
global $CFG;
require_once($CFG->dirroot.'/local/dlelegacytools/lpscheduler/lib.php');
require_once($CFG->dirroot.'/local/dlelegacytools/scorm/lib.php');
require_once($CFG->dirroot.'/local/dlelegacytools/interface/lib.php');

function local_dlelegacytools_cron(){
    global $CFG;
    $configs = get_config('local/dlelegacytools/lpscheduler/','lpscheduler');
    $lpscheduler = new lpscheduler_lib();  
        
    if( !empty($configs) ){
	$lpscheduler->lpscheduler_cron();
    }
    
    // the following stuff is deprecated DMPH
    return;
    
    $file = $CFG->dirroot.'/local/dlelegacytools/lpscheduler/welcomeemail/cron.php';
    
    if(file_exists($file)){
        
       require_once($file);
       mtrace('running Welcome Email');
       lpscheduler_welcomeemail_cron();
    }
}

/**
 * This method should be called by the foundation theme in its config.php
 * in order to setup and apply some scorm tweaks and look and feel tweaks
 *  
 */
function apply_legacy_settings() {
    // scorm styling/behavior
    configure_scorm_behavior();
    // misc look and feel behavior
    configure_interface_behavior();
}

