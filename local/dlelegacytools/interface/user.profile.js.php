<?php
/**
 * hides some profile fields on the signup page. straight up js for speed. DPMH 
 */
header('Content-Type: text/javascript; charset=utf-8');

define('NO_DEBUG_DISPLAY', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG,$DB;
require_once($CFG->dirroot.'/user/profile/lib.php');

$fields = explode(',',$CFG->hiddenuserfields);

// add custom fields that are invisible
$userinfo = $DB->get_records_sql("SELECT shortname FROM {user_info_field} WHERE visible=?",array(PROFILE_VISIBLE_NONE));
foreach ($userinfo as $ui){
    $fields []= 'profile_field_'.$ui->shortname;
}

$fields = json_encode($fields);
echo <<<JSCRIPT
var fields = $fields;
var field = {};
var iter = 0;
for (iter = 0; iter < fields.length; ++iter){
    field = document.getElementById('fitem_id_'+fields[iter]);
    field && field.setAttribute('style','display:none;');
}
JSCRIPT;
