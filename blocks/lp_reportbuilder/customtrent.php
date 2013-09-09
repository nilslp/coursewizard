<?php // $Id$
/**
 * This is a custom report download requested by Walsall.
 * It is a re-write of some old custom Moodle 1.9 code they had to produce a Trent
 * export in a specified format, i.e. one quiz and one SCORM result on the 
 * same row.
 * 
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/lib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/report_forms.php');

global $OUTPUT, $CFG, $DB;

$d = optional_param('d', 0, PARAM_INT);

if ($d == 1) {
    $allowdeletedusers = get_config('reportbuilder', 'allowdeletedusers');
    
    $userclause = '';
    
    if (!$allowdeletedusers) {
        $userclause = ' AND u.deleted = 0';
    }
     
    // Re-worked SQL for performance improvment
    $sql = "SELECT u.username AS PER_REF_NO, c.fullname AS TITLE, cc.name AS COURSE_TYPE1, c.idnumber AS COURSE_CODE, comp.timestarted AS START_DATE, quizdata.quiz_score AS QUIZ_SCORE, scormdata.scorm_score AS SCORE, quiz_duration, scorm_duration, comp.timecompleted AS END_DATE 
            FROM {report_builder_module_comp} root
            INNER JOIN {user} u ON u.id = root.userid
            INNER JOIN {course} c ON c.id = root.courseid
            INNER JOIN {course_categories} cc ON c.category = cc.id
            LEFT JOIN {course_completions} comp ON comp.userid = root.userid AND comp.course = root.courseid
            LEFT JOIN (
                SELECT qa.userid, qa.quiz, qg.grade AS quiz_score,
                    CASE qa.timefinish
                        WHEN NULL THEN NULL
                        ELSE CAST(qa.timefinish AS SIGNED) - CAST(qa.timestart AS SIGNED)
                    END AS quiz_duration,
                    qa.timestart, qa.timefinish                                                                                         
                FROM {quiz_attempts} qa 
                LEFT JOIN {quiz_grades} qg ON qg.quiz = qa.quiz 
                GROUP BY qa.userid
                HAVING MAX(qa.attempt) 
            ) quizdata ON quizdata.userid = root.userid AND quizdata.quiz = root.moduleinstance AND root.moduletype = 'quiz'
            LEFT JOIN (
                SELECT sc.userid, sc.scormid, sc.score AS scorm_score, CAST(sc.timecomplete AS UNSIGNED) - CAST(sc.starttime AS UNSIGNED) AS scorm_duration
                FROM {report_builder_scorm_comp} sc 
                GROUP BY sc.userid
                HAVING MIN(sc.starttime) 
            ) scormdata ON scormdata.userid = root.userid AND scormdata.scormid = root.moduleinstance AND root.moduletype = 'scorm' 
            WHERE root.moduletype IN ('quiz', 'scorm') AND root.completionstate > -1
                $userclause
            GROUP BY root.courseid, root.userid
            ORDER BY u.username ";

    $records = $DB->get_recordset_sql($sql);
    
    $count = count($records);
    
    require_once("$CFG->libdir/excellib.class.php");

    // for long reports, keep sessions and php alive
    set_time_limit(300);

    $datetime = date('d-M-Y');

    $filename = clean_filename('Trent.xls');
    $blocksize = 1000;

    $workbook = new MoodleExcelWorkbook("-");

    $workbook->send($filename);
    $worksheet = array();

    $worksheet[0] =& $workbook->add_worksheet('');
    $row = 0;
    $col = 0;
    $dateformat =& $workbook->add_format();
    $dateformat->set_num_format('dd mmm yyyy');
    $datetimeformat =& $workbook->add_format();
    $datetimeformat->set_num_format('dd mmm yyyy h:mm');
    $system_timezone = date_default_timezone_get();
    date_default_timezone_set('UTC');
    date_default_timezone_set($system_timezone);  

    // Output the header
    $worksheet[0]->write($row, 0, 'PER_REF_NO');
    $worksheet[0]->write($row, 1, 'TITLE');
    $worksheet[0]->write($row, 2, 'START_DATE');
    $worksheet[0]->write($row, 3, 'COURSE_TYPE1');
    $worksheet[0]->write($row, 4, 'COURSE_CODE');
    $worksheet[0]->write($row, 5, 'TRAINING_HOURS');
    $worksheet[0]->write($row, 6, 'COMPLETED_I');
    $worksheet[0]->write($row, 7, 'QUIZ_SCORE');
    $worksheet[0]->write($row, 8, 'SCORE');
    $worksheet[0]->write($row, 9, 'END_DATE');
    
    $row++;

    foreach ($records as $record) {
        $quizduration = 0; 
        $scormduration = 0;
        $duration = 0;
        
        $worksheet[0]->write($row, 0, $record->per_ref_no);
        $worksheet[0]->write($row, 1, $record->title);
        
        if ($record->start_date !== '0') {
            $worksheet[0]->write_date($row, 2, $record->start_date, $dateformat);
        }
        else {
            $worksheet[0]->write_blank($row, 2);
        }
        
        $worksheet[0]->write($row, 3, $record->course_type1);
        
        $worksheet[0]->write($row, 4, $record->course_code);
        
        $quizduration = isset($record->quiz_duration) ? $record->quiz_duration : 0;
        $scormduration = isset($record->scorm_duration) ? $record->scorm_duration : 0;
        
        $duration = $quizduration + $scormduration;
        
        if ($duration > 0) {
            $duration = ceil($duration / 60);
        }
        
        $worksheet[0]->write($row, 5, $duration);
       
         if (isset($record->end_date) && $record->end_date !== '0') {
            $worksheet[0]->write($row, 6, 'T');
        }
        else {
            $worksheet[0]->write($row, 6, 'F');
        }
                
        $worksheet[0]->write($row, 7, $record->quiz_score);
        
        $worksheet[0]->write($row, 8, $record->score);
                
        if (isset($record->end_date) && $record->end_date !== '0') {
            $worksheet[0]->write_date($row, 9, $record->end_date, $dateformat);
        }
        else {
            $worksheet[0]->write_blank($row, 9);
        }
        
        $row++;
    }
        
    $workbook->close();
    die();
}

admin_externalpage_setup('customtrentdownload');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('customtrentdownload','block_lp_reportbuilder'));

echo  get_string('customtrentdownloadblurb', 'block_lp_reportbuilder');

echo '<br />';

echo $OUTPUT->single_button($CFG->wwwroot.'/blocks/lp_reportbuilder/customtrent.php?d=1', get_string('downloadreport','block_lp_reportbuilder'));

echo $OUTPUT->footer();
?>
