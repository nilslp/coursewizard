<?php
/***
 * This script takes a content type and instance ID/scoid to display the proper list of users
 * who attempted that section of a course and the status
 * Query string parameters: 
 *  mod     - type (SCORM, quiz, questionnaire)
 *  sco     - for SCORM records
 *  inst    - instance (all of them)
 */
	
    require("../../../config.php");
    require_once("../../../course/lib.php");
    require_once("../../../blocks/lp_hierarchy/lib.php");
    
    $mod        = optional_param('mod', '', PARAM_ALPHA);
    $sco        = optional_param('sco', 0, PARAM_INT);
    $inst       = optional_param('inst', 0, PARAM_INT);
           
    require_login();
    
    $PAGE->set_url('/local/dlelegacytools/course/confirmdeleteuserlist.php');
    $PAGE->set_pagelayout('popup');
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));

    $titlesql = "SELECT name 
                FROM " . $CFG->prefix . $mod . " WHERE id = $inst";
    $titlesql2 = null;
    
    switch ($mod) {
        case 'classroombooker':
            $sql = "SELECT b.id, b.userid, b.booking_status AS status, a.attendance, d.event_from_datetime, u.firstname, u.lastname, CONCAT_WS('/', h.level1, h.level2, h.level3, h.level4, h.level5) AS 'hierarchy' 
                    FROM {$CFG->prefix}crb_event_bookings b 
                    LEFT OUTER JOIN {$CFG->prefix}crb_event_attendance a ON b.id = a.event_booking_id 
                    LEFT JOIN (SELECT event_id, event_from_datetime FROM {$CFG->prefix}crb_event_dates GROUP BY event_id) d  ON b.event_id = d.event_id 
                    INNER JOIN {$CFG->prefix}user u ON u.id = b.userid
                    LEFT JOIN {$CFG->prefix}lp_user_hierarchy uh ON uh.userid = u.id
                    LEFT JOIN vw_lp_hierarchy_all h ON h.hierarchyid = uh.hierarchyid
                    WHERE b.event_id = $inst ";
            break;
        
        case 'scorm':
            // SCO names are held in different location
            $titlesql2 = "SELECT id, title 
                    FROM {$CFG->prefix}scorm_scoes 
                    WHERE scorm = $inst AND id = $sco AND scormtype = 'sco'";

            $sql = "SELECT sst.id, sst.userid, sst.value AS status, sst.timemodified AS ts, u.firstname, u.lastname, CONCAT_WS('/', h.level1, h.level2, h.level3, h.level4, h.level5) AS 'hierarchy' 
                    FROM {scorm_scoes_track} sst
                    INNER JOIN {$CFG->prefix}user u ON u.id = sst.userid
                    LEFT JOIN {$CFG->prefix}lp_user_hierarchy uh ON uh.userid = u.id
                    LEFT JOIN vw_lp_hierarchy_all h ON h.hierarchyid = uh.hierarchyid
                    WHERE scormid = $inst AND scoid = $sco AND element = 'cmi.core.lesson_status'"; 
            break;
        
        case 'quiz':
            $sql = "SELECT qa.id, qa.userid, attempt, timestart, timefinish, u.firstname, u.lastname, CONCAT_WS('/', h.level1, h.level2, h.level3, h.level4, h.level5) AS 'hierarchy'   
                    FROM {$CFG->prefix}quiz_attempts qa
                    INNER JOIN {$CFG->prefix}user u ON u.id = qa.userid
                    LEFT JOIN {$CFG->prefix}lp_user_hierarchy uh ON uh.userid = u.id
                    LEFT JOIN vw_lp_hierarchy_all h ON h.hierarchyid = uh.hierarchyid
                    WHERE quiz = $inst ";
            break;
        
        case 'questionnaire':
            $sql = "SELECT qa.id, qa.userid, rid, submitted AS ts, complete AS status, u.firstname, u.lastname, CONCAT_WS('/', h.level1, h.level2, h.level3, h.level4, h.level5) AS 'hierarchy'  
                    FROM {$CFG->prefix}questionnaire_attempts qa
                    LEFT JOIN {$CFG->prefix}questionnaire_response qr ON qa.rid = qr.id 
                    INNER JOIN {$CFG->prefix}user u ON u.id = qa.userid
                    LEFT JOIN {$CFG->prefix}lp_user_hierarchy uh ON uh.userid = u.id
                    LEFT JOIN vw_lp_hierarchy_all h ON h.hierarchyid = uh.hierarchyid
                    WHERE qid = $inst 
                    ORDER BY submitted ASC ";
            break;
        
        default:
            die("Unsupported module type $mod");
    }
       
    $initialdata = $DB->get_records_sql($sql);
    
    if ($mod == 'classroombooker') {
        foreach ($initialdata as $d) {
            if (empty($d->attendance)) {
                $d->attendance = "n/a";
            }
            $d->status = $d->status . ", attended:" . $d->attendance; 
            $d->ts = strtotime($d->event_from_datetime);
        }
    }
   
    if ($mod == 'questionnaire') {
        foreach ($initialdata as $d) {
            if ($d->status == 'y') {
                $d->status = 'Completed';
            }
            elseif ($d->status=='n') {
                $d->status = 'Incomplete';
            }
            else {
                $d->status = 'Unknown';
            }
        }
    }
    
    // Perform additional processing on quizes
    if ($mod == 'quiz') {
        foreach ($initialdata as $d){
            // Sort out the timestamps and status
            if ($d->timefinish == 0){
                // Incomplete attempt, use start time for date
                $d->ts = $d->timestart;
                $d->status = "Incomplete";
            }
            else {
                // Completed, use end time for data and check for a grade
                $d->ts = $d->timefinish;
                $sql2 = "SELECT grade 
                        FROM {$CFG->prefix}quiz_grades 
                        WHERE userid = {$d->userid} AND quiz = $inst";
                
                if ($grade = $DB->get_record_sql($sql2)) {
                    $d->status="Complete, grade=" . $grade->grade;	
                }
                else {
                    $d->status="Complete, no grade recorded";
                }
            }
        }
    }
    
    // Print a meaningful title on the popup
    $title = $DB->get_record_sql($titlesql);
    
    switch ($mod) {
        case 'scorm':
            $mod = "E-learning module";
            break;
        
        case 'classroombooker':
            $mod = "Classroom Event";
            break;
    }
    
    $titleText = "User records for $mod '{$title->name}'";
    
    if (!empty($titlesql2)) {
        $title2 = $DB->get_record_sql($titlesql2);
        $titleText .= " - section: " . $title2->title;
    }
    
    // Get the strings
    $hierarchy = Hierarchy::get_instance();
    
    $firstname_string = get_string('firstname');
    $lastname_string = get_string('lastname');
    $hierarchy_string = $hierarchy->get_hierarchy_field_label_text(true);
    $status_string = get_string('status', 'local_dlelegacytools');
    $date_string = get_string('date', 'local_dlelegacytools');
    
    // Print the page title
    echo $titleText;
    
    // Configure the table's columns
    $tableheader = array($firstname_string,
                            $lastname_string,
                            $hierarchy_string,
                            $status_string,
                            $date_string);
    
    if ($initialdata) {
        $data_array = array();
        foreach ($initialdata as $data) {       	
            $cells = array();

            $cells[] = $data->firstname;           
            $cells[] = $data->lastname;
            $cells[] = $data->hierarchy;
            $cells[] = $data->status;
            $cells[] =  date("d/m/Y H:i", intval($data->ts));
             
            $row = new html_table_row($cells);          
            
            $data_array[] = $row;
        }
           
        // Output the table
        $table = new html_table();
        $table->summary = '';
        $table->attributes = array('class'=>'berocca');
        $table->head = $tableheader;
        $table->data = $data_array;
        
        echo html_writer::table($table);
    }
?>
