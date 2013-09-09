<?php

(defined('MOODLE_INTERNAL')) || die('This is not standalone');

$default_params = array(
						'filepath'=>'/',
						'filename'=>'small.php'
						);

if(isset($redirect_params)){

	$default_params = array_merge($default_params,$redirect_params);
	
}

$target_file = $CFG->dirroot . $default_params['filepath'] . $default_params['filename'] ;

if(is_writeable($target_file)){
	
	if($fh = fopen($target_file,'w+')){
			
		$contents = array(
							'<?php',
							"include_once('{$CFG->dirroot}/config.php');",
							"include_once('$CFG->dirroot/local/shortlinker/lib/lib.php');",
							"\$scode = required_param('u', PARAM_ALPHANUM);",
							"//@TODO allow unauthenticated links as well",
							"require_login();",
							"\$cls_url = new url_shortner();",
							"\$url = \$cls_url->retrieve_url_by_shortcode(\$scode);",
							"if(\$url){",
							"	if(!headers_sent()){",
							"		header(\"location: \$url\");",
							"		exit;",
							"	}",
							"}else{",
							"	print_error('unrecog_scode','local_shortlinker');",
							"}"
						);	
			
			
		foreach($contents as $cont){			
	
			fputs($fh, "$cont\n");
		
		}
		set_config('redirect_file',$target_file,'local_shortlinker');		
		set_config('redirect_file_www', $CFG->wwwroot . $default_params['filepath'] . $default_params['filename'], 'local_shortlinker');
	
		fclose($fh);
	}else{
		debugging(get_string('cannot_write_redirect','local_shortlinker',$target_file),DEBUG_ALL);
	}
	
}else{
	debugging(get_string('cannot_write_redirect','local_shortlinker',$target_file),DEBUG_ALL);
}
