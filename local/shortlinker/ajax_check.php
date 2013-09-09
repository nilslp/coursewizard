<?php

include_once('../../config.php');
include_once($CFG->dirroot . '/local/shortlinker/lib/lib.php');

$urlcheck = required_param('nurl', PARAM_URL);
$act	  = optional_param('act', 'check', PARAM_ALPHA);
$extra	  = optional_param('ext', '', PARAM_RAW);
$profs	  = optional_param('gp', -1, PARAM_INT);
$nparams  = optional_param('np', '', PARAM_RAW);


$urlcheck = urldecode(trim($urlcheck));

$return = array(
				'msg'=>'',
				'success'=>false
				);

	
if($urlcheck !== ''){

		$shortner = new url_shortner();
	
		$ourl = $shortner->breakdown_url($urlcheck);
		
		switch($act){
			case 'check':
				
				$return['params']	=$ourl->params;
				$return['base']		=$ourl->baseurl;
				$return['stparam']	=$ourl->st_params; 
				$return['fullurl']	=$ourl->fullurl;
				
				if(count($return['params'])){
					$return['psel'] = array();
					
					$return['psel'][] = '<optgroup label="Current Value"><option value="{cval}">{cval}</option></optgroup>';
					
					foreach($shortner->get_sys_vars() as $ksys => $sysvar){
						$return['psel'][] = "<optgroup label=\"$ksys\">";
						
						foreach($sysvar as $kdyn=>$yvar){
							$return['psel'][] = "<option value=\"{$yvar['svar']}\">{$yvar['title']}</option>";
						}
						
						$return['psel'][] = "</optgroup>"; 
					}
					
					$return['psel'][] = '<optgroup label="Ignore This Param"><option value="-1">Nothing</option></optgroup>';
					
					$return['psel'] = implode("", $return['psel']);
					
				}
				
				//check for an existing record of this url
				/*if($urecs = $shortner->find_all_by_url($ourl->baseurl)){
					$return['msg'] = get_string('ajax_exists','local_shortlinker');
					$return['success'] = true;
					$return['prev_recs'] =array();
					
					foreach($urecs as $urec){
						
						$return['prev_recs'][] = array('id'		=>$urec->id,
													   'example'	=> $shortner->retrieve_url_by_shortcode($urec->scode)
													  );
						
						
					}
					
				}else{*/
					$return['msg'] = get_string('ajax_goodurl','local_shortlinker');
					$return['success'] = true;
				//}
			break;
			case 'create':
				//create the link
				
				if(!empty($nparams)){
					//process the new params
					error_log('New params = ' . $nparams);
					
					$arrparams = explode(';',$nparams);
					
					foreach($arrparams as $newp){
						$newp = explode(':',$newp,2);
					}
					
					//create a new profile
					
				}	
				
				$nscode = $shortner->shorten_url(array('url'=>$ourl->baseurl), array(),false,$profs);
				
				$return['success'] = true;
				$return['url'] = get_config('local_shortlinker','redirect_file_www') . '?u=' . $nscode;

			break;
			case 'fparams':
				//find existing params by var
				$extra = trim($extra);
				if(!empty($extra)){
					
					$vars = explode(',',$extra);
					
					foreach($vars as &$var){
						$var = trim($var);
					}
					$return['vars']=false;
					if($rvars = $DB->get_records_list('shortlinker_params', 'var', $vars)){
						$return['vars']=array();
						foreach($rvars as $rvar){
							$return['vars'][$rvar->var]=array('id'=>$rvar->id,'handle'=>$rvar->handle);
						}
					}
				}
			break;
		}

}else if($profs > -1){
	//all profiles
	if($profs == 0){
		$return['profiles'] = array_values($DB->get_records('shortlinker_profile'));
	}else{//specific profile
		
	}
}else{
	$return['msg'] =  get_string('ajax_emptyurl','local_shortlinker');
}

echo json_encode($return);