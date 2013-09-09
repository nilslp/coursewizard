<?php
header('Content-Type: text/javascript; charset=utf-8');

define('NO_DEBUG_DISPLAY', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG;

echo <<<JSCRIPT
YUI().use('node','event',function(Y){
    var list = Y.all('td.cell.c5 a');
    
    list && list.each(function(node){ 
        var link = node.get('href');
        if (link.indexOf('delete') > 0){
            node.set('href',link.replace(/admin\/user\.php/,'local/dlelegacytools/interface/admin/user.php'));
        }
    });
});
JSCRIPT;
