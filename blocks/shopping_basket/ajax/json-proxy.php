<?php

/**
 * This file serves as a proxy for AJAX requests 
 */
require_once '../../../config.php';
global $CFG;
require_once($CFG->dirroot . '/blocks/shopping_basket/lib.php');

$sesskey = required_param('sesskey', PARAM_TEXT);
$operation = optional_param('op', '', PARAM_TEXT);

if (!confirm_sesskey($sesskey)) {
    die;
}

switch ($operation) {
    case 'send_code':
        $email = required_param('email', PARAM_EMAIL);
        $code = required_param('code', PARAM_TEXT);
        
        $return_value = new stdClass();
        
        $return_value->success = send_individual_license($email, $code);
        
        $json = json_encode($return_value);
        
        break;
}

// Return the JSON package
header('Content-type: application/json');
echo $json;
die;

