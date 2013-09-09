<?php

require_once(dirname(__FILE__) . '/../config.php');

if(!isloggedin()){
	header('location: index.php');
	exit;
}

function get_redirect_url($url_in){
	global $USER, $CFG;

	$arr_find=array('$UID','$UDIRID','$UDEPID','$USUBID','$UEMAIL','$UFIRST','$ULAST');
	$arr_repl=array($USER->id,$USER->directorateid,$USER->departmentid,$USER->subdepartmentid,urlencode($USER->email), urlencode($USER->firstname), urlencode($USER->lastname));
	
	return str_replace($arr_find, $arr_repl, $url_in);
}

$plain_url = get_config('ext_redirect','goto');
$url=get_redirect_url($plain_url);
if(!$url){
	$url='index.php';
}
 
	
if(!has_capability('site:doanything', get_context_instance(CONTEXT_SYSTEM))){
	header('location: ' . $url);
	exit;	
}

if($_POST['redirect_url']){
	set_config('goto',$_POST['redirect_url'],'ext_redirect');
	$plain_url = $_POST['redirect_url'];
	$url=get_redirect_url($plain_url);
}

print_header_simple('External redirect','Redirection settings');
print_box_start();
?>
<div id="cnt_message"></div>
<input type="button" onclick="do_redirect()" value="Go"/>
<fieldset>
	<legend>Redirect settings <input type="button" onclick="stop_count()" value="Change Settings"/></legend>
	<form id="frm_setts" disabled="disabled" action="" method="post">
		<input type="text" name="redirect_url" value="<?php echo $plain_url?>"/>
		<input type="submit" value="Save"/>
	</form>
</fieldset>
<script type="text/javascript" language="JavaScript">
	var timr=setInterval(count_down,1000),secs=30,msg=document.getElementById('cnt_message'),frm=document.getElementById('frm_setts');
	var st_msg='Redirecting in %C% seconds',url='<?php echo $url;?>';
	function count_down(){
		secs--;
		show_status();
		if(secs==0){
			do_redirect();
		}
	}
	function show_status(){
		if(msg){
			msg.innerHTML=st_msg.replace('%C%',secs);	
		}
	}
	show_status();
	function do_redirect(){
		stop_redirect();
		window.location=url;
	}
	function stop_redirect(){
		if(timr)
			clearInterval(timr);
	}
	function stop_count(){
		stop_redirect();
		if(msg){
			msg.innerHTML='Automatic Redirect Cancelled';
		}
		if(frm)
			frm.enabled=true;		
	}
</script>
<?php
print_box_end();
print_footer();
?>