<?php

function xmldb_block_lp_hierarchy_uninstall() {
    global $CFG, $DB;

    $result = true;
   
    if ($result) {
        notify('Removing vw_lp_hierarchy_all view...','notifysuccess');
        
        $sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_all`";
        $result = $DB->execute($sql); 
    }
    
    if ($reswult) {
        notify('Removing vw_lp_hierarchy_freetext view...','notifysuccess');
        
        $sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_freetext`";
        $result = $DB->execute($sql);    
    }
    
    if ($result) {
    	notify('Removing vw_lp_hierarchy_count view...','notifysuccess');
        
        $sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_count`";
        $result = $DB->execute($sql);    
    }
   
    if ($result) {
    	notify('Removing vw_lp_hierarchy_level5 view...', 'notifysuccess');

    	$sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_level5`";
        $result = $DB->execute($sql);
    }
   
    if ($result) {
    	notify('Removing vw_lp_hierarchy_level4 view...', 'notifysuccess');

    	$sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_level4`";
        $result = $DB->execute($sql);
    }

    if ($result) {
    	notify('Removing vw_lp_hierarchy_level3 view...', 'notifysuccess');

    	$sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_level3`";
        $result = $DB->execute($sql);
    }
   
    if ($result) {
    	notify('Removing vw_lp_hierarchy_level2 view...', 'notifysuccess');

    	$sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_level2`";
        $result = $DB->execute($sql);
    }
    
    if ($result) {
    	notify('Removing vw_lp_hierarchy_level1 view...', 'notifysuccess');

    	$sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_level1`";
        $result = $DB->execute($sql);
    }
   
    // TODO Hierarchy profile fields and category
    
    return $result;
}
