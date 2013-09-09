<?php

// mask notice type errors that can interfere with headers
error_reporting(E_ALL ^ E_NOTICE);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

global  $CFG,$DB,
        $tabrowcount,$sheets,
        $workbook,$fields,
        $depths,$numtext_format,
        $dateformat,$worksheets;

require_once($CFG->libdir.'/excellib.class.php');
require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/lib.php');

define('COURSE_QUIZ_OFF',0);
define('COURSE_QUIZ_QUIZ',1);
define('COURSE_QUIZ_QUIZSCORM',2);



$tempfilename = md5(time());
$tempfilepathname = $CFG->dataroot . '/' . REPORT_BUILDER_DOWNLOAD_PATH . $tempfilename;
$filename = 'coursequiz_report_' . date('j-M-y');
$redirect = $CFG->wwwroot.'/blocks/lp_reportbuilder/downloadfile.php?f='.$tempfilename.'&filename='.$filename;


// some environment setup
date_default_timezone_set('Europe/London');
raise_memory_limit('512M');
set_time_limit(300);
ob_start();
echo " ";
ob_flush();
flush();

// initialise some vars
$datetime = date('d-M-Y');
$fname = clean_filename('coursequiz_report_' . $datetime . '.xls');
$tabrowcount  = array(2,2,2);
$worksheets = array();
$quickreportcfg = get_config('reportbuilder','showcoursequizquick');
$includemodulename = get_config('reportbuilder','includemodulename');

// get params
$params = array();
$params['courses']  = required_param('courses',PARAM_TEXT);                
$params['tab']      = required_param('tab',PARAM_INT);        
$params['complete'] = optional_param('complete',0,PARAM_INT);   
$params['fields']   = optional_param('fields','',PARAM_ALPHANUMEXT);     
$params['datefrom'] = optional_param('datefrom','',PARAM_ALPHANUMEXT);
$params['dateto']   = optional_param('dateto','',PARAM_ALPHANUMEXT);

function mysql_fetch_rowsarr($result, $numass = MYSQL_ASSOC) {
    $got = array();
    mysql_data_seek($result, 0);
    while ($row = mysql_fetch_array($result, $numass)) {
        $got[$row['userid']] = $row;
    }
    return $got;
}

function merge(&$a, &$b) {
    foreach ($b as $key=>$val) {
        $temp = (array)$val;
        foreach ($temp as $k=>$v){
            $a[$key]->$k = $v;
        }
    }
}

function createSpreadsheetBase(MoodleExcelWorkbook &$workbook,$reportcfg) {
    global $sheets, $fields, $depths, $worksheets, $includemodulename;
        
    foreach ($sheets as $i => $sheetname) {
        $sheet = &$workbook->add_worksheet($sheetname);
        
        $col = 0;
        $sheet->write(0,$col++, 'First Name');
        $sheet->write(0,$col++, 'Last Name');
        $sheet->write(0,$col++, 'Email');
        $sheet->write(0,$col++, 'Username');
        
        foreach ($depths as $depth) {            
            $sheet->write(0,$col++, $depth->fullname);
        }
        
        //custom profile fields
        if ($fields) {
            foreach ($fields as $field) {
                $sheet->write(0,$col++, $field->name);
            }
        }
        
        //standard fields from here but we still need the dynamic column counter
        $sheet->write(0,$col++, "Course");
		if($reportcfg == COURSE_QUIZ_QUIZSCORM){
            if ($includemodulename) {
                $sheet->write(0,$col++, "E-learning Module Name");
            }
	        $sheet->write(0,$col++, "E-learning Module Taken");
	        $sheet->write(0,$col++, "E-learning Module Progress");
	        $sheet->write(0,$col++, "Time Taken (mins)");
	        $sheet->write(0,$col++, "Extra Attempts");
		}
		if($reportcfg > COURSE_QUIZ_OFF){
	        $sheet->write(0,$col++, "Quiz Status");
	        $sheet->write(0,$col++, "Score");
	        $sheet->write(0,$col++, "Quiz Taken");
	        $sheet->write(0,$col++, "Time Taken (mins)");
	        $sheet->write(0,$col++, "Quiz Attempts");
		}
        $worksheets []= &$sheet;
        
    } //end tabs loop
}

function writeToTab(&$workbook, $exceltab, $criteria, &$data,$reportcfg) {
    global $tabrowcount, $fields, $fts, $tts,$depths;
    global $numtext_format, $dateformat,$worksheets, $includemodulename;
    
    $sheet = &$worksheets[$exceltab];

    $row = $tabrowcount[$exceltab];
    
    foreach ($data as $d) {   
        if (!$d->quizinrange && !$d->scorminrange) {
            continue;
        }
        
        if ($d->tab == $criteria) {
            if ($fts && ($criteria != "NotAttempted")) {
                if ((isset($d->modulelastaccess) && $d->modulelastaccess >= $fts && $d->modulelastaccess <= $tts)
                        || (isset($d->quizattemptend) && $d->quizattemptstart >= $fts && $d->quizattemptstart <= $tts)) {
                    $write = true;
                } else {
                    $write = false;
                }
                
            } else {
                $write = true;
            }
            
            if ($write) {
                $col = 0;
                $sheet->write($row,$col++, $d->firstname);
                $sheet->write($row,$col++, $d->lastname);
                $sheet->write($row,$col++, $d->email);
                
                //preserve leading zeroes on usernames
                $sheet->write_string($row,$col++, "{$d->username}", $numtext_format);
                
                foreach ($depths as $depth) {
                    $l = 'level'.$depth->level;
                    $sheet->write($row,$col++,$d->$l);
                }
                
                //custom profile fields
                if ($fields) {
                    foreach ($fields as $field) {
                        $f = 'field_' . $field->id . '_value';
                        $sheet->write($row,$col++, $d->$f);
                    }
                }

				// standard fields from here but we still need the dynamic column counter
	            $sheet->write($row,$col++, $d->coursename);

				if($reportcfg == COURSE_QUIZ_QUIZSCORM){	  
                    if ($includemodulename) {
                        if (!isset($d->modulename)) {
                            $d->modulename = '';
                        }
                        $sheet->write($row,$col++, $d->modulename);
                    }
	                // handle various SCORM date formats
	                if (!isset($d->modulestatus) || $d->modulestatus == 'Not Attempted') {
	                    $sheet->write($row,$col++, "n/a");
	                } else {
	                    $ts = 0;
	                    if ($d->modulestatus == 'Complete') {
	                        $ts = $d->modulecompletetime;
	                    }
	                    if ($d->modulestatus == 'Incomplete') {
	                        $ts = $d->modulelastaccess;
	                    }
	                    if ($ts > 0) {
	                        $sheet->write($row,$col++, ($ts / 86400) + 25569, $dateformat);
	                    } else {
	                        $sheet->write($row,$col++, "");
	                    }
	                }
	                
	                if (!isset($d->modulestatus)) {
	                    $d->modulestatus = 'n/a';
	                }
	                
	                $sheet->write($row,$col++, $d->modulestatus);
	                
	                if (!isset($d->moduletimetaken)) {
	                    $d->moduletimetaken = 'n/a';
	                }
	                $sheet->write($row,$col++, $d->moduletimetaken);
	                
	                if (!isset($d->moduleattempts)) {
	                    $d->moduleattempts = 'n/a';
	                }
	                $sheet->write($row,$col++, $d->moduleattempts);
                }
				if($reportcfg > COURSE_QUIZ_OFF){
					
	                if (!isset($d->quizstatus)) {
	                    $d->quizstatus = 'n/a';
	                }
	                $sheet->write($row,$col++, $d->quizstatus);
	                
	                if (!isset($d->score)) {
	                    $d->score = 'n/a';
	                }
	                $sheet->write($row,$col++, $d->score);
	                
	                //handle various QUIZ date formats
	                if (!isset($d->quizstatus) || $d->quizstatus == 'Not Attempted') {
	                    $sheet->write($row,$col++, "n/a");
	                } else {
	                    $ts = 0;
	                    if ($d->quizstatus == 'Pass') {
	                        $ts = $d->quizattemptend;
	                    }
	                    if ($d->quizstatus == 'Fail') {
	                        $ts = $d->quizattemptstart;
	                    }
	                    if ($ts > 0) {
	                        $sheet->write($row,$col++, ($ts / 86400) + 25569, $dateformat);
	                    } else {
	                        $sheet->write($row,$col++, "n/a");
	                    }
	                }
	                
	                if (!isset($d->quiztimetaken)) {
	                    $d->quiztimetaken = 'n/a';
	                }
	                $sheet->write($row,$col++, $d->quiztimetaken);
	                
	                if (!isset($d->quizattempts)) {
	                    $d->quizattempts = 'n/a';
	                }
	                $sheet->write($row,$col++, $d->quizattempts);
				}
				
                $row++;
            }
        }
        $tabrowcount[$exceltab] = $row;
        echo " ";
        ob_flush();
        flush();        
    }
}

$fts = null;
$fiso = null;
if ($params['datefrom'] != "") {
    $datearr = explode("-", $params['datefrom']);
    $fts = mktime(0, 0, 0, intval($datearr[1]), intval($datearr[0]), intval($datearr[2]));
    $fiso = $datearr[2] . "-" . $datearr[1] . "-" . $datearr[0] . " 00:00:00";
}

$tts = null;
$tiso = null;
if ($params['dateto'] != "") {
    $datearr = explode("-", $params['dateto']);
    $tts = mktime(23, 59, 59, intval($datearr[1]), intval($datearr[0]), intval($datearr[2]));
    $tiso = $datearr[2] . "-" . $datearr[1] . "-" . $datearr[0] . " 23:59:59";
}

switch ($params['tab']) {
    case 0:
        $sheets = array(0 => 'Completed', 1 => 'Incomplete', 2 => 'Not Attempted');
        break;
    case 1:
        $sheets = array(0 => 'Attempted', 1 => 'Not Attempted');
        break;
    case 2:
        $sheets = array(0 => 'Complete', 1 => 'Exclusions');
        break;
    case 3:
        $sheets = array(0 => 'All Data');
        break;
}

if ('' != $params['courses']) {
    $params['courses'] = explode(',', $params['courses']);
}

//get the base user profile fields info
if ('' != $params['fields']) {
    $params['fields'] = explode('-',$params['fields']);
    if (0 !== count($params['fields'])){
        $fields = $DB->get_records_sql('SELECT id,name FROM {user_info_field} WHERE id IN ('.implode(',',$params['fields']).')');
    } 
}

// get depths
$depths = $DB->get_records('lp_hierarchy_depth',null,'level ASC','id,level,fullname');
    
if (!$workbook) {
//    header("Content-Type: application/download\n");
//    header("Content-Disposition: attachment; filename=$fname");
//    header("Expires: 0");
//    header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
//    header("Pragma: public");

    $workbook = new MoodleExcelWorkbook($tempfilepathname);
    //$workbook->send($fname);
    
    createSpreadsheetBase($workbook,$quickreportcfg);
    $dateformat = &$workbook->add_format(array('num_format' => 'dd/mm/yy hh:mm', 'align' => 'left'));
    $numtext_format = &$workbook->add_format(array('num_format' => '@'));
}

// check whether to include deleted users in the report or not
$deleted_clause = '';
$allowdeletedusers = get_config('reportbuilder', 'allowdeletedusers');
if (1 == $allowdeletedusers){
    $deleted_clause = ' AND u.deleted = 0 ';
}

// check for restricted hierarchy 
$hier_clause = '';
if (!empty($CFG->block_lp_hierarchy_excluded)){
    $hier_clause = ' AND luh.hierarchyid NOT IN ('.$CFG->block_lp_hierarchy_excluded.') ';
}

foreach ($params['courses'] as $course) {
    $USERS = null;
    // get course name
    $coursename = $DB->get_field('course','fullname',array('id'=>$course)); 
    $USERS = $DB->get_records_sql(
            "SELECT cc.userid,
                    cc.course,
                    cc.completion_status AS coursecomplete,
                    u.username,
                    u.firstname,
                    u.lastname,
                    u.email,
                    luh.level1,
                    luh.level2,
                    luh.level3,
                    0 AS scorminrange,
                    0 AS quizinrange
                FROM {report_builder_course_comp} cc
                LEFT JOIN {user} u 
                    ON cc.userid=u.id 
                JOIN vw_lp_user_hierarchy luh
                    ON luh.userid=u.id
                WHERE cc.course=? 
                    $deleted_clause
                    $hier_clause
                GROUP BY cc.userid, cc.course 
                ORDER BY u.lastname ASC",
            array($course)
            );
        
    if (0 === count($USERS)) {
        continue;
    }    
        
    // in PHP the load to spin through and merge array is trivial compared to database queries
    // get userid string    
    $userlist = array();
    foreach ($USERS as $u) {
        $userlist []= $u->userid;
    }
    
    $userlist = implode(',',$userlist);
    if ($fields) {
        foreach ($fields as $f) {
            $user_field_values = $DB->get_records_sql( "
                SELECT  userid,
                        fieldid AS field_id_".$f->id.", 
                        data AS field_".$f->id."_value 
                    FROM {user_info_data} 
                    WHERE fieldid=".$f->id." 
                        AND userid IN (".$userlist.")" );
            $user_field_values = array_slice($user_field_values,0);
            merge($USERS, $user_field_values);
        }
    }
                
//     all user data collected, do SCORM
    if($quickreportcfg == COURSE_QUIZ_QUIZSCORM){
			$scorm_data = $DB->get_records_sql(
					"SELECT
						mcs.id,
						mcs.userid,
						mcs.courseid,
						mcs.completionstate,
						CASE mcs.completionstate
							WHEN '-1' THEN 'Not Attempted'
							WHEN '0' THEN 'Incomplete'
							WHEN '3' THEN 'Incomplete'
							ELSE 'Complete'
						END AS modulestatus,                 
                        s.name AS modulename,
						IF(sa.attempt > 0,(sa.attempt-1),0) as moduleattempts,           
						sa.lastaccess AS modulelastaccess,
						sa.totaltime AS moduletimetaken, 
						sa.timecomplete AS modulecompletetime
					FROM
						{report_builder_module_comp} mcs
					JOIN 
						{report_builder_scorm_completions} sa
						ON sa.userid=mcs.userid AND sa.scormid=mcs.moduleinstance
                    JOIN 
                        {scorm} s
                        ON s.id=mcs.moduleinstance
					WHERE mcs.moduletype='scorm'
					AND mcs.courseid=?
					AND mcs.userid IN ({$userlist})
					GROUP BY mcs.userid,mcs.courseid",           
					array($course)
					);
		
			
			// process scorm records to merge multiple scorms
			$processed_scorm = array();
			
			foreach($scorm_data as $data) {
				$uid = $data->userid;
				// present cumulative time in minutes
				if (empty($data->moduletimetaken)) {
					$data->moduletimetaken = 0;
				} else {
					$times = explode(':',$data->moduletimetaken);
					$data->moduletimetaken = (intval($times[0]) * 60) + (intval($times[1])) + ceil(floatval($times[2])/60); // from time record like "00:00:55.22"
				}
				
				// if we already have a scorm record for this user, update that record
				if (isset($processed_scorm[$uid])) {
					$processed_scorm[$uid]->moduletimetaken += $data->moduletimetaken;
					if (intval($processed_scorm[$uid]->modulelastaccess) < intval($data->modulelastaccess)) {
						$processed_scorm[$uid]->modulelastaccess = $data->modulelastaccess;
					}
					
					// all modules complete: Complete
					// all modules not attempted: Not Attempted 
					// any other combination: Incomplete
					if ( ($processed_scorm[$uid]->modulestatus == 'Not Attempted' && intval($data->completionstate) >= 0) ||
						 ($processed_scorm[$uid]->modulestatus == 'Complete' && $data->modulestatus != 'Complete') ) {
						$processed_scorm[$uid]->completionstate = 0;
						$processed_scorm[$uid]->modulestatus = 'Incomplete';
					} 
					
				} else {
					// if time is recorded against a scorm, set status as incomplete
					if ($data->modulestatus == 'Not Attempted' && $data->moduletimetaken > 0) {
						
					}
					
					$processed_scorm[$uid] = $data;
				}
				
				echo " ";
				ob_flush();
				flush();
			}
            
            //parse for out of date range, set flag
			foreach ($processed_scorm as &$sc) {
				if ($fts) {
                    $testtime = ('Complete' == $sc->modulestatus) ? $sc->modulecompletetime : $sc->modulelastaccess;
					if (intval($testtime) >= $fts && intval($testtime) <= $tts) {
						$sc->scorminrange = 1;
					} else {
						$sc->scorminrange = 0;
					}
				} else {
					$sc->scorminrange = 1;
				}
			}			
			
			merge($USERS, $processed_scorm);
	}
	
	if($quickreportcfg > COURSE_QUIZ_OFF){
			$quiz_sql = "SELECT
							mcs.id,
							mcs.userid,
							mcs.courseid,
							mcs.completionstate,
							CASE mcs.completionstate
								WHEN '1' THEN 'Pass'
								WHEN '2' THEN 'Pass'
								WHEN '3' THEN 'Fail'
								ELSE 'Not Complete'
							END AS quizstatus,
							gr.grade AS score,
							gr.grademax AS maxscore,
							qa.attemptstart AS quizattemptstart,
							qa.attemptfinish AS quizattemptend,
							qa.quizattempts
						 FROM
							{report_builder_module_comp} mcs
						 LEFT JOIN (
							 SELECT userid,
									quiz,
									MAX(attempt) AS quizattempts,
									timestart AS attemptstart,
									timefinish AS attemptfinish
							   FROM {quiz_attempts} 
							   GROUP BY userid,quiz
							) qa
							ON qa.userid=mcs.userid AND qa.quiz=mcs.moduleinstance AND mcs.moduletype='quiz'
						 LEFT JOIN (
							 SELECT qg.*,
									gi.gradepass,
									gi.grademax FROM {quiz_grades} AS qg 
							   JOIN {grade_items} gi 
								 ON gi.iteminstance=qg.quiz 
								AND itemmodule='quiz'
							 ) gr
							ON gr.quiz=qa.quiz AND gr.userid=qa.userid
						 WHERE mcs.moduletype='quiz'
						 AND mcs.courseid=?
						 AND mcs.userid IN ({$userlist})";
			
			if ($fts) {
				//add date clause
				$quiz_sql .= " AND (qa.attemptstart >= ? AND qa.attemptfinish <= ?)";
			}
			
			$quiz_data = $DB->get_records_sql($quiz_sql, array($course,$fts,$tts));  
			
			// process quiz records to merge multiple quizzes
			$processed_quiz = array();
			foreach($quiz_data as $data) {
				$uid = $data->userid;
				
				// if we already have a quiz record for this user, update that record
				if (isset($processed_quiz[$uid])) {
					// any other quiz is anything but pass, use its data
					if ( $data->quizstatus != 'Pass' ) {
						$processed_quiz[$uid] = $data;
					} 
					
				} else {
					$processed_quiz[$uid] = $data;
				}
			} 
            
			foreach ($processed_quiz as &$sc) {
				if ($fts) {
					if (intval($sc->quizattemptend) >= $fts && intval($sc->quizattemptend) <= $tts) {
						$sc->quizinrange = 1;
					} else {
						$sc->quizinrange = 0;
					}
				} else {
					$sc->quizinrange = 1;
				}
			}
			
			merge($USERS, $processed_quiz);
	}

    
    // check which tab each row belongs to
    $hasCompletes = false;
    $hasIncompletes = false;
    $hasNA = false;
    foreach ($USERS as &$row) {
        $row->coursename = $coursename;
        $row->quiztimetaken = 0;
        if ($row->coursecomplete == 0) {
            $row->tab = 'NotAttempted';
            $hasNA = true;
            continue;
        }
                
        if ($row->coursecomplete == 2) {
            $row->tab = 'Complete';
            $hasCompletes = true;
        } else {
            $row->tab = 'Incomplete';
            $hasIncompletes = true;
        }
		if(isset($row->quizattemptstart) && !empty($row->quizattemptstart) && isset($row->quizattemptend) && !empty($row->quizattemptend)){
			$row->quiztimetaken =  (($row->quizattemptend- $row->quizattemptstart) / 60); 
		}        		
    }

    //now write the data to the right tab
    switch ($params['tab']) {
        case 0: //3-tab
            if ($hasCompletes) {
                writeToTab($workbook, 0, 'Complete', $USERS,$quickreportcfg);
            }
            if ($hasIncompletes) {
                writeToTab($workbook, 1, 'Incomplete', $USERS,$quickreportcfg);
            }
            if ($hasNA) {
                writeToTab($workbook, 2, 'NotAttempted', $USERS,$quickreportcfg);
            }
            break;
        case 1: //attempted/not attempted
            if ($hasCompletes) {
                writeToTab($workbook, 0, 'Complete', $USERS,$quickreportcfg);
            }
            if ($hasIncompletes) {
                writeToTab($workbook, 0, 'Incomplete', $USERS,$quickreportcfg);
            }
            if ($hasNA) {
                writeToTab($workbook, 1, 'NotAttempted', $USERS,$quickreportcfg);
            }
            break;
        case 2:  //complete/incomplete+NA
            if ($hasCompletes) {
                writeToTab($workbook, 0, 'Complete', $USERS,$quickreportcfg);
            }
            if ($hasIncompletes) {
                writeToTab($workbook, 1, 'Incomplete', $USERS,$quickreportcfg);
            }
            if ($hasNA) {
                writeToTab($workbook, 1, 'NotAttempted', $USERS,$quickreportcfg);
            }
            break;
        case 3:  //single tab
            if ($hasCompletes) {
                writeToTab($workbook, 0, 'Complete', $USERS,$quickreportcfg);
            }
            if ($hasIncompletes) {
                writeToTab($workbook, 0, 'Incomplete', $USERS,$quickreportcfg);
            }
            if ($hasNA) {
                writeToTab($workbook, 0, 'NotAttempted', $USERS,$quickreportcfg);
            }
            break;
    }

    unset($USERS);
} //end courses loop

//start the spreadsheet
ob_end_flush();

$workbook->close();

do_meta_redirect($redirect);
die;
