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


defined('MOODLE_INTERNAL') || die();

function dle_user_created($user) {
    // DPMH - plugin is deprecated. See local/welcome_email instead.
    return;
    
    global $CFG,$DB;
    
    $dbman = $DB->get_manager();
    
     // update new welcome email
    if( $dbman->table_exists('lp_welcomeemail') ){
        
        $record = new stdClass();
        $record->userid = $user->id;
        $record->email_sent = '0';
        
        $DB->insert_record( 'lp_welcomeemail', $record,false );
    }
    
    // force password update
    $mu_pref = $DB->get_field('user_preferences','id',array('userid'=>$user->id,'name'=>'auth_forcepasswordchange')); 

    $record = new stdClass();
    $record->id = $mu_pref;
    $record->userid = $user->id;
    $record->name   = 'auth_forcepasswordchange';
    $record->value  = '1';
    
    // fix for #4850 http://tech.learningpool.com/issues/4850
    // check welcome hash as both md5 and whatever the default moodle has is
    $prehash = 'welcome';
    $passwordlist = array(
        'welcome',
        md5($prehash),
        hash_internal_user_password($prehash)
    );
    
    if( in_array($user->password,$passwordlist) ) { 
         if($mu_pref != ''){
            $DB->update_record( 'user_preferences', $record );
         }else{              
            $DB->insert_record( 'user_preferences', $record , false);
         }
    }
  return true;
}

// comment
function dle_user_policy_agreed($uid){
	global $DB, $CFG;
	if(!isguestuser() && get_config('local/dlelegacytools','wipe_policy') == 1 && !empty($CFG->sitepolicy)){
		$DB->set_field('user', 'policyagreed', 0, array('id'=>$uid));
	}
}

