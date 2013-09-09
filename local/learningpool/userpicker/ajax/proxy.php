<?php

/**
 * This file serves as a proxy for AJAX requests 
 */
require_once '../../../../config.php';
global $CFG, $USER;
require_once $CFG->dirroot.'/lib/adminlib.php';
require_once($CFG->dirroot . '/user/selector/lib.php');

$sesskey = required_param('sesskey', PARAM_TEXT);
$searchtext = optional_param('searchtext','',PARAM_TEXT);

if (!confirm_sesskey($sesskey)) {
    die;
}
    
// User can choose any user except themselves
$results = get_users(true, $searchtext, true, array($USER->id), 'lastname ASC', '', '', '', 100, 'id,firstname,lastname,email');

$return_value = new stdClass();
$return_value->searchtext = $searchtext;
$return_value->users = array_values($results);
$return_value->success = true;

$json = json_encode($return_value);
        
// Return the JSON
header('Content-type: application/json');
echo $json;
die;

?>
