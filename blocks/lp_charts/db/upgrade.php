<?php
/**
 *
 * @global type $CFG
 * @global moodle_database $DB
 * @global type $OUTPUT
 * @param type $oldversion
 * @return boolean 
 */
function xmldb_block_lp_charts_upgrade($oldversion = 0) {
    global $CFG, $DB, $OUTPUT;
    
    $result = true;
    
    if ($oldversion < 2012042400) {
        set_config('deliverytime', 7, 'block_lp_charts');   // 7AM
        set_config('deliveryday', 1, 'block_lp_charts');    // Monday
    }
    
    if ($oldversion < 2012070201) {
    
        $course_enrolments = $DB->get_record('lp_chart', array('shortname' => 'course_enrolments'));
        
        $course_enrolments->sql_query = 'SELECT DATE(FROM_UNIXTIME(base.timestart)) AS enrolment_date, COUNT(base.id) AS total 
                                        FROM vw_lp_course_students base 
                                        WHERE DATE_SUB(CONCAT(CURDATE(), \' 00:00:00\'), INTERVAL $interval) <= DATE(FROM_UNIXTIME(base.timestart)) 
                                        GROUP BY enrolment_date 
                                        ORDER BY enrolment_date';
        
        $DB->update_record('lp_chart', $course_enrolments);
        
        
        $course_completions = $DB->get_record('lp_chart', array('shortname' => 'course_completions'));
        
        $course_completions->sql_query = 'SELECT DATE(FROM_UNIXTIME(base.lngTimeCourseCompleted)) AS completion_date, COUNT(lngAuto) AS total
                                        FROM rpt_course base
                                        WHERE DATE_SUB(CONCAT(CURDATE(), \' 00:00:00\'), INTERVAL $interval) <= DATE(FROM_UNIXTIME(base.lngTimeCourseCompleted))
                                        GROUP BY completion_date 
                                        ORDER BY completion_date';
        
        $DB->update_record('lp_chart', $course_completions);
    }
    
    if ($oldversion < 2012101902) {
        // Reset the charts to demove dependencies on old tables
        // User logins
        $user_logins = $DB->get_record('lp_chart', array('shortname' => 'user_logins'));

        $user_logins->sql_query = 
        'SELECT DATE(FROM_UNIXTIME(time)) AS login_date, 
                COUNT(id) AS total
        FROM ' . $CFG->prefix . 'log
        WHERE DATE_SUB(CONCAT(CURDATE(), \' 00:00:00\'), INTERVAL $interval) <= DATE(FROM_UNIXTIME(time)) 
            AND action = \'login\' 
            AND userid NOT IN (
                SELECT userid
                FROM ' . $CFG->prefix . 'lp_user_hierarchy
                WHERE FIND_IN_SET(hierarchyid, (SELECT value FROM ' . $CFG->prefix . 'config where name = \'block_lp_hierarchy_excluded\'))
                UNION
                SELECT userid
                FROM ' . $CFG->prefix . 'role_assignments
                WHERE roleid IN 
                (
                    SELECT id
                    FROM ' . $CFG->prefix . 'role
                    WHERE shortname IN (\'site_admin\', \'manager\')
                )
                UNION 
                SELECT id 
                FROM ' . $CFG->prefix . 'user 
                WHERE FIND_IN_SET(id, (SELECT value FROM ' . $CFG->prefix . 'config WHERE name = \'siteadmins\'))
        )
        GROUP BY login_date
        ORDER BY login_date ASC';

        $result = $DB->update_record('lp_chart', $user_logins);    
        
        // Course enrollments
        $course_enrolments = $DB->get_record('lp_chart', array('shortname' => 'course_enrolments'));
        
        $course_enrolments->sql_query = 
        'SELECT DATE(FROM_UNIXTIME(e.timestart)) AS enrolment_date, COUNT(e.id) AS total
        FROM ' . $CFG->prefix . 'user_enrolments e
        WHERE DATE_SUB(CONCAT(CURDATE(), \' 00:00:00\'), INTERVAL $interval) <= DATE(FROM_UNIXTIME(e.timestart)) 
            AND userid NOT IN 
            (
                SELECT userid
                FROM ' . $CFG->prefix . 'lp_user_hierarchy
                WHERE FIND_IN_SET(hierarchyid, (SELECT value FROM ' . $CFG->prefix . 'config where name = \'block_lp_hierarchy_excluded\')
            )
        )
        GROUP BY enrolment_date 
        ORDER BY enrolment_date';
        
        $result = $DB->update_record('lp_chart', $course_enrolments);
        
        // Course completions
        $course_completions = $DB->get_record('lp_chart', array('shortname' => 'course_completions'));
        
        $course_completions->sql_query = 
        'SELECT DATE(FROM_UNIXTIME(base.timecompleted)) AS completion_date, COUNT(base.id) AS total
        FROM ' . $CFG->prefix . 'course_completions base
        WHERE DATE_SUB(CONCAT(CURDATE(), \' 00:00:00\'), INTERVAL $interval) <= DATE(FROM_UNIXTIME(base.timecompleted))
        AND userid NOT IN 
        (
            SELECT userid
            FROM ' . $CFG->prefix . 'lp_user_hierarchy
            WHERE FIND_IN_SET(hierarchyid, (SELECT value FROM ' . $CFG->prefix . 'config where name = \'block_lp_hierarchy_excluded\'))
        )
        GROUP BY completion_date
        ORDER BY completion_date';
        
        $result = $DB->update_record('lp_chart', $course_completions);
        
        $most_active_learners = $DB->get_record('lp_chart', array('shortname' => 'most_active_learners'));
        
        $most_active_learners->sql_query = 
        'SELECT CONCAT(mdlu.firstname, \' \', mdlu.lastname) AS learner,
            COUNT(1) AS total
        FROM ' . $CFG->prefix . 'log l
        JOIN ' . $CFG->prefix . 'user mdlu ON mdlu.id = l.userid
        WHERE action = \'login\' 
            AND l.userid NOT IN 
            (
                SELECT userid
                FROM ' . $CFG->prefix . 'lp_user_hierarchy
                WHERE FIND_IN_SET(hierarchyid, (
                    SELECT value
                    FROM ' . $CFG->prefix . 'config
                    WHERE name = \'block_lp_hierarchy_excluded\')
            ) 
            UNION
            SELECT userid
            FROM ' . $CFG->prefix . 'role_assignments
            WHERE roleid IN 
            (
                SELECT id
                FROM ' . $CFG->prefix . 'role
                WHERE shortname = \'site_admin\'
            )
            UNION
            SELECT id
            FROM ' . $CFG->prefix . 'user u
            WHERE u.username = \'guest\')
        GROUP BY l.userid
        ORDER BY total DESC
        LIMIT 0, 5';
        
        $result = $DB->update_record('lp_chart', $most_active_learners );
        
        $most_active_courses = $DB->get_record('lp_chart', array('shortname' => 'most_active_courses'));
       
        $most_active_courses->sql_query = 
        'SELECT mdlc.fullname AS course,
            COUNT(1) AS total
        FROM ' . $CFG->prefix . 'log l
        JOIN ' . $CFG->prefix . 'course mdlc ON mdlc.id = l.course
        WHERE mdlc.id != 1 
            AND l.module = \'course\' 
            AND action = \'view\' 
            AND l.userid NOT IN (
                SELECT userid
                FROM ' . $CFG->prefix . 'lp_user_hierarchy
                WHERE FIND_IN_SET(hierarchyid, (
                    SELECT value
                    FROM ' . $CFG->prefix . 'config
                    WHERE name = \'block_lp_hierarchy_excluded\')
            ) 
            UNION
            SELECT userid
            FROM ' . $CFG->prefix . 'role_assignments
            WHERE roleid IN 
            (
                SELECT id
                FROM ' . $CFG->prefix . 'role
                WHERE shortname = \'site_admin\'
            )
            UNION
            SELECT id
            FROM ' . $CFG->prefix . 'user u
            WHERE u.username = \'guest\'
        )
        GROUP BY mdlc.id
        ORDER BY total DESC
        LIMIT 0, 5';
        
        $result = $DB->update_record('lp_chart', $most_active_courses);
    }
    
    return $result;
}
?>
