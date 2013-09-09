<?php


defined('MOODLE_INTERNAL') || die();

include_once(dirname(__FILE__). '/lib.php');

function map_user_created($user){
	error_log('user created');
    $da_mngr = new lp_data_api($user->auth,get_config('auth/'.$user->auth));
    $da_mngr->map_data($user);
}

function map_user_updated($user){
    error_log('user updated');
	#error_log(print_r($user,true));
	$da_mngr = new lp_data_api($user->auth,get_config('auth/'.$user->auth));
	$da_mngr->map_data($user);
}

?>