<?php
define('CLI_SCRIPT',true);
include_once(dirname(__FILE__) . '/../../config.php');
include_once(dirname(__FILE__) .'/lib.php');
include_once($CFG->dirroot . '/blocks/lp_hierarchy/lib.php');
//echo ini_get('error_log');
//ini_set('error_log',dirname(__FILE__) . '/reprocess.log');
//echo ini_get('error_log');
$CFG->debug=38911;
$CFG->debugdisplay=1;
error_reporting(E_ALL);

if(!$cauth = get_config('auth/ldap')){
	prnt_msg("No config found");
}

if($prev_strings = $DB->get_records('lp_datamap_hierarchy', array('auth'=>'ldap'))){
	
	if($users = $DB->get_records_select('user', 'department REGEXP \'[0-9]+\' AND auth="ldap"')){
		
		$uids = implode(',',array_keys($users));
		
		$exist_hierarchy = $DB->get_records_select('lp_user_hierarchy', 'userid in (' . $uids . ')',array(),'','userid,hierarchyid');
		
		foreach($users as $user){
			prnt_msg("\tProcessing UID::{$user->id}");
			$da_mngr = new lp_data_api($user->auth,$cauth);
    		$da_mngr->map_data($user);
		}
		
		$new_hierarchy = $DB->get_records_select('lp_user_hierarchy', 'userid in (' . $uids . ')',array(),'','userid,hierarchyid');
		
		$mods=array();
		prnt_msg('Compiling list of modifications ..'); 
		foreach($exist_hierarchy as $oldh){
			prnt_msg("Comparing user::{$oldh->userid} _ {$oldh->hierarchyid} to  {$new_hierarchy[$oldh->userid]->hierarchyid}");
			if(! $oldh->hierarchyid == $new_hierarchy[$oldh->userid]->hierarchyid){
				$mods[] = "Modified User :: {$oldh->userid} moved from {$oldh->hierarchyid} -  {$new_hierarchy[$oldh->userid]->hierarchyid}";
			}
		}
		
		prnt_msg("Modifcations made...");
		prnt_msg(implode("\n",$mods));
		
	}else{
		prnt_msg("No Users Found!");
	}
	
}else{
	prnt_msg("No previous strings!");
}

function prnt_msg($msg){
	echo "$msg\n";	
}
