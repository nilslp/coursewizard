<?php
/**
 * This is a script which was ported from the Moodle 1.9 codebase
 * for Bath and North East Somerset (bathnes)
 */
    require_once(dirname(__FILE__) . '/../../config.php');
    require_once($CFG->libdir . '/adminlib.php');
    require_once('../lp_hierarchy/lib.php');

    global $USER, $OUTPUT, $DB, $PAGE;
    
    // Global strings
    global $ALL_CORRECT_STRING, $ALL_FAILED_STRING, $NA_STRING;
    $ALL_CORRECT_STRING = get_string('skillscheckallcorrect', 'block_lp_reportbuilder');
    $ALL_FAILED_STRING = get_string('skillscheckallfailed', 'block_lp_reportbuilder');
    $NA_STRING = get_string('skillschecknotapplicable', 'block_lp_reportbuilder');
    
    $module_titles = '';
    
    $selected_date_param = optional_param('dateselect', '', PARAM_TEXT);
    
    admin_externalpage_setup('skillscheckerreport');
    
    $startmonthconfig = get_config('reportbuilder', 'skillscheckstartmonth');

    $array_dates = array();
            
    $year_now = date('Y');
    $month_now = date('m');

    if ($startmonthconfig) {
        // This config should be in the format MMYYYYY
        $month = intval(substr($startmonthconfig, 0, 2));
        $year = intval(substr($startmonthconfig, 2, 4));
    }
    else {
        // Give the previous 12 months
        $month = $month_now;
        $year = $year_now - 1;
    }
    
    $start_date = new DateTime(sprintf("%s-%s-01", $year, $month));
    $end_date = new DateTime(sprintf("%s-%s-01", $year_now, $month_now));

    $interval = $start_date->diff($end_date);

    $temp_date = $start_date;
    $month_interval = ($interval->y * 12) +  $interval->m;

    $start_date_orig = clone $start_date;

    for ($i = 0; $i <= $month_interval; $i++) {

        $start_date = clone $start_date_orig;
        $interval = 'P' . ($i + 1) . 'M';
        $temp_date = $start_date->add(new DateInterval($interval));
        $array_dates[] = $temp_date;
    }
    
    // Reverse the array so that the most recent dates appear at the top and
    // the current month is selected
    $array_dates = array_reverse($array_dates);

    // Build up an associative array for the SELECT element
    $options = array();
    
    foreach ($array_dates as $date) {
        $value = date_format($date, 'F Y');
        $key = date_format($date, 'Y-m-d H:i:s');

        $options[$key] = $value;
    }
    
    // Set the selected date on the SELECT
    if ($selected_date_param == '') {
        // Default to the first item in the $options array
        $selected = array_shift(array_keys($options));
    }
    else {
        // Persist it from the form POST
        $selected = $selected_date_param;        
    }
    
    $start_range = new DateTime($selected);
    $end_date_object = new DateTime($selected);
    
    $end_range = $end_date_object->add(new DateInterval("P1M"));
    $end_range = $end_range->sub(new DateInterval("PT1S"));
    
    // Convert the DateTime objects to times for use in the SQL
    $start_range = strtotime(date_format($start_range, 'Y-m-d H:i:s'));
    $end_range = strtotime(date_format($end_range, 'Y-m-d H:i:s'));
    
    // Get the SCORM and SCO IDs from the config
    $scormid = get_config('reportbuilder', 'skillscheckscorm');
    $scoid = get_config('reportbuilder', 'skillschecksco');
    
    $sql = "SELECT u.id as 'userid', u.firstname, u.lastname,	
                sst.element, sst.value, sst.timemodified, CONCAT_WS(' / ', level1, level2, level3, level4, level5) AS 'hierarchy' 
            FROM {user} u
            INNER JOIN {scorm_scoes_track} sst ON sst.userid = u.id
            LEFT JOIN {lp_user_hierarchy}  uh ON uh.userid = u.id
            INNER JOIN vw_lp_hierarchy_all ha ON ha.hierarchyid = uh.hierarchyid
            WHERE sst.scormid = $scormid AND sst.scoid = $scoid 
                AND sst.element = 'cmi.core.lesson_status'
                AND sst.value = 'completed'
                AND sst.timemodified BETWEEN $start_range AND $end_range
            ORDER BY u.lastname, u.firstname";
    
    $result = $DB->get_records_sql($sql);
    
    if ($result) {
        foreach ($result as $key=>$row) {
            $sql = "SELECT sst.element, sst.value
                    FROM {scorm_scoes_track} sst
                    WHERE (element = 'cmi.core.total_time' OR element = 'cmi.core.lesson_location') 
                        AND (userid = {$row->userid} AND scormid = $scormid AND scoid = $scoid)";
			 
            $user_result = $DB->get_records_sql($sql);
            
            if ($user_result) {
                // Initialisation
                $time = '';
                $location = '';

                foreach ($user_result as $data) {
                    switch ($data->element) {
                        case 'cmi.core.lesson_location':
                            $location = $data->value;
                            //chop the strings up
                            $arrAt = explode("@", $location);
                            $arrIs = explode("|", $arrAt[1]);
                            $arrLoc = explode("|", $arrAt[2]);
                            unset($arrAt);
		
                            $iCol = $arrIs[0];
		
                            if ($module_titles == '') {
                                $module_titles = join(',',get_performance_title_from_scorm_location($arrIs));
                            }

                            $arrOutput = get_performance_from_scorm_location($arrLoc, $arrIs);

                            $result[$key]->scores = $arrOutput;
                            break;
                        case 'cmi.core.total_time':
                            $time = get_minutes_from_scorm_time($data->value);
                            $result[$key]->time_taken = $time;
                            break;
                    }                    
                }
            }
        }
    }
    echo $OUTPUT->header();

    echo html_writer::tag('h2', get_string('skillscheckerreport', 'block_lp_reportbuilder'));
    
    // Output the form with the date selector
    echo html_writer::start_tag('form', array('action' => '', 'method' => 'post'));
    
    echo html_writer::select($options, 'dateselect', $selected, false);
    echo html_writer::tag('input', '', array('type' => 'submit', 'value' => 'Submit'));
    
    echo html_writer::end_tag('form');
    
    if ($result) {
        $hierarchy = Hierarchy::get_instance();
        
        $tableheader = array(get_string('userfirstname','block_lp_reportbuilder'),
                             get_string('userlastname','block_lp_reportbuilder'),
                             $hierarchy->get_hierarchy_field_label_text(true),
                             get_string('datecompleted','block_lp_reportbuilder'),
                             get_string('timetaken', 'block_lp_reportbuilder'));

        if ($module_titles != '') {
            $modulename_array = split(',', $module_titles);

            foreach ($modulename_array as $modulename) {
                $tableheader[] = $modulename;
            }
        }

        $data = array();

        foreach($result as $row) {        	
            $cells = array();

            $cells[] = new html_table_cell($row->firstname);
            $cells[] = new html_table_cell($row->lastname);
            $cells[] = new html_table_cell($row->hierarchy);
            $cells[] = new html_table_cell(date("d/m/y H:i", $row->timemodified));
            $cells[] = new html_table_cell($row->time_taken);

            foreach ($row->scores as $score) {
                $cells[] = new html_table_cell($score);
            }

            $row = new html_table_row($cells);          

            $data[] = $row;
        }

        // Output the user generated report table
        $reportstable = new html_table();
        $reportstable->summary = '';
        $reportstable->attributes = array('class'=>'berocca');
        $reportstable->head = $tableheader;
        $reportstable->data = $data;

        echo html_writer::table($reportstable);
    }
    else {
        echo html_writer::tag('div', get_string('noresultsfound', 'block_lp_reportbuilder'));
    }
        
    echo $OUTPUT->footer();
    
/**
 *
 * @param type $total_time
 * @return type 
 */
function get_minutes_from_scorm_time($total_time) {     	
    $hours = 0;
    $mins = 0;
    $seconds = 0;
    
    $time_array = explode(":", $total_time);
    $array_length = count($time_array);

    // Check that the hours and/or minutes have been set
    switch ($array_length) {
        case 3:
            $hours = $time_array[0];
            $mins = $time_array[1];
            $seconds = $time_array[2];
            break;
        case 2:
            $mins = $time_array[0];
            $seconds = $time_array[1];
            break;
    }
    
    $time = ($hours * 60) + $mins;
    
    if (ceil($seconds) > 0) {
        $time++;
    } 

    return $time;
}    
      
function get_performance_title_from_scorm_location($arrIs) {
    $return_array = array();
    
    for ($i = $arrIs[0] + 1; $i < count($arrIs); $i++) {
        $return_array[] = $arrIs[$i];
    }
    
    return $return_array;		
}

function get_performance_from_scorm_location($location_array, $arrIs) {
    global $NA_STRING;
    
    $return_array = array();
  
    for ($i = 1; $i <= $arrIs[0]; $i++) {
        $stKey = "arrOut" + $i;
        $return_array[$stKey] = "";
    }
      
    $start = 0;
    
    for ($i = 1; $i <= $arrIs[0]; $i++) {
        $iFail1 = 0;
        $iFail2 = 0;
			
        $stKey = "arrOut" + $i;

        $return_array[$stKey] = get_performance_from_scorm_location_details($location_array, $start, $arrIs[$i], $iFail1);
        $start +=$arrIs[$i];
            
        $i++;
        if ($i > $arrIs[0]) {
            break;
        }
            
        $tmp = get_performance_from_scorm_location_details($location_array, $start, $arrIs[$i], $iFail2);
        $start +=$arrIs[$i];
            
        $stKey = "arrOut" + $i;
        if ($iFail1 > 0 ) {
            $return_array[$stKey] = $NA_STRING; 
        }
        else {
            $return_array[$stKey] = $tmp;
        }       
    }

    return $return_array;
}

function get_performance_from_scorm_location_details($location_array, $start, $length, &$fail) {
    global $ALL_CORRECT_STRING, $ALL_FAILED_STRING, $NA_STRING;
    
    $return_string = "";
    $fail = 0;

    for ($i = $start; $i < ($length + $start); $i++) {
        if ($location_array[$i] < 2){
            $fail++;
        }
        
        if ($location_array[$i] == 2){
            $return_string .= (($i + 1) - $start) . ",";
        }
    }

    if ($fail == 0) {
        $return_string = $ALL_CORRECT_STRING; 
    }
    else {
        if ($return_string == '') {
            $return_string = $ALL_FAILED_STRING;  
        }
        else {
            $return_string = substr($return_string, 0, -1);
        }
    }

    return $return_string;
}
?>
