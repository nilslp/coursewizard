<?php

include_once('../../../config.php');
include_once($CFG->dirroot . '/local/shortlinker/lib/lib.php');

$scode = required_param('u', PARAM_ALPHANUM);

//@TODO allow unauthenticated links as well
require_login();

$cls_url = new url_shortner();

$url = $cls_url->retrieve_url_by_shortcode($scode);

if($url){
	
	if(!headers_sent()){
		header("location: $url");
		exit;
	}
	
}else{
	print_error('unrecog_scode','local_shortlinker');
}
