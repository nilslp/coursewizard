<?php

defined('MOODLE_INTERNAL') || die();

class rb_source_scorm extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;

    function __construct() {
        global $CFG;        
        // check hierarchy access
        $hierarchy_clause = '';
//        $temp = reset(explode(',',$CFG->block_lp_hierarchy_excluded));
//        if (!empty($temp)) {
//            $hierarchy_clause .= " AND mcs.hierarchyid NOT IN ($CFG->block_lp_hierarchy_excluded) ";
//        }
        
        if (isset($CFG->block_lp_hierarchy_excluded) && !empty($CFG->block_lp_hierarchy_excluded)) {
            $hierarchy_clause .= " AND mcs.hierarchyid NOT IN ($CFG->block_lp_hierarchy_excluded) ";
        }
        
//        $access = $this->get_accessible_hierarchy_list();
//        $temp = reset(explode(',',$access));
//        if (!empty($temp)) {
//            $hierarchy_clause .= " AND mcs.hierarchyid IN ($access) ";
//        }        
        
        $accessible_hierarchy_list = $this->get_accessible_hierarchy_list();
        
        if (!empty($accessible_hierarchy_list)) {
            $hierarchy_clause .= " AND mcs.hierarchyid IN ($accessible_hierarchy_list) ";
        }
        
        // check deleted config
        $deleted_clause = intval(get_config('reportbuilder', 'allowdeletedusers')) ? '' : ' AND mcs.deleted = 0 ';  
        
        $this->base = "(SELECT
                            mcs.* 
                        FROM
                            {report_builder_module_comp} mcs
                        WHERE mcs.moduletype='scorm'  
                        $hierarchy_clause
                        $deleted_clause)";
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitlescorm', 'block_lp_reportbuilder');

        //Adding custom fields
        /*
         * May enable/add this stuff - DMPH
         * 
         $this->add_custom_user_fields($this->joinlist,
                                      $this->columnoptions,
                                      $this->filteroptions);
        $this->add_custom_position_fields($this->joinlist,
                                          $this->columnoptions,
                                          $this->filteroptions);
        $this->add_custom_organisation_fields($this->joinlist,
                                              $this->columnoptions,
                                              $this->filteroptions);
        $this->add_custom_course_fields($this->joinlist,
                                        $this->columnoptions,
                                        $this->filteroptions);
         * 
         */

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
                'scorm_completion',
                'INNER',
                "{report_builder_scorm_completions}",
                "base.userid = scorm_completion.userid AND scorm_completion.scormid = base.moduleinstance",
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            );
         
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
        
        $joinlist []=
            new rb_join(
                'scorm',
                'INNER',
                '{scorm}',
                'scorm.id = base.moduleinstance',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            );
        
        $joinlist []= 
            new rb_join(
                'sco',
                'INNER',
                '{scorm_scoes}',
                'sco.id = scorm_completion.scoid',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                array (
                    'scorm_completion'
                )               
            );

        // include some standard joins
        $this->add_course_table_to_joinlist($joinlist, 'base', 'courseid');
        // requires the course join
        $this->add_course_category_table_to_joinlist($joinlist,
            'course', 'category');
        $this->add_user_custom_fields_to_joinlist($joinlist, 'base', 'userid');
        /*
         * We may enable this stuff later? -DMPH
         * 
        $this->add_position_tables_to_joinlist($joinlist, 'base', 'userid');
        // requires the position_assignment join
        $this->add_manager_tables_to_joinlist($joinlist,
            'position_assignment', 'reportstoid');
        $this->add_tag_tables_to_joinlist('course', $joinlist, 'scorm', 'course');
        $this->add_cohort_user_tables_to_joinlist($joinlist, 'base', 'userid');
        $this->add_cohort_course_tables_to_joinlist($joinlist, 'scorm', 'course'); 
        
         * 
         */

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
            )*/
           
            new rb_column_option(
                'scorm',
                'title',
                get_string('scormtitle', 'block_lp_reportbuilder'),
                'scorm.name',
                array('joins' => 'scorm')
            ),
            new rb_column_option(
                'sco',
                'starttime',
                get_string('time', 'block_lp_reportbuilder'),
                $DB->sql_cast_char2int('scorm_completion.starttime', true),
                array(
                    'joins' => 'scorm_completion',
                    'displayfunc' => 'nice_datetime',
                )
            ),  
            new rb_column_option(
                'sco',
                'startdate',
                get_string('startdate', 'block_lp_reportbuilder'),
                $DB->sql_cast_char2int('scorm_completion.starttime', true),
                array(
                    'joins' => 'scorm_completion',
                    'displayfunc' => 'nice_date',
                )
            ),    
            new rb_column_option(
                'sco',
                'totaltime',
                get_string('totaltime', 'block_lp_reportbuilder'),
                'scorm_completion.totaltime',
                array(
                    'joins' => 'scorm_completion',
                    'displayfunc' => 'maybe_null'
                )
            ),
            new rb_column_option(
                'sco',
                'totaltimeinmins',
                get_string('totaltimeinmins', 'block_lp_reportbuilder'),
                'scorm_completion.totaltime',
                array(
                    'joins' => 'scorm_completion',
                    'displayfunc' => 'time_in_mins'
                )
            ),
            new rb_column_option(
                'sco',
                'scoreraw',
                get_string('score', 'block_lp_reportbuilder'),
                'scorm_completion.score',
                array(
                    'joins' => 'scorm_completion',
                    'displayfunc' => 'maybe_null'
                )
            ),
            new rb_column_option(
                'sco',
                'attempt',
                get_string('attemptnum', 'block_lp_reportbuilder'),
                'scorm_completion.attempt',
                 array(
                    'joins' => 'scorm_completion',
                    'displayfunc' => 'maybe_null'                     
                 )
            ),
            new rb_column_option(
                'sco', 
                'completionstate', 
                get_string('completionstate', 'block_lp_reportbuilder'), 
                'scorm_completion.status',
                    array(
                        'joins' => 'scorm_completion'
                    )
            ),
//            new rb_column_option(
//                'completion',
//                'completionstate',
//                get_string('completionstate', 'block_lp_reportbuilder'),
//                'base.completionstate',
//                array(
//                    'displayfunc' => 'scorm_completionstate'
//                )
//            ),
            new rb_column_option(
                'completion',
                'completionstatetruefalse',
                get_string('completionstatetruefalse', 'block_lp_reportbuilder'),
                'base.completionstate',
                array(
                    'displayfunc' => 'scorm_completionstate_truefalse'
                )
            ),
            new rb_column_option(
                'completion',
                'laststatusupdate',
                get_string('laststatusupdate', 'block_lp_reportbuilder'),
                'scorm_completion.timecomplete',
                array(
                    'joins' => 'scorm_completion',
                    'displayfunc' => 'nice_datetime'
                )
            ),
            new rb_column_option(
                'completion',
                'laststatusupdate_date',
                get_string('laststatusupdate_date', 'block_lp_reportbuilder'),
                'scorm_completion.timecomplete',
                array(
                    'joins' => 'scorm_completion',
                    'displayfunc' => 'nice_date'
                )
            )
        );

        // include some standard columns
        $this->add_user_fields_to_columns($columnoptions);
        $this->add_user_custom_fields_to_columns($columnoptions);
        $this->add_user_hierarchy_fields_to_columns($columnoptions, 'user_hierarchy');
        $this->add_course_fields_to_columns($columnoptions);
        $this->add_course_category_fields_to_columns($columnoptions);
       
        /*
         * Totara stuff
         * 
        $this->add_position_fields_to_columns($columnoptions);
        $this->add_manager_fields_to_columns($columnoptions);
        $this->add_tag_fields_to_columns('course', $columnoptions);
        $this->add_cohort_user_fields_to_columns($columnoptions);
        $this->add_cohort_course_fields_to_columns($columnoptions);
         * 
         */

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
                'scorm',
                'title',
                get_string('scormtitle', 'block_lp_reportbuilder'),
                'text'
            ),
            new rb_filter_option(
                'sco',
                'starttime',
                get_string('attemptstart', 'block_lp_reportbuilder'),
                'date'
            ),
            new rb_filter_option(
                'sco',
                'attempt',
                get_string('attemptnum', 'block_lp_reportbuilder'),
                'select',
                array('selectfunc' => 'scorm_attempt_list')
            ),
            new rb_filter_option(
                'sco',
                'completionstate',
                get_string('completionstate', 'block_lp_reportbuilder'),
                'select',
                array('selectfunc' => 'scorm_status_list')
            ),
            new rb_filter_option(
                'completion',
                'laststatusupdate',
                get_string('laststatusupdate', 'block_lp_reportbuilder'),
                'date'
            )       
        );

        // include some standard filters
        $this->add_user_fields_to_filters($filteroptions);
        $this->add_hierarchy_fields_to_filters($filteroptions);
        $this->add_course_fields_to_filters($filteroptions);
        $this->add_course_category_fields_to_filters($filteroptions);
        
        /*
         * Totara stuff
         * 
        $this->add_position_fields_to_filters($filteroptions);
        $this->add_manager_fields_to_filters($filteroptions);
        $this->add_tag_fields_to_filters('course', $filteroptions);
        $this->add_cohort_user_fields_to_filters($filteroptions);
        $this->add_cohort_course_fields_to_filters($filteroptions);
         * 
         */

        return $filteroptions;
    }

    protected function define_contentoptions() {
        global $DB;

        $contentoptions = array(
            /*
             * Totara stuff
             * 
            new rb_content_option(
                'current_org',                      // class name
                get_string('currentorg', 'block_lp_reportbuilder'),  // title
                'organisation.path',                // field
                'organisation'                      // joins
            ),
             * 
             */
            new rb_content_option(
                'user',
                get_string('theuser', 'block_lp_reportbuilder'),
                'base.userid'
            ),
            new rb_content_option(
                'date',
                get_string('thedate', 'block_lp_reportbuilder'),
                $DB->sql_cast_char2int('sco_starttime.value', true),
                'sco_starttime'
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
                'type' => 'scorm',
                'value' => 'title',
            ),
            array(
                'type' => 'sco',
                'value' => 'attempt',
            ),
            array(
                'type' => 'sco',
                'value' => 'starttime',
            ),
            array(
                'type' => 'sco',
                'value' => 'totaltime',
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
                'type' => 'sco',
                'value' => 'starttime',
                'advanced' => 1,
            ),
            array(
                'type' => 'sco',
                'value' => 'attempt',
                'advanced' => 1,
            ),
            array(
                'type' => 'sco',
                'value' => 'scoreraw',
                'advanced' => 1,
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

    //
    //
    // Source specific filter display methods
    //
    //

    function rb_filter_scorm_attempt_list() {
        global $DB;

        if (!$max = $DB->get_field_sql('SELECT MAX(attempt) FROM {scorm_scoes_track}')) {
            $max = 10;
        }
        $attemptselect = array();
        foreach( range(1, $max) as $attempt) {
            $attemptselect[$attempt] = $attempt;
        }
        return $attemptselect;
    }
    
    function rb_filter_scorm_status_list() {
        global $CFG;
        require_once($CFG->libdir.'/completionlib.php');
        
        return array(
            '' => get_string('notattempted','block_lp_reportbuilder'),
            'incomplete' => get_string('incomplete', 'block_lp_reportbuilder'),
            'completed' => get_string('complete', 'block_lp_reportbuilder'),
            'passed' => get_string('passed', 'block_lp_reportbuilder'),
            'failed' => get_string('failed', 'block_lp_reportbuilder')
        );
    }
    
    function rb_filter_completion_state_list() {
        global $CFG;
        require_once($CFG->libdir.'/completionlib.php');
        
        return array(
            -1 => get_string('notattempted','block_lp_reportbuilder'),
            COMPLETION_INCOMPLETE => get_string('incomplete', 'block_lp_reportbuilder'),
            COMPLETION_COMPLETE => get_string('complete', 'block_lp_reportbuilder'),
            COMPLETION_COMPLETE_PASS => get_string('passed', 'block_lp_reportbuilder'),
            COMPLETION_COMPLETE_FAIL => get_string('failed', 'block_lp_reportbuilder')
        );
    }

    /*
    function rb_filter_scorm_status_list() {
        global $DB;

        // get all available options
        $records = $DB->get_records_sql("SELECT DISTINCT " .
            $DB->sql_compare_text("value") . " AS value FROM " .
            "{scorm_scoes_track} " .
            "WHERE element = 'cmi.core.lesson_status'");
        if (!empty($records)) {
            $statusselect = array();
            foreach ($records as $record) {
                $statusselect[$record->value] = ucfirst($record->value);
            }
        } else {
            // a default set of options
            $statusselect = array(
                'passed' => get_string('passed', 'block_lp_reportbuilder'),
                'completed' => get_string('completed', 'block_lp_reportbuilder'),
                'not attempted' => get_string('notattempted', 'block_lp_reportbuilder'),
                'incomplete' => get_string('incomplete', 'block_lp_reportbuilder'),
                'failed' => get_string('failed', 'block_lp_reportbuilder')
            );
        }
        return $statusselect;
    }
     * 
     */
    
    /**
     * displays the user completions status hyperlinked to quiz/scorm where applicable
     * 
     * @global type $CFG
     * @param type $item
     * @param type $row
     * @return type 
     */
    function rb_display_scorm_completionstate($item, $row) {   
        global $CFG;
        require_once($CFG->libdir.'/completionlib.php');
        $text = '';
                
        switch (intval($item)) {
            case COMPLETION_INCOMPLETE:
                $text = get_string('incomplete', 'block_lp_reportbuilder');
                break;
            case COMPLETION_COMPLETE:
                $text = get_string('complete', 'block_lp_reportbuilder');
                break;
            case COMPLETION_COMPLETE_PASS:
                $text = get_string('passed', 'block_lp_reportbuilder');
                break;
            case COMPLETION_COMPLETE_FAIL:
                $text = get_string('failed', 'block_lp_reportbuilder');
                break;
            default:
                $text = get_string('notattempted', 'block_lp_reportbuilder');
                break;
        }
          
        return $text;
    }

    function rb_display_scorm_completionstate_truefalse($item, $row) {   
        global $CFG;
        require_once($CFG->libdir.'/completionlib.php');
        $text = '';
                
        switch (intval($item)) {
            case COMPLETION_INCOMPLETE:
            case COMPLETION_COMPLETE_FAIL:
                $text = 'F';
                break;
            
            case COMPLETION_COMPLETE:
            case COMPLETION_COMPLETE_PASS:
                $text = 'T';
                break;
 
            default:
                $text = 'F';
                break;
        }
          
        return $text;
    }

} // end of rb_source_scorm class

