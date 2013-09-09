<?php

function xmldb_block_lp_reportbuilder_uninstall() {
    global $CFG, $DB;
    
    $sql = "DROP VIEW IF EXISTS vw_lp_course_students";
    
    $result = $DB->execute($sql);
    
    return $result;
}