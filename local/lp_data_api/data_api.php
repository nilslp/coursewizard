<?php
/*
 * Created to process input from ldap
 * @author rwm 16/05/2012
 */

include_once(dirname(__FILE__).'/lib.php');

if (isset($result)){
	
	if(!isset($result['department']) || empty($result['department'])){
		$result['department'] = 'Unknown - Unknown - Unknown';
		$result['subdepartmentfree'] = 'auth'; 
	}
	
    $da_mngr = new lp_data_api($this->authtype,$this->config);
    
    $da_mngr->process_data($result);

}else{
	error_log('no result to doctor');
}

?>