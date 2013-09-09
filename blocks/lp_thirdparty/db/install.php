<?php

function xmldb_block_lp_thirdparty_install() {
		
		global $CFG, $DB, $OUTPUT;
		
		$dbman = $DB->get_manager();
		
		$old_table = new xmldb_table('lp_work_queue_old');
		
		if($dbman->table_exists($old_table) && $DB->count_records('lp_work_queue_old') > 0){
			echo $OUTPUT->notification('Found existings queue records and importing','notifysuccess');	
			
			
			//Pull in old data
			$sql = "INSERT INTO {lp_work_queue} (id,userid,createdate,moddate,modid,req_attention,st_freetext)
					SELECT old.id,old.userid,unix_timestamp(old.createdate),unix_timestamp(old.moddate),old.modid,old.req_attention,''
					FROM {lp_work_queue_old} old";
			
			$DB->execute($sql);
			
			$fld = '';
			switch($DB->count_records('lp_hierarchy_depth')){
				
				case 3:
					$fld = 'subdepartmentfree';
				break;
											
				case 1:
					$fld = 'directorate';
				break;
				
				case 2:
				default:
					$fld = 'department';
				break;
				
			}
			
			//pull through the 	freetext
			$sql="update {lp_work_queue} q, {user} u set q.st_freetext = u.$fld where q.userid=u.id";
			
			$DB->execute($sql);
			
			echo $OUTPUT->notification('Queue import completed','notifysuccess');
		}
		
		if($config = $DB->get_records('config_plugins',array('plugin'=>'auth/utp_reg'))){
		
			echo $OUTPUT->notification('Carrying config over','notifysuccess');
						
			$exist_cfg = $DB->get_records_select_menu('config_plugins','plugin = \'auth/thirdparty\'',null,'name','id, name');
			
			foreach($config as $cfg){
				if(strpos($cfg->name,'field_lock_') === false ){
						
					$cfg->plugin = 'auth/thirdparty';	
					
					if(in_array($cfg->name,$exist_cfg)){
						$cfg->id = array_search($cfg->name, $exist_cfg);
						
						if($cfg->name == 'restrictdirectorates'){
							$cfg = xmldb_block_lp_thirdparty_convert_config($cfg);
						}
						
						$DB->update_record('config_plugins', $cfg);
						
					}else{
						unset($CFG->id);
						
						if($cfg->name == 'restrictdirectorates'){
							$cfg = xmldb_block_lp_thirdparty_convert_config($cfg);
						}
						
						$DB->insert_record('config_plugins', $cfg,false,true);
						
					}	
					
				}
			}
			
			
			echo $OUTPUT->notification('Config Copied','notifysuccess');
		}
			
		//echo $OUTPUT->notification('','notifysuccess');
	
}	


function xmldb_block_lp_thirdparty_convert_config($config){
	
	global $DB;
	
	if($config->name == 'restrictdirectorates'){
		
		$arr_ids = unserialize($config->value);
		if(is_array($arr_ids)){
			
			//not the most efficient db wise but this should only happen one time
			foreach($arr_ids as &$id){
				$id = $DB->get_field('lp_hierarchy', 'id', array('oldid'=>$id)); 
			}
			
			$config->value = serialize($arr_ids);
		}
		
	}
	
	return $config;
}
