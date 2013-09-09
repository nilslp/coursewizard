<?php

function xmldb_local_lp_courseaccessmanager_uninstall() {
    global $CFG, $DB;

    $result = true;
   
    if ($result) {
    	notify('Removing table lp_access_rule ...', 'notifysuccess');

    	$sql = "DROP TABLE IF EXISTS `lp_access_rule`";
        $result = $DB->execute($sql);
    }
    
    if ($result) {
    	notify('Removing table lp_access_assignment ...', 'notifysuccess');

    	$sql = "DROP TABLE IF EXISTS `lp_access_assignment`";
        $result = $DB->execute($sql);
    }
    
    return $result;
}
