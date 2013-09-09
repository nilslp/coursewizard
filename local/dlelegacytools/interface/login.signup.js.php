<?php
/**
 * hides some profile fields on the signup page. straight up js for speed. DPMH 
 */
header('Content-Type: text/javascript; charset=utf-8');

define('NO_DEBUG_DISPLAY', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG;

function check_default_value_exists($name) {
    global $CFG;
    $exists = true; // optimistic 
    switch($name){
        case 'city':
            $exists = !empty($CFG->defaultcity);
            break;
        case 'country':
            $exists = !empty($CFG->country);
            break;
        default:
            break;
    }
    
    return $exists;
}

$fields = get_config('moodle','hiddenuserfields');
if (empty($fields)){
    exit;
}
$fields = explode(',',$fields);
$cull = array();

// check that default values exist for required fields
foreach ($fields as $f){
    if (check_default_value_exists($f)){
        $cull []= $f;
    }
}


$cull = json_encode($cull);

echo <<<JSCRIPT
var fields = $cull;
var field = {};
var iter = 0;
for (iter = 0; iter < fields.length; ++iter){
    field = document.getElementById('fitem_id_'+fields[iter]);
    field && field.setAttribute('style','display:none;');
}
JSCRIPT;
