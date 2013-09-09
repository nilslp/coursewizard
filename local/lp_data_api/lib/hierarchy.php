<?php
/**
 *Data mapping for hierarchy to be used by the lp_datamap plugin 
 * 
 */

function lp_dm_hier($map,$user,$val){
    global $DB, $CFG;
    
    include_once($CFG->dirroot . '/blocks/lp_hierarchy/lib.php');
    
    $hierarchy = Hierarchy::get_instance();
    $tval = split($map->_cfg->seperator,$val);
    
    
    
    
}

?>
