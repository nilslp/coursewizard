<?php

function process_item($r){
    global $CFG, $DB, $course;
    
    $return_string = '';
    
    // Take an individual item in the course and print out the appropriate table row(s)
    $sql = "SELECT {$CFG->prefix}course_modules.module, {$CFG->prefix}course_modules.instance, {$CFG->prefix}course_modules.section, {$CFG->prefix}modules.name 
            FROM {$CFG->prefix}course_modules 
            LEFT JOIN {$CFG->prefix}modules ON {$CFG->prefix}course_modules.module = {$CFG->prefix}modules.id 
            WHERE {$CFG->prefix}course_modules.id = $r";
            
    $mod = $DB->get_record_sql($sql);
    
    // Sometimes resources exist in course_sections long after they have been deleted
    if (!$mod) {
        return;
    }
    
    $sql = "SELECT name 
            FROM " . $CFG->prefix . $mod->name . 
            " WHERE id = {$mod->instance}";
            
    $instance = $DB->get_record_sql($sql);
    $return_string .= "<tr><td valign=\"top\">$r</td><td valign=\"top\">";
    $type = $mod->name;
    
    switch ($type) {
            case 'scorm':
                $sql = "SELECT id, title 
                        FROM {$CFG->prefix}scorm_scoes 
                        WHERE scorm = {$mod->instance} AND scormtype='sco'";
                $scoes = $DB->get_records_sql($sql);
                if (sizeof($scoes) > 1) { 
                    $return_string .= "Multi Section E-learning</td><td valign=\"top\">{$instance->name}</td><td><i>see below</i></td></tr>";                   
                } 
                else {
                    $return_string .= "Single Section E-learning</td><td valign=\"top\">{$instance->name}</td><td><i>see below</i></td></tr>";
                }
                
                $scocount = 1;
                    
                // Dividing rows for the sco inner table
                $return_string .= "<tr><td colspan=\"4\">Section info:</td></tr>";
                foreach ($scoes as $sco) {
                    $return_string .= "<tr class=\"border_row\"><td></td><td valign=\"top\">$scocount</td><td valign=\"top\">{$sco->title}</td><td valign=\"top\">";
                    
                    // Check if sco has a quiz
                    $isQuizSco = false;
                    $sql = "SELECT DISTINCT userid 
                            FROM {$CFG->prefix}scorm_scoes_track 
                            WHERE scormid = {$mod->instance} AND scoid = {$sco->id} AND attempt = 1 AND element IN ('cmi.core.score.max','cmi.core.score.min','cmi.core.score.raw')";
                            
                    if ($quiz = $DB->get_records_sql($sql)) {
                        $isQuizSco = true;
                    }
                    
                    // Not a quiz type sco
                    $sql = "SELECT userid, value 
                            FROM {$CFG->prefix}scorm_scoes_track 
                            WHERE scormid = {$mod->instance} AND scoid = {$sco->id} AND attempt = 1 AND element = 'cmi.core.lesson_status'";

                    if ($tracks = $DB->get_records_sql($sql)) {
                        $userids = array(); $stati = array();
                        
                        foreach ($tracks as $track) {
                            if (!in_array($track->userid,$userids)) {
                                array_push($userids,$track->userid);
                            }
                          
                            if (array_key_exists($track->value, $stati)) {
                                $stati['' . $track->value] = intval($stati['' . $track->value]) + 1;
                            }
                            else {
                                $stati['' . $track->value]=1;
                            }
                        }
                        
                        $return_string .= sizeof($userids) . " user records:";
                                    
                        if (sizeof($stati) > 0) {
                            foreach ($stati as $key=>$val) {
                                $return_string .= "<br /> - $val $key";
                            }
                        }
                        
                        // Print out link to popup
                        $return_string .= "<br /><div style=\"float:right;\"><input class=\"view_users_button\" type=\"button\" mod=\"$type\" sco=\"{$sco->id}\" inst=\"{$mod->instance}\" value=\"View Users\" /></div><div style=\"clear:both;\"></div>";
                    }
                    else {
                        $return_string .= "No user data, safe to delete";
                    }
                    
                    $scocount++;
                    $return_string .= "</td></tr>";
                }
                
                $return_string .= "<tr><td colspan=\"4\" align=\"center\">&nbsp;</td></tr>";
                break;
                
            case 'quiz':
                $return_string .= "Quiz</td><td valign=\"top\">{$instance->name}</td><td valign=\"top\">";
                $sql = "SELECT id, userid, attempt, timestart, timefinish 
                        FROM {$CFG->prefix}quiz_attempts 
                        WHERE quiz = {$mod->instance}";

                if ($attempts = $DB->get_records_sql($sql)) {
                    $userids = array(); $incomplete = 0; $retries = 0; $tsStart = time(); $tsEnd = 0;
                    
                    foreach($attempts as $attempt){
                        if($attempt->timestart==0 || $attempt->timefinish==0){$incomplete++;}
                        if($attempt->attempt>1){$retries++;}
                        if(!in_array($attempt->userid,$userids)){array_push($userids,$attempt->userid);}
                        if($attempt->timestart!=0 && $attempt->timestart<$tsStart){$tsStart=$attempt->timestart;}
                        if($attempt->timefinish!=0 && $attempt->timefinish>$tsEnd){$tsEnd=$attempt->timefinish;}
                    }
                    
                    $dateStart = date("d/m/Y H:i",$tsStart);
                    $dateEnd = date("d/m/Y H:i",$tsEnd);
                    $return_string .= sizeof($userids) . " distinct users attempted this quiz:";
                    $return_string .= "<br /> - A total of " . sizeof($attempts) . " times";
                    $return_string .="<br /> - Including $incomplete incomplete attempts";
                    $return_string .= "<br /> - And $retries retries";
                    $return_string .= "<br /> - Between $dateStart and $dateEnd";
                                  
                    $return_string .= "<br /><div style=\"float:right;\"><input class=\"view_users_button\" type=\"button\" mod=\"$type\" sco=\"\" inst=\"{$mod->instance}\" value=\"View Users\" /></div><div style=\"clear:both;\"></div>";
                }
                else {
                    $return_string .= "No quiz user data, safe to delete";
                }
                
                $return_string .= "</td></tr>";
                break;
                
            case 'questionnaire':
                $return_string .="Questionnaire</td><td valign=\"top\">{$instance->name}</td><td valign=\"top\">";
                    
                $sql = "SELECT qa.id, qa.userid, rid, submitted, complete 
                    FROM {$CFG->prefix}questionnaire_attempts qa
                    LEFT JOIN {$CFG->prefix}questionnaire_response qr ON qa.rid = qr.id 
                    WHERE qid = {$mod->instance} 
                    ORDER BY submitted ASC";
                    
                if ($attempts = $DB->get_records_sql($sql)) {
                    $incomplete = 0; $userids = array(); $tsStart = null; $tsEnd = null;
                    foreach ($attempts as $attempt){
                        if($attempt->complete=='n'){$incomplete++;}
                        if(!$tsStart || $tsStart>$attempt->submitted){$tsStart=$attempt->submitted;}
                        if(!$tsEnd || $tsEnd<$attempt->submitted){$tsEnd=$attempt->submitted;}
                        if(!in_array($attempt->userid,$userids)){array_push($userids,$attempt->userid);}
                    }
                    $dateStart = date("d/m/Y H:i",$tsStart);
                    $dateEnd = date("d/m/Y H:i",$tsEnd);
                    $return_string .= sizeof($userids) . " distinct users:";
                    $return_string .= "<br /> - Attempted this questionnaire " . sizeof($attempts) . " times";
                    $return_string .= "<br /> - Between $dateStart and $dateEnd";
                    $return_string .= "<br /> - $incomplete incomplete submissions";

                    // Print out link to popup
                    $return_string .= "<br /><div style=\"float:right;\"><input class=\"view_users_button\" type=\"button\" mod=\"$type\" sco=\"\" inst=\"{$mod->instance}\" value=\"View Users\" /></div><div style=\"clear:both;\"></div>";
                }
                else {
                    $return_string .= "No user data, safe to delete";
                }

                $return_string .= "<td></tr>";
                break;

            default:
                // Print simple message that item will be deleted
                $return_string .= "$type</td><td valign=\"top\">{$instance->name}</td><td>Will be deleted.</td></tr>";
    } //end switch
    
    return $return_string;
}
