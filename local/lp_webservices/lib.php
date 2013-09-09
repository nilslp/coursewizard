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
 * @subpackage lp_webservices
 * @copyright  2013 Dennis Heaney
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('METACOMPLIANCE_ROLE_NAME', 'Meta-Compliance User');
define('METACOMPLIANCE_ROLE_SHORTNAME', 'meta_compliance_user');

function lp_webservices_generate_metacompliance_user($username,$password) {
    global $DB, $CFG;
    require_once($CFG->libdir.'/externallib.php');
    
    // one time only thing
    if ((int)$DB->record_exists('role', array('shortname' => METACOMPLIANCE_ROLE_SHORTNAME))) {
        throw new moodle_exception('error:metacompliancerolealreadycreated','local_lp_webservices');
    }
    
    // generate role
    $role = new stdClass();
    $role->name = METACOMPLIANCE_ROLE_NAME;
    $role->shortname = METACOMPLIANCE_ROLE_SHORTNAME;
    $role->description = get_string('metacomplianceroledesc', 'local_lp_webservices');
    $role->sortorder = $DB->get_field_sql('SELECT MAX(sortorder) + 1 AS sortmax FROM {role}');
    $role->archetype = 'manager';
    $newroleid = $DB->insert_record('role', $role);
    
    // add capability for metacompliance
    $contextsys = context_system::instance();
    $cap = new stdClass();
    $cap->contextid = $contextsys->id;
    $cap->roleid = $newroleid;
    $cap->capability = 'local/lp_webservices:metacompliance';
    $cap->permission = 1;
    $cap->modifierid = 0;  
    $DB->insert_record('role_capabilities', $cap);  
    
    // add capability for rest
    $cap->capability = 'webservice/rest:use';
    $DB->insert_record('role_capabilities', $cap);  
    
    // create user
    $metauser = create_user_record($username, $password);
    if (!empty($metauser)) {
        // assign metacompliance role to user
        role_assign($newroleid, $metauser->id, $contextsys->id);
        
        // generate token for user -NB: this should be handled by our event handler, but creating two doesn't hurt ... i think?
        $service = $DB->get_record('external_services', array('shortname' => 'learningpool_webservice'));
        external_generate_token(EXTERNAL_TOKEN_PERMANENT, $service, $metauser->id, $contextsys);
        set_config('metacomplianceusername', $username, 'local_lp_webservices');
    }
}

function lp_webservices_get_metauser_token() {
    global $DB, $CFG;
    require_once($CFG->libdir.'/externallib.php');

    // get token for user       
    $contextsys = context_system::instance();
    $serviceid = $DB->get_field('external_services', 'id', array('shortname' => 'learningpool_webservice'));
    $metauserid = $DB->get_field('user', 'id', array('username' => get_config('local_lp_webservices', 'metacomplianceusername')));
    
    if (empty($serviceid) || empty($metauserid)) {
        return false;
    }
    
    return $DB->get_field_sql('SELECT token FROM {external_tokens} WHERE userid = ? AND externalserviceid = ? AND contextid = ? ORDER BY id DESC', array($metauserid, $serviceid, $contextsys->id), IGNORE_MULTIPLE);
}