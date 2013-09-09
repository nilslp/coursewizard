<?php

function xmldb_block_lp_reportbuilder_install() {
    global $CFG, $DB, $OUTPUT;
    
    $result = true;
    
    if ($result) {
        echo $OUTPUT->notification('Adding vw_lp_course_students view...','notifysuccess');
        
        $sql = "DROP VIEW IF EXISTS vw_lp_course_students";
    
        $result = $DB->execute($sql);

        $sql = "CREATE VIEW vw_lp_course_students
                AS
                SELECT ue.id, 
                    e.courseid, 
                    e.enrol,
                    ue.userid, 
                    ue.timecreated,
                    ue.timestart,
                    ue.timeend,
                    h.hierarchyid, 
                    h.level1 AS `level1`,
                    h.level2 AS `level2`,
                    h.level3 AS `level3`,
                    h.level4 AS `level4`,
                    h.level5 AS `level5`,
                    u.deleted 
                FROM {$CFG->prefix}enrol e
                INNER JOIN {$CFG->prefix}user_enrolments ue ON ue.enrolid = e.id
                INNER JOIN vw_lp_user_hierarchy h ON h.userid = ue.userid
                INNER JOIN {$CFG->prefix}user u ON u.id = ue.userid
                WHERE e.roleid = 5
                    AND ue.timeend = 0";
    
        $result = $DB->execute($sql);
    }
    
   if ($result) {
        echo $OUTPUT->notification('Setting default export options...','notifysuccess');

        // Export CSV and Excel by default
        $result = set_config('exportoptions', 3, 'reportbuilder');
    }
   
    if ($result) {
        // Default the date/time formats
        set_config('defaultdateformat', '%d %b %Y', 'reportbuilder');
        set_config('defaultdatetimeformat', '%d %b %Y at %H:%M', 'reportbuilder');
        set_config('defaulttimeformat', '%H:%M', 'reportbuilder');
    }
    
    return $result;
}
