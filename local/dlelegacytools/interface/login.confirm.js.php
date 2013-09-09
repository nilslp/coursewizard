<?php
/**
 * adds a return button for wordpress partnered sites. DPMH 
 */
header('Content-Type: text/javascript; charset=utf-8');

define('NO_DEBUG_DISPLAY', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG,$OUTPUT;

$btn = $OUTPUT->single_button("{$CFG->alternateloginurl}", get_string('backtowordpress','local_dlelegacytools'));

echo <<<JSCRIPT
var content = document.getElementById('region-main');
content.innerHTML = '$btn' + content.innerHTML;
JSCRIPT;
