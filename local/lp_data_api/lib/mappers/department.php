<?php

/**
*	Handle department transfer
**/

class translator_department extends data_map_translator{
	
	/*public function __construct($authtype){
		error_log('Converter set type to ' . $authtype);
		$this->mytype = $authtype;
	}*/
	
	public function convert($uid,$data_in,$cfg){
		global $DB;
		
		//error_log('Converter for department');
		
		$hierarchy = Hierarchy::get_instance();
		
		//error_log('Converter Mytype = ' . $this->mytype);
		
		switch($this->mytype){
			case 'ldap':
				$new_data = array(); 
				$ilvls = $hierarchy->get_max_depth();
				$ilvl=1;
				$bmatch = false;
				
				$data_in->hierarchy_in = trim($data_in->hierarchy_in,' ' . $cfg->seperator);
				if($data_in->hierarchy_in !== ''){
					$arrlvls = explode($cfg->seperator,$data_in->hierarchy_in);
					foreach($arrlvls as $ulvl){
						if($ilvls < 1 ){
							//more levels have been pulled through than there are in the system
							break;
						}
						
						$ulvl = trim($ulvl);
						if($ulvl !== ''){
							$new_data[$ilvl] = trim($ulvl);	
						}else{
							//blank level detected break out
							break;
						}
						
						$ilvl++;
						$ilvls--;
					}
				}
				
				if($ilvls > 0){
					//fewer levels than in the system passed, pad the array
					for($i = 0; $i < $ilvls; $i++){
						$new_data[$ilvl] = 'Unknown';
						$ilvl++;
					}
				}
				
				error_log('Calculated new data');
				error_log(print_r($new_data,true));
				
				$sql = 'SELECT mh.* FROM {user_info_data} ud
						JOIN {user_info_field} uf on
							uf.shortname = \'hierarchyid\'
							AND
							ud.fieldid = uf.id
						JOIN {lp_hierarchy} mh on
							find_in_set(mh.id ,ud.data)
						WHERE
							ud.userid = ?
							order by mh.path';
				
				
				if($urec = $DB->get_records_sql($sql,array($uid))){
					
					error_log('Retrieved Existing Data');
					error_log(print_r($urec,true));
					
					$bmatch = true;
					foreach($urec as $ur){
						//cannot use depthid as that may not relate to the exact level
						if($ur->fullname != $new_data[$this->path_to_count($ur->path)]){
							$bmatch = false;
							break;		
						}
					}
					//there were no name differences at any level so there
					//isn't anthing to change
					if($bmatch === true){
						error_log('Matched existing records');
						return false;
					}
				}
				
				//this is painful but there is no other way around it
				$path = '';
				foreach ($new_data as $k => $v) {
				
					if($oid = $this->match_depth_name($urec, $this->path_to_count($path) + 1, $v)){
						//found match in the original records
						$path .= '/' . $oid;
					}else if($crec = $this->find_hierarchy($v, $path)){
						//record already exists	
						$path .= '/' . $crec->id;
					}else{
						$path .= '/' . $this->create_hierarchy($v, $path);	
					}
					
				}
				
				$return = new stdClass();
				$return->field = 'hierarchyid';
				$return->value = str_replace('/', ',', trim($path,'/'));
				
				$side = new stdClass();
				$side->userid = $uid;
				$side->hierarchyid = end(explode(',', $return->value));
				
				
				
				if($oside = $DB->get_record('lp_user_hierarchy',array('userid'=>$uid))){
					$side->id = $oside->id;
					$DB->update_record('lp_user_hierarchy', $side);
				}else{
					$DB->insert_record('lp_user_hierarchy', $side);
				}
				
				error_log('Side data');
				error_log(print_r($side,true));
				
				error_log('Returning data');
				error_log(print_r($return,true));
				
				return $return;
			break;
		}
	}

	private function match_depth_name($urecs,$depth,$fullname){
		if($urecs === false){
			return false;
		}
			
		foreach($urecs as $ur){
			if($ur->fullname == $fullname and $this->path_to_count($ur->path) == $depth){
				return $ur->id;
			}
		}
		
		return false;
		
	}
	
	private function find_hierarchy($fullname,$path){
		global $DB;
		
		$selection = 'fullname = "' . $fullname . '"';
		
		$selection .= " and path REGEXP '$path/[0-9]+'";
		
		return $DB->get_record_select('lp_hierarchy', $selection,null,'*',IGNORE_MULTIPLE);
		
	}
	
	private function create_hierarchy($fullname,$path){
		global $DB,$USER;
		
		error_log('Create Hierarchy with path :: ' . $path);
		
		if(empty($path)){
			$path = '';
		}
		
		$expath = explode('/',$path); //split into 2 statements for strict standards
		$lastid = end($expath);
		if($lastid == ''){
			$lastid = 0;
		}
		$hierarchy = Hierarchy::get_instance();
		
		$nrec = new stdClass();
		$nrec->fullname = $fullname;
		$nrec->shortname = (($lastid===0)?'dir':'sub' . $lastid) . '_' . strtolower(str_replace(' ', '_', $fullname));
		$nrec->description ='';
		$nrec->depthid = $hierarchy->get_depth_by_level($this->path_to_count($path)+1)->id;
		$nrec->parentid = $lastid;
		$nrec->visible = 1;
		$nrec->timecreated = time();
		$nrec->sortorder=1;
		$nrec->timemodified = $nrec->timecreated;
		$nrec->modifierid = $USER->id;
		
		error_log("Creating new hierarchy with");
		error_log(print_r($nrec,true));
		
		$nid = $DB->insert_record('lp_hierarchy', $nrec,true);
		
		$nrec = new stdClass();
		$nrec->id = $nid;
		$nrec->path = $path . '/' . $nid;
		$nrec->sortorder = $nid;
		
		$DB->update_record('lp_hierarchy', $nrec);
		
		return $nid;
	}
	
	private function path_to_count($path){
		return substr_count($path, '/');
	}
	
}
