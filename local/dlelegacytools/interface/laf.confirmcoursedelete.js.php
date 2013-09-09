<?php
header('Content-Type: text/javascript; charset=utf-8');

define('NO_DEBUG_DISPLAY', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG, $OUTPUT;

$course = optional_param('course', 0, PARAM_INT);
$sessionkey = sesskey();

echo <<<JSCRIPT
YUI().use('node','event',function(Y){
    var list = Y.all('a.action-icon');
    
    list && list.each(function(node) { 
        var url = node.getAttribute('href');
        var delete_pos = url.lastIndexOf('delete.php?');
        
        if (parseInt(delete_pos) > 0) {
            var id = url.substring(parseInt(delete_pos) + 11);

            node.setAttribute('href', '{$CFG->wwwroot}/local/dlelegacytools/confirmdelete/confirmcoursedelete.php?' + id);
        }
    });
});
JSCRIPT;
