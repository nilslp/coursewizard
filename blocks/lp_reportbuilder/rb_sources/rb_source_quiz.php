<?php

defined('MOODLE_INTERNAL') || die();

class rb_source_quiz extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;

    function __construct() {        
        global $CFG;        
        // check hierarchy access
        $hierarchy_clause = ' 1=1 ';
        
        if (isset($CFG->block_lp_hierarchy_excluded) && !empty($CFG->block_lp_hierarchy_excluded)) {
            $hierarchy_clause .= " AND mcs.hierarchyid NOT IN ($CFG->block_lp_hierarchy_excluded) ";
        }
        
        $accessible_hierarchy_list = $this->get_accessible_hierarchy_list();
        
        if (!empty($accessible_hierarchy_list)) {
            $hierarchy_clause .= " AND mcs.hierarchyid IN ($accessible_hierarchy_list) ";
        }
               
        // check deleted config
        $deleted_clause = intval(get_config('reportbuilder', 'allowdeletedusers')) ? '' : ' AND mcs.deleted = 0 ';  
        
        $this->base = "(SELECT
                            mcs.* ,
                            qa.id AS attemptid,
                            qa.uniqueid,                            
                            qa.attempt,
                            qa.sumgrades,
                            qa.timestart AS attemptstart,
                            qa.timefinish AS attemptfinish,
                            qa.timemodified AS attemptmodified
                        FROM
                            {report_builder_module_comp} mcs
                        LEFT JOIN {quiz_attempts} qa
                            ON qa.userid=mcs.userid AND qa.quiz=mcs.moduleinstance AND mcs.moduletype='quiz'
                        WHERE mcs.moduletype='quiz' AND 
                        $hierarchy_clause
                        $deleted_clause)";
    //    $this->base = '{quiz_attempts}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitlequiz', 'block_lp_reportbuilder');

        parent::__construct();
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    protected function define_joinlist() {
        global $CFG;
        $joinlist = array();
               
        $joinlist []= 
            new rb_join(
                'user_hierarchy',
                'INNER',
                "{lp_user_hierarchy}",
                "base.userid = user_hierarchy.userid",
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            );
           
        // include some standard joins
        $this->add_user_table_to_joinlist($joinlist, 'base', 'userid');
        
        $joinlist []= new rb_join(
                'quiz_grades',
                'LEFT',
                "(SELECT qg.*,gi.gradepass,gi.grademax FROM {quiz_grades} AS qg JOIN {grade_items} gi ON gi.iteminstance=qg.quiz AND itemmodule='quiz')",
                'quiz_grades.quiz=base.moduleinstance AND quiz_grades.userid=base.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            );
        
        $joinlist []= 
            new rb_join(
                'quiz',
                'INNER',
                '{quiz}',
                'quiz.id = base.moduleinstance',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            );        

        $this->add_course_table_to_joinlist($joinlist, 'base', 'courseid');
        // requires the course join
        $this->add_course_category_table_to_joinlist($joinlist,
            'course', 'category');
        $this->add_user_custom_fields_to_joinlist($joinlist, 'base', 'userid');
        
        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB;

        $columnoptions = array(
            /*
            // array of rb_column_option objects, e.g:
            new rb_column_option(
                '',         // type
                '',         // value
                '',         // name
                '',         // field
                array()     // options
            )
            */
            new rb_column_option(
                'quiz',
                'title',
                get_string('quiztitle', 'block_lp_reportbuilder'),
                'quiz.name',
                array('joins' => 'quiz')
            ),
            new rb_column_option(
                'quiz',
                'attempt',
                get_string('attemptnum', 'block_lp_reportbuilder'),
                'base.attempt'
            ),
            new rb_column_option(
                'quiz',
                'timestart',
                get_string('timestart', 'block_lp_reportbuilder'),
                'base.attemptstart',
                array(
                    'displayfunc' => 'nice_datetime'
                )
            ),
            new rb_column_option(
                'quiz',
                'timefinish',
                get_string('timefinish', 'block_lp_reportbuilder'),
                'base.attemptfinish',
                array(
                    'displayfunc' => 'nice_datetime'
                )
            ),
            new rb_column_option(
                'quiz',
                'duration',
                get_string('duration', 'block_lp_reportbuilder'),
                'CASE
                    WHEN base.attemptfinish = 0
                    THEN NULL WHEN base.attemptfinish > base.attemptstart
                    THEN base.attemptfinish - base.attemptstart
                    ELSE 0
                END',
                array(
                    'displayfunc' => 'attempt_duration'
                )
            ),
            new rb_column_option(
                'quiz',
                'status',
                get_string('quizstatus', 'block_lp_reportbuilder'),
                "CONCAT(quiz_grades.grade,' | ',quiz_grades.gradepass)",
                array(
                    'joins' => 'quiz_grades',
                    'displayfunc' => 'quiz_status'
                )
            ),
            new rb_column_option(
                'quiz_grades',
                'grade',
                get_string('quizresult', 'block_lp_reportbuilder'),
                "CONCAT(quiz_grades.grade,' | ',quiz_grades.grademax)",
                array(
                    'joins' => array('quiz_grades','quiz'),
                    'displayfunc' => 'quiz_grade'
                )
            ),
            new rb_column_option(
                'quiz_grades',
                'grade_decimal',
                get_string('quizresultdecimal', 'block_lp_reportbuilder'),
                "CONCAT(quiz_grades.grade,' | ',quiz_grades.grademax)",
                array(
                    'joins' => array('quiz_grades','quiz'),
                    'displayfunc' => 'quiz_grade_decimal'
                )
            ),
        );

        // include some standard columns
        $this->add_user_fields_to_columns($columnoptions);
        $this->add_user_custom_fields_to_columns($columnoptions);
        $this->add_user_hierarchy_fields_to_columns($columnoptions, 'user_hierarchy');
        $this->add_course_fields_to_columns($columnoptions);
        $this->add_course_category_fields_to_columns($columnoptions);
       
        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            /*
            // array of rb_filter_option objects, e.g:
            new rb_filter_option(
                '',       // type
                '',       // value
                '',       // label
                '',       // filtertype
                array()   // options
            )
            */
            new rb_filter_option(
                'quiz',
                'title',
                get_string('quiztitle', 'block_lp_reportbuilder'),
                'text'
            ),
            new rb_filter_option(
                'quiz',
                'timefinish',
                get_string('timefinish', 'block_lp_reportbuilder'),
                'date'
            ),
            new rb_filter_option(
                'quiz',
                'timestart',
                get_string('timestart', 'block_lp_reportbuilder'),
                'date'
            )
        );

        // include some standard filters
        $this->add_user_fields_to_filters($filteroptions);
        $this->add_hierarchy_fields_to_filters($filteroptions);
        $this->add_course_fields_to_filters($filteroptions);
        $this->add_course_category_fields_to_filters($filteroptions);
      
        return $filteroptions;
    }

    protected function define_contentoptions() {
        global $DB;

        $contentoptions = array(
            new rb_content_option(
                'user',
                get_string('theuser', 'block_lp_reportbuilder'),
                'base.userid'
            )
        );
        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',       // parameter name
                'base.userid',  // field
                null            // joins
            ),
            new rb_param_option(
                'courseid',
                'base.courseid'
            ),
            new rb_param_option(
                'moduleid',
                'base.moduleid'
            )
        );
        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelink',
            ),
            array(
                'type' => 'quiz',
                'value' => 'title',
            ),
            array(
                'type' => 'quiz',
                'value' => 'score',
            ),
            array(
                'type' => 'quiz',
                'value' => 'status',
            )
        );

        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
            ),
            array(
                'type' => 'user_hierarchy',
                'value' => 'hierarchy',
            )
        );

        return $defaultfilters;
    }

    protected function define_requiredcolumns() {
        $requiredcolumns = array(
            /*
            // array of rb_column objects, e.g:
            new rb_column(
                '',         // type
                '',         // value
                '',         // heading
                '',         // field
                array(),    // options
            )
            */
        );
        return $requiredcolumns;
    }

    //
    //
    // Source specific column display methods
    //
    //

    // add methods here with [name] matching column option displayfunc
    /*
    function rb_display_[name]($item, $row) {
        // variable $item refers to the current item
        // $row is an object containing the whole row
        // which will include any extrafields
        //
        // should return a string containing what should be displayed
    }
    */
    
    function rb_display_quiz_grade($item, $row) {
        static $a = 0;
        if (!empty($item) && ($pieces = explode('|',$item))) {
            if (empty($a)) {
                $a = new stdClass();
            }
            $a->grade = round($pieces[0]);
            $a->max = round($pieces[1]);
            return get_string('gradexoutofy','block_lp_reportbuilder',$a);
        }
            
        return get_string('nullcolumnvalue', 'block_lp_reportbuilder');
    }
    
    function rb_display_quiz_grade_decimal($item, $row) {
        static $a = 0;
        if (!empty($item) && ($pieces = explode('|',$item))) {
            if (empty($a)) {
                $a = new stdClass();
            }
            $a->grade = sprintf("%.2f",$pieces[0]);
            $a->max = sprintf("%.2f",$pieces[1]);
            return get_string('gradexoutofy','block_lp_reportbuilder',$a);
        }
            
        return get_string('nullcolumnvalue', 'block_lp_reportbuilder');
    }
    
    function rb_display_quiz_status($item, $row) {
        if (!empty($item) && ($pieces = explode('|',$item))) {
            if (floatval($pieces[0]) >= floatval($pieces[1])) {
                return get_string('passed', 'block_lp_reportbuilder');
            }
            return get_string('failed', 'block_lp_reportbuilder');
        }
        
        return get_string('notattempted', 'block_lp_reportbuilder');
    }
    
    function rb_display_attempt_duration($item, $row) {      
        if (empty($item)) {
            return get_string('nullcolumnvalue', 'block_lp_reportbuilder');
        }
        
        $days = floor($item/86400);
        $hours = floor(($item%86400)/3600);
        $minutes = ceil($item%3600/60);
        
        return ((empty($days) ? '' : get_string('xdays','block_lp_reportbuilder',$days) . ' ') .
                (empty($days) && empty($hours) ? '' : get_string('xhours','block_lp_reportbuilder',$hours) . ' ')  .
                get_string('xminutes','block_lp_reportbuilder',$minutes)
                );
    }
    
    //
    //
    // Source specific filter display methods
    //
    //

    function rb_filter_quiz_attempt_list() {
        global $DB;

     /*   if (!$max = $DB->get_field_sql('SELECT MAX(attempt) FROM {scorm_scoes_track}')) {
            $max = 10;
        }
        $attemptselect = array();
        foreach( range(1, $max) as $attempt) {
            $attemptselect[$attempt] = $attempt;
        }*/
        return $attemptselect;
    }

} // end of rb_source_quiz class

