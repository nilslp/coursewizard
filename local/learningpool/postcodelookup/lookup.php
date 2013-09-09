<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG,$PAGE;
require_once($CFG->libdir.'/datalib.php');
require_once(dirname(__FILE__).'/lib.php');

// catch warnings etc and expunge them!
ob_start();

$context = context_system::instance();
$PAGE->set_context($context);

$postcode   = optional_param('postcode','',PARAM_ALPHANUM);
$result     = array();

ob_clean();
header('Content-type: application/json');
ob_flush();

$result = learningpool_postcode_query($postcode);

echo json_encode($result);