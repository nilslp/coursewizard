<?php

class url_shortner{

	private $regex_stparam = '/\?([\S\s]*)/';
	private $regex_params = '/(?:^|\&)([\d\w]+)=([\d\w\s!"£\$%\^\*]+)/';
	private $regex_uc	= '/(uc\(\(([\w\d!"£\$%\^&\*@~#:;,\.\']+)\)\))/';
	
	/**
	 * Create a random string of characters and one number
	 * 
	 * @param int $length Length of return string
	 * @return String
	 */
	public function generate_random_string($length = 10) {
				
	    return substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
	}
	
	/**
	 * Function to create a shortcode from an id integer
	 * 
	 * @param int $id Integer id to be converted into a shortcode
	 * @return String
	 */
	public function generate_shortcode($id){
			
		$code = base_convert($id,10,36);
		
		//if it's less than 4 let's pad it using a random string
		if(strlen($code) < 4){
			$code = $this->generate_random_string(4-strlen($code)).$code;
		}
		
		return $code;
	}
	
	
	public function shorten_url(array $urlattr, array $params, $forceduplicate=false, $use_profile=false){
		global $DB,$USER;
		
		
		//if using a profile clear out existing vars
		if($use_profile){
			$pronames = $this->get_params_by_proid($use_profile);
			foreach($pronames as $provar=>$prof){
				if(array_key_exists($provar, $params)){
					unset($params[$provar]);
				}
			}		
		}
		
		$create_link = true;
		
		//if not forceduplicate and there is an active record
		if(!$forceduplicate && $rec = $DB->get_record('shortlinker', array('url'=>$urlattr['url'],'active'=>1))){
		
			$create_link = false;
				
			$eparams = $this->get_full_params($urlrec->id);
			
			if(count($params)){
				foreach($params as $pvar=>$pval){
					//check the var against existing params
					if(!array_key_exists($pvar, $eparams) || $eparams[$pvar] != $pval){
						$create_link=true;
						break;
					}
				}
			}
			
			if(!$create_link){
				
				//@todo more checks this is exactly the same
				
				return $rec->scode;
			}
			
		}
		
		$nrec = (object) $this->new_shortlink_rec($urlattr);
		
		$nrec->modifierid = $USER->id;
		
		$nrec->id = $DB->insert_record('shortlinker', $nrec, true);
		
		$urlobj = new stdClass();
		$urlobj -> id = $nrec->id;
		$urlobj -> scode = $this->generate_shortcode($nrec->id);
		
		$DB->update_record('shortlinker', $urlobj);

		if(!$use_profile){
			$this->store_params($nrec->id, $params);
		}else{
			$this->attach_params($nrec->id,$use_profile);
			if(count($params)){
				$this->blind_store_params($nrec->id, $params);
			}
		}
		
		return $urlobj->scode;
		
	}	

	private function blind_store_params($id,$params){
		global $DB;
		
		$pids = array();
		
		if(count($params)){
			$check_params = '"' . implode('","',array_keys($params)) . '"';
		
			$sql = "SELECT * FROM {shortlinker_params} WHERE var in ($check_params)";
			
			if($exist_params = $DB->get_records_sql($sql)){
				foreach($check_params as $cparam){
					if(array_key_exists($cparam->var, $params)){
						unset($params[$cparam->var]);
						$pids[]=$cparam->id;
					}
				}
			}
			
			$ncomb = new stdClass();
			$ncomb->paramid =0;
			$ncomb->slinkid =$id;
			
			if(count($params)){
				$nparam = new stdClass();
				$nparam->token	=0;
				$nparam->handle	=0;
				
				
				foreach($params as $k => $p){
					
					$nparam->token	= $k;
					$nparam->handle	= $p;
					
					$ncomb->paramid = $DB->insert_record('shortlinker_params', $nparam,true,true);
					
					$DB->insert_record('shortlinker_combine', $ncomb,false,true);
				}
			}
			
			foreach($pids as $pid){
				$ncomb->paramid = $pid;	
				$DB->insert_record('shortlinker_combine', $ncomb,false,true);
			}
			
		}
		
	}

	
	private function store_params($id,array $params){
		if(count($params) > 0){
			
			$exist_params = $this->get_params($id);
			foreach($exist_params as $ep){
				if(array_key_exists($ep->token, $params)){
					if($params[$ep->token] !== $ep->handle){
						$DB->delete_records('shortlinker_combine',array('paramid'=>$ep->id, 'slinkid'=>$id));
						
						$new_cmb = new stdClass();
						$new_cmb->slinkid = $id;
						
						unset($ep->id);
				
						$ep->handle = $params[$ep->token];
						$new_cmb->paramid=$DB->insert_record('shortlinker_params', $ep,true,true);
					}
					unset($params);
				}
			}
			
			$nparam = new stdClass();
			$nparam->token	=0;
			$nparam->handle	=0;
			$ncomb = new stdClass();
			$ncomb->paramid =0;
			$ncomb->slinkid =$id;
			
			foreach($params as $k => $p){
				
				$nparam->token	= $k;
				$nparam->handle	= $p;
				
				$ncomb->paramid = $DB->insert_record('shortlinker_params', $nparam,true,true);
				
				$DB->insert_record('shortlinker_combine', $ncomb,false,true);
			}
			
		}
	}
	
	/**
	 * Function to retrieve params
	 * 
	 * @param int $id ID of the shortlink
	 * @return array
	 */
	public function get_params($id){
		global $DB;
		
		$ret =  array();
		
		$sql = "SELECT sp.* FROM 
					{shortlinker_params} sp
				JOIN {shortlinker_combine} sc 
					ON sp.id = sc.paramid
				WHERE
					sc.slinkid = ?";
		
		if($dparams = $DB->get_records_sql($sql,array($id))){
			foreach($dparams as $dp){
				$ret[$dp->token] = $dp->handle;
			}
		}
		
		return $ret;
	}
	
	public function get_full_params($id){
		global $DB;
		
		$ret =  array();
		
		$sql = "SELECT sp.* FROM 
					{shortlinker_params} sp
				JOIN {shortlinker_combine} sc 
					ON sp.id = sc.paramid
				WHERE
					sc.slinkid = ?";
		
		if($dparams = $DB->get_records_sql($sql,array($id))){
			foreach($dparams as $dp){
				$ret[$dp->var] = $dp;
			}
		}
		
		return $ret;
	}
	
	public function get_params_by_proname($pro_name){
		global $DB;
		
		$ret = array();
		
		$sql = "SELECT p.*
				FROM {shortlinker_params} p
				JOIN {shortlinker_profile_param} spp ON
				  spp.paramid = p.id
				JOIN {shortlinker_profile}  sp ON
				  sp.id = spp.profileid
				  WHERE
   					sp.sname = ?";
		
		if($dparams = $DB->get_records_sql($sql,array($pro_name))){
			foreach($dparams as $dp){
				$ret[$dp->var] = $dp;
			}
		}
		
		return $ret;
	}
	
	public function get_params_by_proid($pro_id){
		global $DB;
		
		$ret = array();
		
		$sql = "SELECT p.*
				FROM {shortlinker_params} p
				JOIN {shortlinker_profile_param} spp ON
				  spp.paramid = p.id
				JOIN {shortlinker_profile}  sp ON
				  sp.id = spp.profileid
				  WHERE
   					sp.id = ?";
		
		if($dparams = $DB->get_records_sql($sql,array($pro_id))){
			foreach($dparams as $dp){
				$ret[$dp->var] = $dp;
			}
		}
		
		return $ret;
	}
	
	private function new_shortlink_rec($arrin){
		
		$arrin = array_merge(array(
								'scode'			=> '',
								'description' 	=> '',
								'url'			=> '',
								'active'		=> 1,
								'created'		=> time(),
								'modified'		=> time(),
								'modifierid'	=> 0
								),	
								$arrin);
								
		return $arrin;
		
	}
	
	public function find_by_shortcode($scode){
		global $DB;
		
		$sql = "SELECT *  FROM {shortlinker} WHERE BINARY `scode` = '$scode'";
		
		if($rec = $DB->get_record_sql($sql)){
			
			return $rec;
			
		}
		
		return false;
	}
	
	public function retrieve_url_by_shortcode($scode){
		global $DB,$CFG,$USER,$SITE;
		
		if($urlrec = $this->find_by_shortcode($scode)){
			
			$retst = $urlrec->url;
			
			$params = $this->get_full_params($urlrec->id);
			
			//$retst = str_replace(array_keys($params), $params, $retst);
			$append = array();
			if($params){
				foreach($params as $p){
					$append[]=$p->var . '=' . (strpos($p->handle, '}')? "uc(($p->handle))":$p->handle);
				}
			}
			
			$append = '?' . implode('&',$append);
			
			$retst .= $append;
						
			eval('$retst="' . $retst . '";');
			
			$retst = $this->urlencode_vars($retst);
												
			return $retst;
		}
		
		return false;
	}
	
	public function stringify_params($params){
			$append = array();
			if($params){
				foreach($params as $p){
					$append[]=$p->var . '=' . (strpos($p->handle, '}')? "uc(($p->handle))":$p->handle);
				}
			}
			$append = '?' . implode('&',$append);
		
			return $append;			
	}
	
	public function parse_url($urlin){
		global $DB, $CFG, $SITE, $USER;
				
		//error_log('Pre_eval::' . $urlin);
		
		eval('$urlin="' . $urlin . '";');
		
		//error_log('Post_eval::' . $urlin);
		
		$urlin = $this->urlencode_vars($urlin);
		
		return $urlin;
	}
	
	private function urlencode_vars($stin){
		if(preg_match_all($this->regex_uc, $stin,$matchs)){
			foreach($matchs[0] as $i=>$m){
				$stin = str_replace($m, urlencode($matchs[2][$i]), $stin);
			}
		}
		
		//cleanup any empty refs
		$stin = str_replace('uc(())', '', $stin);
		
		return $stin;
	}
	
	public function find_by_url($url){
		global $DB;
		
		if($rec = $DB->get_record('shortlinker', array('url'=>$url))){
			return $rec;
		}
		
		return false;
	}
	
	public function find_all_by_url($url){
		global $DB;
		
		if($recs = $DB->get_records('shortlinker',  array('url'=>$url))){
			return $recs;
		}
		
		return false;
	}
	
	public function breakdown_url($url){
		$return = new shortlinker_url();
		
		$return->baseurl = $url;
		$return->fullurl = $url;
			
		if(strpos($url, '?') > 0){
			$uarr = explode('?', $url,2);
			$return->baseurl=$uarr[0];
			$return->st_params=trim($uarr[1]);
			$return->stp=$uarr;
			
			if(!empty($return->st_params)){
				if(preg_match_all($this->regex_params, $return->st_params,$matchs) > 0){
					foreach($matchs[0] as $i=>$m){
						//$return->params[] = array($matchs[1][$i] => $matchs[2][$i]);
						$return->params[] = new shortlinker_url_param($matchs[1][$i],$matchs[2][$i]);
					}
				}
			}
		}
		
		error_log('Params');
		error_log(print_r($return,true));
		
		return $return;
	}
	
	public function get_sys_vars(){
		//@Todo make this part dynamic and sexy
		return array(
					'User' 	=> array(
									array('svar'=>'{$USER->firstname}','title'=>'First name'),
									array('svar'=>'{$USER->lastname}','title'=>'Last name'),
									array('svar'=>'{$USER->id}','title'=>'ID'),
								),
					'Site' 	=> array(
									array('svar'=>'{$SITE->name}','title'=>'Site'),
								)
				);
	}
	
	private function attach_params($uid,$prof){
		global $DB;
		
		$sql = 'INSERT INTO
					{shortlinker_combine} (slinkid,paramid)
				SELECT
					?, sp.paramid
				FROM 
					{shortlinker_profile_param} sp
				WHERE
					sp.profileid = ?';
		
		$DB->execute($sql,array($uid,$prof));
	}
		
}

function shortlinker_write_redirect($params = false){
	global $CFG;
	
	if($params){
		$redirect_params = $params;
	}
	
	$exist_file = get_config('local_shortlinker','redirect_file');
	if(file_exists($exist_file) && is_file($exist_file)){
		unlink($exist_file);
	}
	
	include(dirname(__FILE__) . '/../res/write_redirect.php');
	
}

class shortlinker_url extends stdClass{
	public 	$baseurl='',
			$st_params='',
			$params=array(),
			$fullurl='';
}

class shortlinker_url_param extends stdClass{
	public $var,$val;
	public function __construct($n,$v){
		$this->svar = $n;
		$this->val = $v;
	}
}
