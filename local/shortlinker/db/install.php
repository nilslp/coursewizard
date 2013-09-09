<?php
include_once($CFG->dirroot . '/local/shortlinker/lib/lib.php');

function xmldb_local_shortlinker_install() {
    global $DB;

	shortlinker_write_redirect();
	
	$stnd = new stdClass();
	
	$stnd->sname = 'Clean';
	$stnd->description = "No predefined parameters are attached";
	
	$DB->insert_record('shortlinker_profile', $stnd);
	
	/*$stnd->sname = 'Good Practice SSO';
	$stnd->description = "Good Practice Single Sign On";
	$ssid = $DB->insert_record('shortlinker_profile', $stnd,true);*///Commented out for other DLEs
	
	$params =array();
	
	$stndp = new stdClass();
	$stndp->var ='lpuid';
	$stndp->token ='{u}';
	$stndp->handle ='{$USER->id}';
	
	$params[] = $DB->insert_record('shortlinker_params', $stndp,true,true);
	
	$stndp->var ='lpemail';
	$stndp->token ='{e}';
	$stndp->handle ='{$USER->email}';
	
	$params[] = $DB->insert_record('shortlinker_params', $stndp,true,true);
	
	$stndp->var ='lpdir';
	$stndp->token ='{d}';
	$stndp->handle ='{$USER->directorateid}';
	
	$params[] = $DB->insert_record('shortlinker_params', $stndp,true,true);
	
	$stndp->var ='lpdept';
	$stndp->token ='{dt}';
	$stndp->handle ='{$USER->departmentid}';
	
	$params[] = $DB->insert_record('shortlinker_params', $stndp,true,true);
	
	$stndp->var ='lpfname';
	$stndp->token ='{fn}';
	$stndp->handle ='{$USER->firstname}';
	
	$params[] = $DB->insert_record('shortlinker_params', $stndp,true,true);
	
	$stndp->var ='lplname';
	$stndp->token ='{ln}';
	$stndp->handle ='{$USER->lastname}';
	
	$params[] = $DB->insert_record('shortlinker_params', $stndp,true,true);
	
	/*$sql = 'INSERT INTO 
				{shortlinker_profile_param} (profileid,paramid)
			SELECT
				?,sp.id
			FROM
				{shortlinker_params} sp
			WHERE
				sp.id in (' . implode(',',$params) . ')';
				
	$DB->execute($sql,array($ssid));*///Commented as above	
	
	return true;
}
