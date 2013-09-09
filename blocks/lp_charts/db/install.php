<?php
function xmldb_block_lp_charts_install() {
    global $CFG, $DB, $OUTPUT;

    $result = true;
      
    if ($result) {
    	echo $OUTPUT->notification('Adding user logins chart...','notifysuccess');
 
        // Insert the new profile field to store the hierarchy
        $new_chart = new object();

        $new_chart->shortname = 'user_logins';
        $new_chart->fullname = 'User Logins';
        $new_chart->description = '';
        $new_chart->sql_query = 'SELECT DATE(FROM_UNIXTIME(time)) AS login_date, 
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
        $new_chart->chart_type = 'ColumnChart';
        $new_chart->is_date_based = 1;
        $new_chart->hidden = 0;
        
        $result = $DB->insert_record('lp_chart', $new_chart, false);    
        
        $chart_record = $DB->get_record('lp_chart', array('shortname' => 'user_logins'));
        
        $column_x = new object();
        $column_x->chartid = $chart_record->id;
        $column_x->name = 'login_date';
        $column_x->label = 'Date';
        $column_x->type = 'string';
        $column_x->axis = 'x';
        
        $result = $DB->insert_record('lp_chart_columns', $column_x, false);    

        $column_y = new object();
        $column_y->chartid = $chart_record->id;
        $column_y->name = 'total';
        $column_y->label = 'Logins';
        $column_y->type = 'number';
        $column_y->axis = 'y';
        
        $result = $DB->insert_record('lp_chart_columns', $column_y, false);    
    }
    
    if ($result) {
    	echo $OUTPUT->notification('Adding course enrolments chart...','notifysuccess');
 
        // Insert the new profile field to store the hierarchy
        $new_chart = new object();

        $new_chart->shortname = 'course_enrolments';
        $new_chart->fullname = 'Course Enrolments';
        $new_chart->description = '';
        $new_chart->sql_query = 'SELECT DATE(FROM_UNIXTIME(e.timestart)) AS enrolment_date, COUNT(e.id) AS total
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
        $new_chart->chart_type = 'ColumnChart';
        $new_chart->is_date_based = 1;
        $new_chart->hidden = 0;
        
        $result = $DB->insert_record('lp_chart', $new_chart, false);    
        
        $chart_record = $DB->get_record('lp_chart', array('shortname' => 'course_enrolments'));
        
        $column_x = new object();
        $column_x->chartid = $chart_record->id;
        $column_x->name = 'enrolment_date';
        $column_x->label = 'Date';
        $column_x->type = 'string';
        $column_x->axis = 'x';
        
        $result = $DB->insert_record('lp_chart_columns', $column_x, false);    

        $column_y = new object();
        $column_y->chartid = $chart_record->id;
        $column_y->name = 'total';
        $column_y->label = 'Enrolments';
        $column_y->type = 'number';
        $column_y->axis = 'y';
        
        $result = $DB->insert_record('lp_chart_columns', $column_y, false);    
    }
    
    if ($result) {
    	echo $OUTPUT->notification('Adding course completions chart...','notifysuccess');
 
        // Insert the new profile field to store the hierarchy
        $new_chart = new object();

        $new_chart->shortname = 'course_completions';
        $new_chart->fullname = 'Course Completions';
        $new_chart->description = '';
        $new_chart->sql_query =  'SELECT DATE(FROM_UNIXTIME(base.timecompleted)) AS completion_date, COUNT(base.id) AS total
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
        $new_chart->chart_type = 'ColumnChart';
        $new_chart->is_date_based = 1;
        $new_chart->hidden = 0;
        
        $result = $DB->insert_record('lp_chart', $new_chart, false);    
        
        $chart_record = $DB->get_record('lp_chart', array('shortname' => 'course_completions'));
        
        $column_x = new object();
        $column_x->chartid = $chart_record->id;
        $column_x->name = 'completion_date';
        $column_x->label = 'Date';
        $column_x->type = 'string';
        $column_x->axis = 'x';
        
        $result = $DB->insert_record('lp_chart_columns', $column_x, false);    

        $column_y = new object();
        $column_y->chartid = $chart_record->id;
        $column_y->name = 'total';
        $column_y->label = 'Completions';
        $column_y->type = 'number';
        $column_y->axis = 'y';
        
        $result = $DB->insert_record('lp_chart_columns', $column_y, false);    
    }
    
    if ($result) {
    	echo $OUTPUT->notification('Adding most active learners chart...','notifysuccess');
 
        // Insert the new profile field to store the hierarchy
        $new_chart = new object();

        $new_chart->shortname = 'most_active_learners';
        $new_chart->fullname = 'Most Active Learners';
        $new_chart->description = '';
        $new_chart->sql_query = 'SELECT CONCAT(mdlu.firstname, \' \', mdlu.lastname) AS learner,
            COUNT(1) AS total
        FROM ' . $CFG->prefix . 'log l
        JOIN ' . $CFG->prefix . 'user mdlu ON mdlu.id = l.userid
        WHERE action = \'login\' 
            AND mdlu.deleted = 0 
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
        $new_chart->chart_type = 'PieChart';
        $new_chart->is_date_based = 0;
        $new_chart->hidden = 0;
        
        $result = $DB->insert_record('lp_chart', $new_chart, false);    
        
        $chart_record = $DB->get_record('lp_chart', array('shortname' => 'most_active_learners'));
        
        $column_x = new object();
        $column_x->chartid = $chart_record->id;
        $column_x->name = 'learner';
        $column_x->label = 'Learner';
        $column_x->type = 'string';
        $column_x->axis = 'x';
        
        $result = $DB->insert_record('lp_chart_columns', $column_x, false);    

        $column_y = new object();
        $column_y->chartid = $chart_record->id;
        $column_y->name = 'total';
        $column_y->label = 'Logins';
        $column_y->type = 'number';
        $column_y->axis = 'y';
        
        $result = $DB->insert_record('lp_chart_columns', $column_y, false);    
    }

    if ($result) {
    	echo $OUTPUT->notification('Adding most active courses chart...','notifysuccess');
 
        // Insert the new profile field to store the hierarchy
        $new_chart = new object();

        $new_chart->shortname = 'most_active_courses';
        $new_chart->fullname = 'Most Active Courses';
        $new_chart->description = '';
        $new_chart->sql_query =  'SELECT mdlc.fullname AS course,
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
        $new_chart->chart_type = 'PieChart';
        $new_chart->is_date_based = 0;
        $new_chart->hidden = 0;
        
        $result = $DB->insert_record('lp_chart', $new_chart, false);    
        
        $chart_record = $DB->get_record('lp_chart', array('shortname' => 'most_active_courses'));
        
        $column_x = new object();
        $column_x->chartid = $chart_record->id;
        $column_x->name = 'course';
        $column_x->label = 'Course';
        $column_x->type = 'string';
        $column_x->axis = 'x';
        
        $result = $DB->insert_record('lp_chart_columns', $column_x, false);    

        $column_y = new object();
        $column_y->chartid = $chart_record->id;
        $column_y->name = 'total';
        $column_y->label = 'Logins';
        $column_y->type = 'number';
        $column_y->axis = 'y';
        
        $result = $DB->insert_record('lp_chart_columns', $column_y, false);    
    }

    return $result;
}