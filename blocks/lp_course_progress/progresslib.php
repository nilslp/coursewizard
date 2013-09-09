<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

define('LP_COURSE_PROGRESS_SCOREOPTION_NONE', 0);
define('LP_COURSE_PROGRESS_SCOREOPTION_SCORE_ONLY', 1);
define('LP_COURSE_PROGRESS_SCOREOPTION_ALL', 2);

define('LP_DATA_STATUS_NON_ATTEMPT', 0);
define('LP_DATA_STATUS_INCOMPLETE', 1);
define('LP_DATA_STATUS_COMPLETE', 2);

define('LP_DISPLAY_COURSE_TABS_ALL', 0);
define('LP_DISPLAY_COURSE_TABS_SPLIT', 1);
define('LP_DISPLAY_COURSE_TABS_ENROLLED_ONLY', 2);

class module_progress_object {

    static protected $_quiz_score_format = 0;
    
    static protected $_score_separator = 0;
    
    private static $strings = array();
        
    static protected function get_status_string( $status = 0 ) {
        if (empty(self::$strings)) {
            self::$strings[LP_DATA_STATUS_NON_ATTEMPT] = get_string('nonattempted_title','block_lp_course_progress');
            self::$strings[LP_DATA_STATUS_INCOMPLETE] = get_string('incomplete_title','block_lp_course_progress');
            self::$strings[LP_DATA_STATUS_COMPLETE] = get_string('complete_title','block_lp_course_progress');
        }
        
        return self::$strings[$status];
    }
    
    public $type;
    public $data;
    public $name = 'Untitled';
    public $status = '';
    public $data_status = LP_DATA_STATUS_NON_ATTEMPT;
    public $url = '';

    public function __construct($type, $data) {
        $this->type = $type;
        $this->data = $data;
        $this->populate();
    }
    
    public function update_completion_counts( &$counts ) {
        if ($counts && isset($counts[$this->data_status])) {
            $counts[$this->data_status]++;
        }
    }

    protected function populate() {
        global $DB;
        
        if (0 === self::$_quiz_score_format) {
            self::$_quiz_score_format = get_config('block_lp_course_progress', 'scoremessagedisplayoption');        
        }

        if (0 === self::$_score_separator) {
            self::$_score_separator = get_string('scoreseparator', 'block_lp_course_progress');
        }

        if (!isset($this->data) || !isset($this->data['moduleinstance'])) {
            return null;
        }

        $instanceid = $DB->get_field('course_modules', 'instance', array('id' => $this->data['moduleinstance']));
        if (!$instanceid) {
            return null;
        }

        try {
            $records = $DB->get_records($this->type, array('id' => $instanceid));
        } catch (Exception $e) {
            // just ignore unmatched tables
            return null;
        }
        
        if ($records) {
            $module = reset($records);
        }

        if (isset($this->data['status'])) {
            $this->status = $this->data['status'];            
        }
        
        if (isset($this->data['is_complete']) && $this->data['is_complete']) {
            $this->data_status =  LP_DATA_STATUS_COMPLETE;            
        } else {
            $this->data_status = $this->data['modulecompletionstate'] ? LP_DATA_STATUS_INCOMPLETE : LP_DATA_STATUS_NON_ATTEMPT;
        }
        
        $this->name = $module->name;
        $this->url = new moodle_url("/mod/{$this->type}/view.php", array('id' => $this->data['moduleinstance'])); // this may not always work!

        return $module;
    }

    public function get_progress_html( $obj=null ) {
        $output = array();
        
        if (!$obj) {
            $obj = $this;
        }
        
        $output []= html_writer::start_tag('li', array('data-status' => $obj->data_status));

        $output []= html_writer::start_tag('span', array('class' => 'trunc', 'title' => $obj->name));
        if (!empty($obj->url)){
            $output []= html_writer::link($obj->url->out(), $obj->name);
        } else {
            $output []= $obj->name;
        }
        $output []= html_writer::end_tag('span');
        $output []= html_writer::start_tag('div', array('class' => 'meter', 'title' => self::get_status_string($obj->data_status)));

       $output []= html_writer::tag(
               'div',
               $this->get_meter_content_html($obj),
               array('class' => 'level progress', 'style' => 'width: ' . $this->calc_percentage($obj) . '%')
               );
        
        $output []= html_writer::end_tag('div');
        $output []= html_writer::end_tag('li');
        
        return implode('',$output);
    }
    
    public function calc_percentage( $obj = null ) {
        $val = 0;
        
        if (!$obj) {
            $obj = $this;
        }
        
        switch ($obj->data_status) {
            case LP_DATA_STATUS_COMPLETE: 
                $val = 100;
                break;
            case LP_DATA_STATUS_INCOMPLETE: 
                $val = 50;
                break;
            default :
                break;
        }
        
        return $val;
    }
    
    protected function get_meter_content_html( $obj=null ) {
        return '';
    }

}

class module_progress_object_quiz extends module_progress_object {
    
    public $attempt_count = 0;
    
    public $score_raw = 0;
    
    public $score_per = 0;
    
    public $data_grade = 0;
    
    public function populate() {
        global $DB, $USER;
        
        $module = parent::populate();
        
        if (empty($module)) {
            return false;
        }
                        
        // Get the boundaries
        $sql = "SELECT q.id, q.name, q.sumgrades, q.grade, qf.mingrade, qf.maxgrade 
                    FROM {quiz} q
                    INNER JOIN {quiz_feedback} qf ON qf.quizid = q.id 
                    WHERE course = {$module->course} AND q.id = {$module->id} 
                    ORDER BY qf.maxgrade DESC 
                    LIMIT 1";

        $quiz_feedback = $DB->get_records_sql($sql);

        // Get attempts
        $sql = "SELECT sumgrades 
                    FROM {quiz_attempts} 
                    WHERE quiz = {$module->id}  
                        AND userid = {$USER->id} 
                        AND sumgrades IS NOT NULL 
                    ORDER BY sumgrades DESC";

        $attempts = $DB->get_records_sql($sql);

        if ($attempts) {            
            $this->attempt_count = count($attempts);

            $sumgrade = 0;
            $attempt = array_shift($attempts);
            if ($attempt) {
                $sumgrade = $attempt->sumgrades;
            }

            $feedback = array_shift($quiz_feedback);
            if ($feedback && is_object($feedback)) {
                $quiz_feedback = $feedback;
            }

            $score_array = $this->calculate_quiz_score(
                    $quiz_feedback->grade, 
                    $quiz_feedback->maxgrade, 
                    $quiz_feedback->mingrade, 
                    $quiz_feedback->sumgrades, 
                    $sumgrade
                    );

            switch (self::$_quiz_score_format) {
                case LP_COURSE_PROGRESS_SCOREOPTION_NONE:
                    $this->score_raw = '';
                    $this->score_per = '';
                    break;

                case LP_COURSE_PROGRESS_SCOREOPTION_ALL:
                    $this->score_raw = round($sumgrade, 2) . self::$_score_separator . round($quiz_feedback->sumgrades, 2);
                    $this->score_per = round((round($sumgrade, 2) / round($quiz_feedback->sumgrades, 2)) * 100);
                    break;

                default:
                    $this->score_raw = $score_array['score'];
                    $this->score_per = '';
            }

            // $this->status = $score_array['status'];
            $this->data_grade = $score_array['score'] . '|' . $score_array['status'];
            // $this->data_status = LP_DATA_STATUS_COMPLETE;
        } else {
            $this->attempt_count = 0;
            $this->score_raw = '';
            $this->score_per = '';
            $this->status = 'Not Attempted';
            $this->data_grade = '';
            $this->data_status = LP_DATA_STATUS_NON_ATTEMPT;
        }   
        
        $this->status = module_progress_object::get_status_string($this->data_status);
        
        return $module;
    }
    
    public function get_progress_html( $obj=null ) {
        $output = array();
        
        if (empty($this->url)) {
            return '';
        }
        
        $output []= html_writer::start_tag('li', array('class' => 'quiz', 'data-status' => $this->data_status, 'data-grade' => $this->data_grade));
        $output []= html_writer::start_tag('span', array('class' => 'trunc', 'title' => $this->name));
        $output []= html_writer::link($this->url->out(), $this->name);
        $output []= html_writer::end_tag('span');
        $output []= html_writer::start_tag('div', array('class' => 'meter', 'title' => $this->status));
        $output []= html_writer::start_tag('div', array('class' => "level progress $this->status", 'style' => 'width: ' . ((empty($this->status) || 'Not Attempted' == $this->status) ? 0 : 100) . '%'));
        $output []= html_writer::start_tag('div', array('class' => 'grade hover'));
        $output []= empty($this->score_raw) ? 'N/A' : $this->score_raw;
        $output []= html_writer::end_tag('div');
        $output []= html_writer::end_tag('div');
        $output []= html_writer::end_tag('div');
        $output []= html_writer::end_tag('li');
        
        return implode('',$output);
    }
    
    /**
    * Calculates the quiz score
    * @param type $quiz_grade quiz.grade
    * @param type $quiz_max quiz.max
    * @param type $quiz_min quiz.min
    * @param type $quiz_sumgrade quiz.sumgrade
    * @param type $user_sumgrade The user's sumgrade value
    * @return type An array in the format (score, status) Note: 'status' will be 'passed' or 'failed'
    */
    protected function calculate_quiz_score($quiz_grade, $quiz_max, $quiz_min, $quiz_sumgrade, $user_sumgrade) {
        $return_array = array('score' => 0, 'status' => '');

        $score = -1;
        $pass_mark = 0;

        if ($quiz_sumgrade != 0) {
            $score = ($user_sumgrade / $quiz_sumgrade) * 100;
        }

        $return_array['score'] = round($score);

        if ($quiz_grade == 0) {
            if ($quiz_max == 11) {
                $pass_mark = $quiz_min * 10;
            } else {
                $pass_mark = $quiz_min;
            }
        } else {
            $dbl = 100 / $quiz_grade;
            $pass_mark = $quiz_min * $dbl;
        }

        $pass_mark = round($pass_mark);

        $return_array['status'] = ($score >= $pass_mark) ? 'passed' : 'failed';

        return $return_array;
    }
    
}

class module_progress_object_scorm extends module_progress_object {
        
    protected $scoes;
    
    public function update_completion_counts(&$counts) {
        if ($counts && $this->scoes) {
            foreach ($this->scoes as $sco) {
                if (isset($counts[$sco->data_status])) {
                    $counts[$sco->data_status]++;
                }
            }
        }
    }    
    
    public function populate() {
        global $DB, $USER;
             
        $module = parent::populate();
        
        if (empty($module)) {
            return false;
        }
        
        $this->scoes = array();   
        
        // Get the SCO records 
        $sql = "SELECT ss.id, ss.scorm, s.name as 'title', ss.title as 'scotitle' 
                    FROM {scorm_scoes} ss 
                    INNER JOIN {scorm} s ON s.id = ss.scorm
                    WHERE ss.scorm = {$module->id}
                        AND ss.scormtype = 'sco'";

        $records = $DB->get_records_sql($sql);

        if ($records) {
            $sco_title = array_slice($records, 0, 1);
            $sco_title_string = $sco_title[0]->title;
            $scoid = null;

            if (count($records) <= 1) {
                $is_multi = false;
                $scoid = $sco_title[0]->id;
            } else {
                // This is a multi-sco
                $is_multi = true;
            }

            foreach ($records as $sco) {
                // Get the SCO tracking information
                $sql = "SELECT t.id, t.value, t.element 
                            FROM {scorm_scoes_track} t
                            WHERE scoid = $sco->id 
                                AND userId = {$USER->id} 
                                AND (element = 'cmi.core.lesson_status' OR element = 'cmi.core.score.raw' OR element = 'cmi.core.score.max') 
                            ORDER BY element ASC";

                $user_sco_records = $DB->get_records_sql($sql);

                // new sco for list
                $current_sco = new stdClass();
                $current_sco->status = 'not-attempted';
                $current_sco->data_status = LP_DATA_STATUS_NON_ATTEMPT;
                $current_sco->score = '';
                $current_sco->maximum = '';
                $current_sco->display_score = '';

                if ($is_multi) {
                    $current_sco->name = $sco_title_string . ' - ' . $sco->scotitle;
                } else {
                    $current_sco->name = $sco_title_string;
                }
                
                $current_sco->url = $this->link_to_sco($module->id, $scoid);
                $current_sco->percentage = 0;

                if ($user_sco_records) {
                    // Decode the SCO records
                    foreach ($user_sco_records as $user_sco) {
                        switch ($user_sco->element) {
                            case 'cmi.core.lesson_status':
                                $current_sco->status = $user_sco->value;
                                break;

                            case 'cmi.core.score.max':
                                $current_sco->maximum = $user_sco->value;
                                break;

                            case 'cmi.core.score.raw':
                                $current_sco->score = $user_sco->value;
                                break;
                        }

                        switch ($current_sco->status) {
                            case 'incomplete':
                                $current_sco->data_status = LP_DATA_STATUS_INCOMPLETE;
                                break;
                            case 'passed':
                            case 'failed':
                            case 'completed':
                                $current_sco->data_status = LP_DATA_STATUS_COMPLETE;
                                break;

                            default:
                                $current_sco->data_status = LP_DATA_STATUS_NON_ATTEMPT;
                        }

                        if (self::$_quiz_score_format) {
                            switch (self::$_quiz_score_format) {
                                case LP_COURSE_PROGRESS_SCOREOPTION_NONE:
                                    $current_sco->score = '';
                                    break;

                                case LP_COURSE_PROGRESS_SCOREOPTION_ALL:
                                    $current_sco->score = $current_sco->score . self::$_score_separator . $current_sco->maximum;
                                    break;
                            }
                        } else {
                            $current_sco->score = '';
                        }
                    }

                    if (in_array($current_sco->status, array('passed', 'failed', 'completed'))) {
                        if ($current_sco->score != '' && !empty($current_sco->maximum)) {
                            $current_sco->percentage = ($current_sco->score / $current_sco->maximum) * 100;
                        } else {
                            $current_sco->percentage = 100;
                        }
                    } else if ($current_sco->status == 'incomplete') {
                        $current_sco->percentage = 50;
                    }
                } 

                // Add the module to the list of course modules
                $current_sco->status = $this->status;
                $current_sco->data_status = $this->data_status;
                $this->scoes []= $current_sco;
            }
        }
        
        return $module;
    }
    
    public function get_progress_html( $obj = null ) {
        $output = array();
        
        foreach ($this->scoes as $sco) {
            $output []= parent::get_progress_html($sco); 
        }
        
        return implode('',$output);
    }
    
    /**
     * Returns a link to the specified module
     * @param type $scorm Scorm ID
     * @param type $scoid SCO ID
     * @return string URL to module
     */
    protected function link_to_sco($scorm, $scoid = null) {        
        if ($scoid != null) {
            // This is a multi SCO
            return new moodle_url('/mod/scorm/view.php', array('a' => $scorm));
        } else {
            return new moodle_url('/mod/scorm/player.php', array('a' => $scorm, 'scoid' => $scoid));
        }

        return '';
    }
    
}

class module_progress_object_facetoface extends module_progress_object {
    public function populate() {        
        global $DB, $USER;
        
        $module = parent::populate();
        
        if (empty($module)) {
            return false;
        }                
        
        $result = $DB->get_record_sql(
                "SELECT su.id,
                        su.sessionid
                   FROM {facetoface_signups} su
                   JOIN {facetoface_sessions} sess
                     ON sess.id=su.sessionid
                  WHERE su.userid=?
                    AND sess.facetoface=?",
                array($USER->id, $module->id)
                );
        
        if (!empty($result)) {
            $this->url = new moodle_url("/mod/{$this->type}/signup.php", array('s' => $result->sessionid, 'backtoallsessions' => $module->id ));         
        }
        
        return $module;
    }
}