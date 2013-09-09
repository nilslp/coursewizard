<?php

/*
 * Library functions for block_lp_course_progress
 * @copyright Learning Pool 2012
 * @author Brian Quinn
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lp_course_progress
 */
require_once(dirname(__FILE__) . '/email_notification/lib.php');
require_once(dirname(__FILE__) . '/progresslib.php');
require_once(dirname(__FILE__) . '/progresslib.php');

/**
 * cron functionality for the plugin
 *  - sends incomplete course notifications that have been set up
 */
function block_lp_course_progress_cron() {
    global $DB;
    lp_course_progress_trace('lp_course_progress_cron() started at ' . date('H:i:s'));
    try {
        lp_course_progress_process();
    } catch (Exception $e) {
        lp_course_progress_trace('lp_course_progress_cron() failed with an exception:');
        lp_course_progress_trace($e->getMessage());
        lp_course_progress_trace($DB->get_last_error());
    }
    lp_course_progress_trace('lp_course_progress_cron() finished at ' . date('H:i:s'));
}

/**
 * This function does the cron process within the time range according to settings.
 */
function lp_course_progress_process() {
    $manager = new block_lp_course_progress_emailmanager();
    $manager->send_notifications();

    // migrate course notifications if we haven't already done so
    if (!get_config('block_lp_course_progress', 'migrated')) {
        lp_course_progress_migrate_notifications();
    }
}

/**
 * helper function to print our messages consistently
 */
function lp_course_progress_trace($msg) {
    mtrace('lp_course_progress: ' . $msg);
}

function lp_course_progress_migrate_notifications() {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/local/learningpool/utils.php');

    $count = 0;
    $records = $DB->get_records('lp_incomplete_notify');
    $recordcount = count($records);
    foreach ($records as $rec) {
        ++$count;
        lp_course_progress_trace('migrating record ' . $count . ' of ' . $recordcount . '.');
        $insert = new stdClass();
        $insert->description = $rec->desc;
        $insert->startdate = strtotime("{$rec->startdate}");
        $insert->enddate = strtotime("{$rec->enddate}");
        $insert->courses = $rec->courses;
        $insert->message = $rec->message;
        $insert->frequency = $rec->frequency;
        $insert->next = strtotime("{$rec->nextdate}");
        $insert->type = $rec->type;
        $insert->status = $rec->status;

        $DB->insert_record('lp_incomplete_notification', $insert);
    }

    // set config so we don't have to do this again
    set_config('migrated', 1, 'block_lp_course_progress');
}

/**
 * Prints the key information for the top of the My Course Progress page
 * @param type $show_meter true|false indicator of whether the meter should be displayed
 * @param type $show_header true|false indicator of whether the header should be displayed
 * @param type $display_courses An array of courses
 * @param type $show_meter_links true|false indicator of whether the meter should contain hyperlinks
 * @return type A string containing the progress key HTML markup
 */
function print_course_progress_key($show_meter, $show_header, $display_courses = null, $show_meter_links = true) {
    
    $splitcourse = get_config('block_lp_course_progress', 'splitallcourses');
    
    // This array defines the filter options
    $filters = array('incomplete' => get_string('status_incomplete', 'block_lp_course_progress'),
        'not-attempted' => get_string('status_not_attempted', 'block_lp_course_progress'),
        'complete' => get_string('status_complete', 'block_lp_course_progress')
        );
    //'category' => get_string('status_by_category', 'block_lp_course_progress'),
    
    switch ($splitcourse){
        case LP_DISPLAY_COURSE_TABS_ALL :
        case LP_DISPLAY_COURSE_TABS_ENROLLED_ONLY :
            $filters['courses'] = get_string('status_all', 'block_lp_course_progress');
            break;
        case LP_DISPLAY_COURSE_TABS_SPLIT :
            $filters['enrolled'] = get_string('status_enrolled', 'block_lp_course_progress');
            $filters['not-enrolled'] = get_string('status_notenrolled', 'block_lp_course_progress');
            break;
        default:
            break;
    }
        
    $output = '';

    $output .= html_writer::start_tag('div', array('class' => 'cp-header'));

//    if ($show_header) {
//        $output .= html_writer::tag('h2', get_string('blocktitle', 'block_lp_course_progress'));
//    }

    if ($show_meter) {
        $output .= render_course_progress_meter($display_courses, $show_meter_links);
    }

    $output .= html_writer::start_tag('div', array('class' => 'filters'));
    $output .= html_writer::start_tag('ul', array('class' => 'group'));

    reset($filters);

    // Output the filter options
    for ($i = 0; $i < count($filters); $i++) {
        $key = key($filters);

        $output .= html_writer::tag('li', $filters[$key], array('class' => key($filters)));

        next($filters);
    }

    $output .= html_writer::end_tag('ul');
    $output .= html_writer::end_tag('div');
    $output .= html_writer::tag('div','&nbsp;', array('class'=>'vc'));
    $output .= html_writer::end_tag('div');

    return $output;
}



function get_course_progress($userid = 0, &$return_categories = null, &$return_courses = null) {
    global $CFG, $DB, $USER;

    // defines the completion_info class
    require_once($CFG->libdir . '/completionlib.php');

    $display_categories = array();
    $display_courses = array();
  
    if ($userid == 0) {
        $userid = $USER->id;
    }

    $is_category_open = false;
    $override = get_config('block_lp_course_progress','overrideselfcomplete');

    $tab_format = get_config('block_lp_course_progress', 'splitallcourses');
    $enrolled_course_sql = '';
    
    // Get courses that the user is enrolled on
    $sql = "SELECT DISTINCT GROUP_CONCAT(id) AS id 
        FROM {course} 
        WHERE id IN (
            SELECT DISTINCT(instanceid) 
            FROM {context} 
            WHERE instanceid IN (
                SELECT DISTINCT(e.courseid)
                    FROM {user_enrolments} ue
                    JOIN {enrol} e ON e.id = ue.enrolid
                    JOIN {user} u ON u.id = ue.userid
                    WHERE u.id=$userid
            )
        ) 
        OR id IN (
            SELECT course 
            FROM {scorm} 
            WHERE id IN (
                SELECT DISTINCT scormid 
                FROM {scorm_scoes_track} 
                WHERE userid = $userid 
                    AND element = 'cmi.core.lesson_status'
            ) 
            ORDER BY course
        )";

    $enrolled_courses = $DB->get_field_sql($sql);
    
    // filter invalid results
    $enrolled_courses = explode(',', $enrolled_courses);
    $enrolled_courses = implode(',', array_filter($enrolled_courses));

    if (LP_DISPLAY_COURSE_TABS_ENROLLED_ONLY == $tab_format) {
        if (!empty($enrolled_courses)) {
            $enrolled_course_sql = " AND c.id IN ($enrolled_courses) ";
        } else {
            // not enrolled on any courses
            $enrolled_course_sql = ' AND c.id IN (0) ';
        }
    } 
        
    
    // save for later
    $enrolled_courses = explode(',',$enrolled_courses);
    
    // check if we need to filter courses with no course idnumber 
    $filter_no_id = get_config('block_lp_course_progress', 'filternocourseid');    
    $course_filter_sql = '';
    if ($filter_no_id) {
        $course_filter_sql = " AND (c.idnumber IS NOT NULL AND c.idnumber != '') ";
    }

    // Get the courses for this user
    // modified to get full course object for use with completion_info object
    $sql = "SELECT c.*, cc.id AS category_id, cc.name AS category_name, cc.sortorder AS category_sortorder
            FROM {course} c
            INNER JOIN {course_categories} cc ON c.category = cc.id
            WHERE c.visible = 1 
                AND cc.visible = 1 " .
            $enrolled_course_sql .
            $course_filter_sql .
            "ORDER BY cc.sortorder, c.sortorder";

    $courses = $DB->get_records_sql($sql);

    $current_category_name = '';
    $current_category = new stdClass;

    // Iterate over each course
    foreach ($courses as $course) {
        // new completion info object
        $completion = new completion_info($course);

        if ($current_category_name != $course->category_name) {
            $current_category_name = $course->category_name;
            if ($is_category_open) {
                $display_categories[] = $current_category;

                $is_category_open = false;
                $current_category = new stdClass;
            }

            $current_category->id = $course->category_id;
            $current_category->name = $current_category_name;
            $current_category->status = '';
            $current_category->sortorder = $course->category_sortorder;

            // Set the flag to indicate that the tag has been opened
            $is_category_open = true;
        }

        // Initialisers
        $counts = array(
            LP_DATA_STATUS_NON_ATTEMPT => 0,
            LP_DATA_STATUS_INCOMPLETE => 0,
            LP_DATA_STATUS_COMPLETE => 0,
        );
                
        // Define the course object for display
        $current_course = new stdClass;
        $current_course->id = $course->id;
        $current_course->name = $course->fullname;
        $current_course->category_id = $course->category_id;
        $current_course->category_name = $course->category_name;
        $current_course->sortorder = $course->sortorder;
        $current_course->status = 'not-attempted';
        $current_course->data_status = LP_DATA_STATUS_NON_ATTEMPT;
        $current_course->modules = array();
        $current_course->percentage = 0;
        $current_course->user_enrolled = true;
        $current_course->date_completed = false;
        
        // new switch to handle course completion format
        if (COMPLETION_ENABLED == $completion->is_enabled()) {
            populate_course_completion_records($current_course, $completion, $counts, $course, $override);
        } else {
            populate_non_completion_records($current_course,$counts,$course);
        }
        
        // Set the overall course status based on the running totals in 
        // relation to the number of modules in this course
        $count_total = array_sum($counts);
        
        if ($count_total != 0) {
            if (($counts[LP_DATA_STATUS_COMPLETE] / $count_total) == 1) {
                $current_course->status = 'complete';
                $current_course->data_status = LP_DATA_STATUS_COMPLETE;
            } else if (($counts[LP_DATA_STATUS_INCOMPLETE] + $counts[LP_DATA_STATUS_COMPLETE]) > 0) {
                $current_course->status = 'incomplete';
                $current_course->data_status = LP_DATA_STATUS_INCOMPLETE;
            } 
        }
        
        // if completion is enabled and we're complete, override the calculation above
        if (COMPLETION_ENABLED == $completion->is_enabled() && $completion->is_course_complete($USER->id)) {
            $current_course->status = 'complete';
            $current_course->data_status = LP_DATA_STATUS_COMPLETE; 
            $current_course->date_completed = $DB->get_field('course_completions','timecompleted',array('userid'=>$USER->id,'course'=>$current_course->id));
        }

        switch ($current_course->data_status) {
            case LP_DATA_STATUS_COMPLETE:
                $current_course->percentage= 100;
                break;

            case LP_DATA_STATUS_INCOMPLETE:
                $current_course->percentage = round((($counts[LP_DATA_STATUS_COMPLETE] + ($counts[LP_DATA_STATUS_INCOMPLETE]*.5)) / $count_total) * 100);
                break;

            case LP_DATA_STATUS_NON_ATTEMPT:
                $current_course->percentage = 0;
                break;
        }
               
        if (LP_DISPLAY_COURSE_TABS_ENROLLED_ONLY != $tab_format) {
            // check if user is enrolled
            $current_course->user_enrolled = in_array($current_course->id, $enrolled_courses);
            if ($current_course->user_enrolled) {
                $current_course->status .= '  enrolled';
            } else {
                $current_course->status = 'not-enrolled';
            }
        }

        $display_courses[] = $current_course;
    } // end courses loop

    if ($is_category_open) {
        $display_categories[] = $current_category;
        $is_category_open = false;
    }

    // Return the courses and categories
    $return_categories = $display_categories;
    $return_courses = $display_courses;
}

/**
 * Renders a course progress meter
 * @param type $display_courses An array of courses
 * @param type $show_link Indicator if the meter
 * @return type A HTML string containing the markup for a progress meter
 */
function render_course_progress_meter($display_courses, $show_link = true) {
    $complete_count = 0;
    $incomplete_count = 0;
    $non_attempt_count = 0;
    $total = 0;

    // CSS widths
    $complete_width = 0;
    $incomplete_width = 0;
    $non_attempt_width = 0;

    $output = '';

    // Calculate the totals for the progress meter
    foreach ($display_courses as $course) {
        switch ($course->data_status) {
            case LP_DATA_STATUS_NON_ATTEMPT:
                $non_attempt_count++;
                break;

            case LP_DATA_STATUS_INCOMPLETE:
                $incomplete_count++;
                break;

            case LP_DATA_STATUS_COMPLETE:
                $complete_count++;
                break;
        }
    }

    // Calculate the total
    $total = $complete_count + $incomplete_count + $non_attempt_count;

    if ($total == 0) {
        // Initialise when no data is found
        // $complete_width = 33;
        // $incomplete_width = 34;
        // $non_attempt_count = 33;
        $complete_width = 0;
        $incomplete_width = 0;
        $non_attempt_width = 0;
    } else {
        // Derive the CSS width - floor to stop floating point inaccuracy pushing the width over 100%
        $complete_width = floor(($complete_count / $total) * 100);
        $incomplete_width = floor(($incomplete_count / $total) * 100);
        $non_attempt_width = floor(($non_attempt_count / $total) * 100);
        // bias incompletes with the portion remaining from the undercalculation above - ooh, psychology
        $incomplete_width += max(array(0,100 - ($complete_width + $incomplete_width + $non_attempt_width)));
    }

    $output .= html_writer::start_tag('div', array('class' => 'meter'));

    $output .= html_writer::start_tag('div', array('class' => 'progress complete',
                'data-count' => $complete_count,
                'title' => get_string('status_complete', 'block_lp_course_progress'),
                'style' => "width: $complete_width%"));
    if ($show_link) {
        // Hyperlinks should be included
        $output .= html_writer::start_tag('a', array('href' => new moodle_url('/blocks/lp_course_progress/full_course_progress.php')));
        if ($complete_count)
            $output .= $complete_count;
        $output .= html_writer::end_tag('a');
    }
    else {
        // A hyperlink is not required
        if ($complete_count)
            $output .= $complete_count;
    }
    $output .= html_writer::end_tag('div');

    $output .= html_writer::start_tag('div', array('class' => 'progress incomplete',
                'data-count' => $incomplete_count,
                'title' => get_string('status_incomplete', 'block_lp_course_progress'),
                'style' => "width: $incomplete_width%"));
    if ($show_link) {
        $output .= html_writer::start_tag('a');
        if ($incomplete_count)
            $output .= $incomplete_count;
        $output .= html_writer::end_tag('a');
    }
    else {
        if ($incomplete_count)
            $output .= $incomplete_count;
    }
    $output .= html_writer::end_tag('div');

    $output .= html_writer::start_tag('div', array('class' => 'progress not-attempted',
                'data-count' => $non_attempt_count,
                'title' => get_string('status_not_attempted', 'block_lp_course_progress'),
                'style' => "width: $non_attempt_width%"));
    if ($show_link) {
        $output .= html_writer::start_tag('a');
        if ($non_attempt_count)
            $output .= $non_attempt_count;
        $output .= html_writer::end_tag('a');
    }
    else {
        if ($non_attempt_count)
            $output .= $non_attempt_count;
    }
    $output .= html_writer::end_tag('div');

    $output .= html_writer::end_tag('div');

    return $output;
}

/**
 * Prints the UI for course progress, either as a meter or a full list,
 * depending on the $meter_only flag
 * @global type $CFG
 * @param type $userid The specified $USER->id
 * @param type $display_categories An array of course categories
 * @param type $display_courses An array of courses
 * @param type $meter_only true | false to indicate if only the progress meter is required
 * @return type string containing HTML to render the course progress UI
 */
function print_course_progress($userid, $display_categories, $display_courses, $meter_only = false) {
    global $CFG, $OUTPUT;

    // Retrieve the strings
    $incomplete_string = get_string('incomplete_title', 'block_lp_course_progress');
    $complete_string = get_string('complete_title', 'block_lp_course_progress');
    $nonattempt_string = get_string('nonattempted_title', 'block_lp_course_progress');

    $output = '';

    if ($meter_only) {
        // Render the course progress meter showing a count of complete and
        // incomplete courses and ones which have not been attempted
        $incomplete_count = 0;
        $nonattempt_count = 0;
        $complete_count = 0;

        // Work out the totals
        foreach ($display_courses as $course) {
            switch ($course->status) {
                case LP_DATA_STATUS_NON_ATTEMPT:
                    $nonattempt_count++;
                    break;

                case LP_DATA_STATUS_INCOMPLETE:
                    $incomplete_count++;
                    break;

                case LP_DATA_STATUS_COMPLETE:
                    $complete_count++;
                    break;
            }
        }

        // Build the HTML meter
        $output .= html_writer::start_tag('div', array('class' => 'meter'));
        $output .= html_writer::start_tag('a', array('href' => new moodle_url('/blocks/lp_course_progress/full_course_progress.php#complete')));
        $output .= html_writer::start_tag('div', array('title' => get_string('complete_title', 'block_lp_course_progress'), 'data-count' => $complete_count, 'class' => 'progress complete'));
        $output .= $complete_count;
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('a');
        $output .= html_writer::start_tag('a', array('href' => new moodle_url('/blocks/lp_course_progress/full_course_progress.php#incomplete')));
        $output .= html_writer::start_tag('div', array('title' => get_string('incomplete_title', 'block_lp_course_progress'), 'data-count' => $incomplete_count, 'class' => 'progress incomplete'));
        $output .= $incomplete_count;
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('a');
        $output .= html_writer::start_tag('a', array('href' => new moodle_url('/blocks/lp_course_progress/full_course_progress.php#notattempted')));
        $output .= html_writer::start_tag('div', array('title' => get_string('incomplete_title', 'block_lp_course_progress'), 'data-count' => $nonattempt_count, 'class' => 'progress not-attempted'));
        $output .= $nonattempt_count;
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('div');
    } else {
        // Render the courses

        $any_comps = false;
        $any_incomps = false;
        $any_nonattemps = false;

        foreach ($display_courses as $course) {

            switch ($course->data_status) {
                case LP_DATA_STATUS_COMPLETE : $any_comps = true;
                    break;
                case LP_DATA_STATUS_INCOMPLETE : $any_incomps = true;
                    break;
                case LP_DATA_STATUS_NON_ATTEMPT : $any_nonattemps = true;
                    break;
            }
            
            $output .= html_writer::start_tag('dl', array('class' => "group course courses $course->status hide", 'category-id' => $course->category_id, 'sortorder' => $course->sortorder));
            $output .= html_writer::start_tag('dt');
            $output .= html_writer::start_tag('span', array('class' => 'trunc'));
            $output .= html_writer::start_tag('a', array('href' => $CFG->wwwroot . '/course/view.php?id=' . $course->id, 'title' => $course->name));
            $output .= $course->name;
            $output .= html_writer::end_tag('a');
            $output .= html_writer::end_tag('span');

            $course_percentage = $course->percentage . '%';
            
            $datestring = '';
            if (!empty($course->date_completed)) { 
                $datestring = userdate($course->date_completed, '%d %b %Y');
            }

            $output .= html_writer::start_tag('div', array('class' => 'meter', 'title' => $course_percentage . (empty($datestring) ? '' : " Completed on $datestring")));
            $output .= html_writer::start_tag('div', array('class' => 'level', 'style' => 'width: ' . $course_percentage));
            $output .= $datestring;
            $output .= html_writer::end_tag('div');
            $output .= html_writer::end_tag('div');

            $output .= html_writer::start_tag('dd');
            $output .= html_writer::start_tag('ul', array('class' => 'modules'));

            // Output modules
            foreach ($course->modules as $module) {
                $output .= $module->get_progress_html();
            }

            // Close any open tags
            $output .= html_writer::end_tag('ul');
            $output .= html_writer::end_tag('dd');

            $output .= html_writer::end_tag('dt');
            $output .= html_writer::end_tag('dl');
        }

        if (!$any_comps) {
            $output .= html_writer::start_tag('dl', array('class' => 'group course courses complete no-courses hide'));
            // $output .= html_writer::tag('dt', $OUTPUT->header('You have not completed any learning yet.',3));
            $output .= html_writer::tag('dt', get_string('progressnocompletes', 'block_lp_course_progress'));
            $output .= html_writer::end_tag('dl');
        }

        if (!$any_incomps) {
            $output .= html_writer::start_tag('dl', array('class' => 'group course courses incomplete no-courses hide'));
            // $output .= html_writer::tag('dt', $OUTPUT->header('You have no incomplete learning at the moment.',3));
            $output .= html_writer::tag('dt', get_string('progressnoincompletes','block_lp_course_progress'));
            $output .= html_writer::end_tag('dl');
        }

        if (!$any_nonattemps) {
            $output .= html_writer::start_tag('dl', array('class' => 'group course courses not-attempted no-courses hide'));
            // $output .= html_writer::tag('dt', $OUTPUT->header('You have no learning assigned to you yet.',3));
            $output .= html_writer::tag('dt', get_string('progressnononattempts','block_lp_course_progress'));
            $output .= html_writer::end_tag('dl');
        }
    }

    return $output;
}

function populate_course_completion_records(&$courseinfo, completion_info &$info, &$counts, $course, $override = false) {
    global $DB, $USER;
    
    $completions = $info->get_completions($USER->id);
    if (empty($completions)) {
        $counts[LP_DATA_STATUS_NON_ATTEMPT]++;
        return;
    }
    # @BARRY - see here
    foreach ($completions as $completion) {
        $criteria = $completion->get_criteria();
        
        if ($criteria->criteriatype != COMPLETION_CRITERIA_TYPE_ACTIVITY) {
            continue;
        }
        
        $data = array();     
        $data['modulecompletionstate'] = $DB->get_field_sql('SELECT IFNULL(completionstate,1) FROM {course_modules_completion} WHERE userid = ? AND coursemoduleid = ?', array($USER->id, $criteria->moduleinstance));   
        $data['is_complete'] = $completion->is_complete();
        $data['status'] = $completion->get_status();
        $data['moduleinstance'] = $criteria->moduleinstance;
        $data = array_merge($data,$criteria->get_details($completion));      
                
        $type = $criteria->module;
        $classname = 'module_progress_object_'.$type;        
        if ($override && class_exists($classname)) {
            $progressobj = new $classname($type, $data);
        } else if ('facetoface' == $type) {             
            // Feature Request #5929 - I've updated the module progress objects for scorm and quiz to work better with m2 course completion,
            // but I'm not going to rock the boat for now - just going to enable the custom progress meter for facetoface
            $progressobj = new $classname($type, $data);            
        } else {
            $progressobj = new module_progress_object($type, $data);
        }
        
        $progressobj->update_completion_counts($counts);
        $courseinfo->modules []= $progressobj;
    }
}

function populate_non_completion_records(&$courseinfo, &$counts, $course) {    
    global $DB;
    
    $order = ' ORDER BY cm.id ASC ';
    if (!get_config('block_lp_course_progress','persistordering')) {
        $order = ' ORDER BY section DESC ';
    }
    
    $sql = "SELECT cm.id,m.name 
        FROM {course_modules} cm 
        JOIN {modules} m
            ON cm.module=m.id
        WHERE cm.course = ?
            AND m.name IN (?,?)
        $order";
    
    $modules = $DB->get_records_sql($sql,array($course->id,'scorm','quiz'));
    
    foreach ($modules as $mod) {        
        $type = $mod->name;
        $data = array(
            'moduleinstance' => $mod->id
        );
        if ($course->id == 188){
            $i=0;
        }
        $classname = 'module_progress_object_'.$type;
        if (class_exists($classname)) {
           $progressobj = new $classname($type, $data);
        } else {
            $progressobj = new module_progress_object($type, $data);
        }
        
        $progressobj->update_completion_counts($counts);
        $courseinfo->modules []= $progressobj;
    }
}
