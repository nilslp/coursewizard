<?php

//code to handle data mapping, wrapped in class to prevent function clashes
class lp_data_api{
    private $_cfg;
    /**
     * Function to check DB for user profile field existance
     * Accepts the shortname for field to compare with table
     * 
     * @param string $fname 
     * @return boolean
     */
    public function has_user_field($fname=''){
        global $DB;
        
        return $DB->record_exists('user_info_field',array(
           'shortname'=> $fname
        ));
        
    }
    
    /**
     * Accepts the authentication type as param so it can be used for multiple
     * authentication types
     *
     * @param type $authtype
     * @param type $auth_config 
     */
    function __construct($authtype='',&$auth_config=null) {
         $this->_cfg=new stdClass();
         
         $this->_cfg->mask = '/data_api_field_/';
         
         $this->_cfg->ufield=array(
                                'shortname'     => '',
                                'name'          => '',
                                'datatype'      => 'text',
                                'description'   => '',
                                'required'      => 0,
                                'locked'        => 0,
                                'visible'       => 0,
                                'signup'        => 0
                               );

        $this->_cfg->type=$authtype;
		
		#error_log('Construct of lp_data_api');
		
        if(trim($this->_cfg->type) !== ''){
        	
            //strip out all auth configs that are for mapping
            $this->_cfg->maps = array();
            foreach($auth_config as $k=>$v){
                if(preg_match($this->_cfg->mask,$k) != 0){
                    $this->_cfg->maps[preg_replace($this->_cfg->mask,'',$k)]=$v;
                }
            }
            
            /*if(isset($this->_cfg->maps)){
                //decode the string of mapping
                $this->_cfg->maps=  json_decode($this->_cfg->maps, true);
            }else{
                $this->_cfg->maps=false;
            }*/
            if(count($this->_cfg->maps) == 0){
            	$this->_cfg->maps = false;
            }
			
			if(!isset($this->_cfg->seperator)){
				$this->_cfg->seperator = '-';
			}
        }
        
    }
    
    /**
     * Function to create a new user profile field
     * 
     * @param type $params
     * @return boolean 
     */
    public function create_user_field($params){
        global $DB;
        
        $params = array_merge($this->_cfg->ufield, $params); //ensure the vital defaults are set
        
        if(isset($params['shortname']) && trim($params['shortname']) !== '' && !$this->has_user_field($params['shortname'])){
            $iid=$DB->insert_record('user_profile_field',$params,true);
            if($iid > 0 ){
                return true;                
            }
            return false;
        }else{
            debug('Tried to create a user field that already exists', DEBUG_DEVELOPER);
        }
    }
    
    /**
     * Function called when authentication has been called, 
     * this is mainly used to hive off the  hierarchy string 
     * before it can be truncated
     * 
     * @param array $res Result array from the authentication method
     */
    public function process_data(&$res){
        global $DB;
        
		#error_log('Process Data');
		
        if($this->_cfg->maps !== false){
            $qry= array();
            foreach ($this->_cfg->maps as $k=>$v){
                if(isset($res[$k])){
                    //store value to query db later
                    //$qry[$k] = 'hierarchy_in = "' . $res[$k] . '"';
					$qry[$k] = $this->gen_hin_query($res[$k]);
                }
            }
            
            if(count($qry) > 0){
                $select = "auth=\"{$this->_cfg->type}\" AND (" . implode(' OR ', $qry) . ')';
				
				#error_log('Check for existing entry :: ' .$select);
				
                if($dbres=$DB->get_records_select('lp_datamap_hierarchy',$select)){
                	#error_log('Found existing records');
					
					#error_log(print_r($qry,true));
					
                    $ky_hi='';
                    foreach($dbres as $r){
                    	#error_log('Check for ' . $r->hierarchy_in);
                        if($ky_hi=array_search($this->gen_hin_query($r->hierarchy_in), $qry)){
                    		#error_log('Found it');    	
                            //remove from our query object
                            unset($qry[$ky_hi]);
                            //store the db record id
                            $res[$ky_hi]=$r->id;
                        }
                    }
                }
                if(count($qry) > 0){
                    $tmp_rec = new stdClass();
                    $tmp_rec->auth = $this->_cfg->type;
                    foreach($qry as $k=>$v){
                        $tmp_rec->hierarchy_in=$res[$k];
                        //store the raw data into the table and record the new ID into the result array
                        $res[$k]=$DB->insert_record('lp_datamap_hierarchy',$tmp_rec,true);
                    }
                }else{
                	#error_log('Nothing to do');
                }
            }
        }
    }

	private function gen_hin_query($val){
		return 'hierarchy_in = "' . $val . '"';
	}
        
    public function map_data($user){
        global $DB;
        
		$src = realpath(dirname(__FILE__));
		
		#error_log('Mapping data');
		#error_log(print_r($user,true));
		
        if($this->_cfg->maps !== false){
        
			#error_log('Found Maps');	
		
            foreach($this->_cfg->maps as $k=>$v){
            	
				#error_log('Map data :: ' . $k);
				
                if(isset($user->$k)){
                    if($dta = $DB->get_record('lp_datamap_hierarchy',array('id'=>$user->$k))){
                    	#error_log('Map retrieved the record');
						$mapper = $src . '/lib/mappers/'. $k . '.php';
						
                    	if(file_exists($mapper)){
                    		include_once($mapper);
							$class = 'translator_' . $k;
							
							#error_log('Config passed');
							#error_log(print_r($this->_cfg,true));							
							
							$class = new $class($this->_cfg->type);
							if($mapres = $class->convert($user->id,$dta,$this->_cfg)){
								//get data field id first
								if($dfield = $DB->get_record('user_info_field', array('shortname'=>$mapres->field))){
								
									$nrec = new stdClass();
									$nrec->fieldid = $dfield->id;
									$nrec->data = $mapres->value;
									$nrec->userid = $user->id;
									
									if(isset($mapres->id)){
										$nrec->id = $mapres->id;
										$DB->update_record('user_info_data', $nrec);
									}else{
										$DB->insert_record('user_info_data', $nrec);
									}							
								}else{
									#error_log('Could not find datafield');
								}
							}
						}else{
							#error_log('Map no mapper found for ' . $k . ' at ' . $mapper);
						}
                    }else{
                    	#error_log('Mapping FAIL');
                    	debugging('Tried to retrieve data for user field - ' . $k . ', with id - ' . $user->$k , DEBUG_DEVELOPER);	
                    }
                }else{
                	#error_log('Map no attribute in user object');
                }
            }
            
        }
                
    }

}

abstract class data_map_translator{
	
	protected $mytype = '';
	
	public function __construct($authtype){
		#error_log('Parent constructor called');
		$this->mytype = $authtype;
	}
	
	abstract function convert($uid,$data_in,$cfg);
	
}

?>