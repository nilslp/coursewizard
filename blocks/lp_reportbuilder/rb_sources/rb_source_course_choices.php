<?php

defined('MOODLE_INTERNAL') || die();

class rb_source_course_choices extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;
    public $hidedeleted;
    public $hierarchyfilterlist;

    function __construct() {
        global $CFG;
        
        // This source is a special case it is a view and the base must 
        // be a sub-select if a specific hierarchy is to be excluded
        $hierarchy_where_clause = '';
        $deleted_where_clause = '';
        
        if (get_config('reportbuilder', 'allowdeletedusers')) {
            $this->base = $CFG->prefix . 'choice_answers';
        }
        else {
            $this->base = "(SELECT a.* FROM {$CFG->prefix}choice_answers a INNER JOIN {$CFG->prefix}user u on u.id = a.userid WHERE deleted = 0)";
        }
        
        if (isset($CFG->block_lp_hierarchy_excluded)) {
            $hierarchy_where_clause = ($CFG->block_lp_hierarchy_excluded == '')? 
                $hierarchy_where_clause : 
                $hierarchy_where_clause . " WHERE hierarchyid NOT IN ($CFG->block_lp_hierarchy_excluded)";
        }        
        
        $accessible_hierarchy_list = $this->get_accessible_hierarchy_list();
        
        if ($accessible_hierarchy_list != '') {
            if ($hierarchy_where_clause != '') {
                $hierarchy_where_clause .= " AND hierarchyid IN ($accessible_hierarchy_list)";
            }
            else {
                $hierarchy_where_clause .= " WHERE hierarchyid IN ($accessible_hierarchy_list)";
            }
        }
        
        // Set the base source accordingly
        if ($hierarchy_where_clause == '') {
            if (get_config('reportbuilder', 'allowdeletedusers')) {
                $this->base = $CFG->prefix . 'choice_answers';          
            }
            else {
                $this->base = '(SELECT a.* FROM {choice_answers} a INNER JOIN vw_lp_course_students u on u.id = a.userid WHERE deleted = 0)';
            }
        }
        else {
            if (get_config('reportbuilder', 'allowdeletedusers')) {
                $this->base = "(SELECT a.* FROM {choice_answers} a INNER JOIN vw_lp_course_students u on u.id = a.userid $hierarchy_where_clause)";            
            }
            else {
                $this->base = "(SELECT a.* FROM {choice_answers} a INNER JOIN vw_lp_course_students u on u.id = a.userid $hierarchy_where_clause AND deleted = 0)";                
            }
        }
        
        //$this->base = $CFG->prefix . 'choice_answers';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitlecoursechoices', 'block_lp_reportbuilder');
        parent::__construct();
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    function define_joinlist() {
        global $CFG;
        
        $joinlist = array(
            new rb_join(
                'course_user',
                'LEFT',            
                $CFG->prefix . 'user',
                'course_user.id = base.userid',
                REPORT_BUILDER_RELATION_MANY_TO_MANY,
                    array('base', 'user_enrolments')
            ),
            new rb_join(
                'choice_options',
                'LEFT',            
                $CFG->prefix . 'choice_options',
                'choice_options.choiceid = base.choiceid AND choice_options.id = base.optionid',
                REPORT_BUILDER_RELATION_MANY_TO_MANY,
                    array('base')
            ),
            new rb_join(
                'user_enrolments',
                'LEFT',            
                $CFG->prefix . 'user_enrolments',
                'user_enrolments.userid = base.userid',
                REPORT_BUILDER_RELATION_MANY_TO_MANY,
                array('base')
            ),
            new rb_join(
                'enrol',
                'LEFT',            
                $CFG->prefix . 'enrol',
                'enrol.id = user_enrolments.enrolid',
                REPORT_BUILDER_RELATION_MANY_TO_MANY,
                    array('user_enrolments')
            ),
            new rb_join(
                'user_hierarchy',
                'INNER',            
                'vw_lp_user_hierarchy',
                'user_hierarchy.userid = base.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'course_modules',
                'INNER',            
                $CFG->prefix . 'course_modules',
                'course_modules.course = base.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'course',
                'LEFT',            
                $CFG->prefix . 'course',
                'course.id = enrol.courseid',
                REPORT_BUILDER_RELATION_MANY_TO_MANY,
                    array('enrol')
            ),
            new rb_join(
                'choice',
                'INNER',            
                $CFG->prefix . 'choice',
                'choice.id = base.choiceid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                array('base')
            )
        );

        // Include the course category
        $this->add_course_category_table_to_joinlist($joinlist,
            'course', 'category');

        return $joinlist;
    }

    function define_columnoptions() {
        // Include standard course and course categories columns
        $this->add_course_fields_to_columns($columnoptions, 'course');
        $this->add_course_category_fields_to_columns($columnoptions, 'course_category', 'course');
        $this->add_user_fields_to_columns($columnoptions, 'course_user');
        $this->add_user_hierarchy_fields_to_columns($columnoptions, 'user_hierarchy');
        $columnoptions[] = new rb_column_option(
            'choice_options',
            'choiceresponse', 
            get_string('choice_resp', 'block_lp_reportbuilder'),//name to show for option
            "choice_options.text",//data field
            array('joins' => 'choice_options')
        );
        $columnoptions[] = new rb_column_option(
            'choice_options',
            'choiceresponse', 
            get_string('choice_resp', 'block_lp_reportbuilder'),//name to show for option
            "choice_options.text",//data field
            array('joins' => 'choice_options')
        );
        
        $columnoptions[] = new rb_column_option(
            'choice',
            'choicename',  
            get_string('choice_name', 'block_lp_reportbuilder'),//name to show for option
            "choice.name",//data field
            array('joins' => 'choice')
        ); 
        
        $columnoptions[] = new rb_column_option(
            'choice',
            'choiceresponsedate',  
            get_string('choiceresponsedate', 'block_lp_reportbuilder'),//name to show for option
            "base.timemodified",//data field
            array(
                'joins' => 'base',
                'displayfunc' => 'nice_date'
            )
        ); 
        
        $columnoptions[] = new rb_column_option(
            'choice',
            'choiceresponsedatetime',  
            get_string('choiceresponsedatetime', 'block_lp_reportbuilder'),//name to show for option
            "base.timemodified",//data field
            array(
                'joins' => 'base',
                'displayfunc' => 'nice_datetime'
            )
        ); 
        return $columnoptions;
    }

    function define_filteroptions() {
        // Include some standard filters
        //$this->add_course_fields_to_filters($filteroptions, 'base', 'id');
        $this->add_course_category_fields_to_filters($filteroptions, 'course', 'category');
        $this->add_user_fields_to_filters($filteroptions);
       
        $filteroptions[] = new rb_filter_option(
            'choice',
            'choiceresponsedate',
            get_string('choiceresponsedate','block_lp_reportbuilder'),
            'date');
       
        $filteroptions[] = new rb_filter_option(
            'choice',
            'choiceresponsedatetime',
            get_string('choiceresponsedatetime','block_lp_reportbuilder'),
            'date');
        
       $filteroptions[] = new rb_filter_option(
            'course',    //alias for join
            'fullname',       //alias for data field
            get_string('coursename', 'block_lp_reportbuilder'), 
            'select',
            array(
                'selectfunc' => 'course_list',
                'selectoptions' => rb_filter_option::select_width_limiter(),
            )
        );
        $filteroptions[] = new rb_filter_option(
            'course',
            'shortname',
            get_string('courseshortname', 'block_lp_reportbuilder'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'course',
            'idnumber',
            get_string('courseidnumber', 'block_lp_reportbuilder'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'course',
            'startdate',
            get_string('coursestartdate', 'block_lp_reportbuilder'),
            'date'
        );
        $filteroptions[] = new rb_filter_option(
            'course',
            'name_and_summary',
            get_string('coursenameandsummary', 'block_lp_reportbuilder'),
            'textarea'
        );
        $filteroptions[] = new rb_filter_option(
            'choice',    //alias for join
            'choicename',       //alias for data field
            get_string('choice_name', 'block_lp_reportbuilder'), 
            'select',
            array(
                'selectfunc' => 'choice_name_list',
                'selectoptions' => rb_filter_option::select_width_limiter(),
            )
        );
        $filteroptions[] = new rb_filter_option(
            'choice_options',  
            'choiceresponse',     
            get_string('choice_resp', 'block_lp_reportbuilder'), 
            'select',
            array(
                'selectfunc' => 'choice_answer_list',
                'selectoptions' => rb_filter_option::select_width_limiter(),
            )
        );
        global $CFG;
        require_once $CFG->dirroot . '/blocks/lp_hierarchy/lib.php';
        
        $hierarchy = Hierarchy::get_instance();
        
        $hierarchylabel = $hierarchy->get_hierarchy_field_label_text(true);
        if ((int)get_config('block_lp_reportbuilder', 'usesimplehierarchyselect')) {
            $filteroptions[] = new rb_filter_option(
                'user_hierarchy',
                'hierarchyid',
                $hierarchylabel, 
                'simple_hierarchy_select', 
                array(                	       
                   // 'selectfunc' => 'hierarchy_list'
                )
            );
        } else {        
            $filteroptions[] = new rb_filter_option(
                'user_hierarchy', 
                'hierarchyid', 
                $hierarchylabel, 
                'org', 
                array(                	       
                    'selectfunc' => 'hierarchy_list'
                )
            );
        }
        return $filteroptions;
    }

    function define_contentoptions() {
        $contentoptions = array(

            new rb_content_option(
                'date',
                'Start date', //get_string('startdate', 'rb_source_courses'),
                'base.startdate'
            ),
        );
        return $contentoptions;
    }

    function define_paramoptions() {
        $paramoptions = array(
//            new rb_param_option(
//                'courseid',
//                'base.id'
//            ),
//            new rb_param_option(
//                'visible',
//                'base.visible'
//            ),
//            new rb_param_option(
//                'category',
//                'base.category'
//            ),
        );

        return $paramoptions;
    }

    function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' =>'user_hierarchy',
                'value'=>'hierarchy',
            ),
            array(
                'type' => 'user',
                'value' => 'fullname',
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
            ),array(
                'type' => 'choice',
                'value' => 'choicename',
            ),
            array(
                'type' => 'choice_options',
                'value' => 'choiceresponse',
            ),
        );
        return $defaultcolumns;
    }

    function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' =>'user_hierarchy',
                'value'=>'hierarchyid',
                'advanced'=>0,
            ),
            array(
                'type' => 'course',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'course_category',
                'value' => 'id',
                'advanced' => 0,
            ),
            array(
                'type' => 'choice_options',
                'value' => 'choiceresponse',
                'advanced' => 0,
            ),
            array(
                'type' => 'choice',
                'value' => 'choicename',
                'advanced' => 0,
            ),
        );

        return $defaultfilters;
    }

    function define_requiredcolumns() {
        $requiredcolumns = array(
            /*
            // array of rb_column objects, e.g:
            new rb_column(
                '',         // type
                '',         // value
                '',         // heading
                '',         // field
                array()     // options
            )
            */
        );
        return $requiredcolumns;
    }

    function rb_display_format_nullable_number($item, $row) {
        global $CFG, $OUTPUT;
            
        if($item === null) {
            // No value found, return zero
            return '0';
        } 
        else {
            // Return the number
            return $item;
        } 
    }
    
    function rb_display_format_time_as_minutes($item, $row) {
        global $CFG, $OUTPUT;
            
        if($item === null) {
            // No value found, return an empty string
            return '-';
        } 
        else {
            // Format the number to 2 decimal places
            return number_format($item, 2, '.', ',');
        } 
    }
    
    function rb_display_link_course_users($item, $row) {   	
    	global $CFG;

        if ($item === null) {
            return '0';
    	}
    	else {
            return '<a href="' . $CFG->wwwroot . 
                    '/blocks/lp_reportbuilder/reports/users_enrolled.php?autosubmit=1&courseid=' . $row->id . 
                    ($this->hidedeleted == true ? '&d=0' : '' ) .
                    (isset($this->hierarchyfilterlist) ? '&hier=' . $this->hierarchyfilterlist : '') .
                    '" title="'. get_string('tooltiplinkenrolments', 'block_lp_reportbuilder') . '">' . $item . '</a>';
    	}
    }
    
    function rb_display_link_course_incompletions($item, $row) {
        global $CFG;
        
        if ($item === null) {
            return '0';
        }
        else {
            return '<a href="' . $CFG->wwwroot . 
                    '/blocks/lp_reportbuilder/reports/course_incompletions.php?autosubmit=1&courseid=' . $row->id . 
                    ($this->hidedeleted == true ? '&d=0' : '' ) .
                    (isset($this->hierarchyfilterlist) ? '&hier=' . $this->hierarchyfilterlist : '') .
                    '" title="' . get_string('tooltiplinkincomplete', 'block_lp_reportbuilder') . '">' . $item . '</a>';
        }
    }
    
    function rb_display_link_course_completions($item, $row) {
        global $CFG;
        
        if ($item === null) {
            return '0';
        }
        else {
            return '<a href="' . $CFG->wwwroot . 
                    '/blocks/lp_reportbuilder/reports/course_completions.php?autosubmit=1&courseid=' . $row->id . 
                    ($this->hidedeleted == true ? '&d=0' : '' ) .
                    (isset($this->hierarchyfilterlist) ? '&hier=' . $this->hierarchyfilterlist : '') .
                    '" title="' . get_string('tooltiplinkcompletions', 'block_lp_reportbuilder') . '">' . $item . '</a>';
        }
    }
    
    function rb_display_link_course_nonattempts($item, $row) {
        global $CFG;
        
        if ($item === null) {
            return '0';
        }
        else {
            return '<a href="' . $CFG->wwwroot . 
                    '/blocks/lp_reportbuilder/reports/course_nonattempts.php?autosubmit=1&courseid=' . $row->id .
                    ($this->hidedeleted == true ? '&d=0' : '' ) .
                    (isset($this->hierarchyfilterlist) ? '&hier=' . $this->hierarchyfilterlist : '') .
                    '" title="' . get_string('tooltiplinknonattempts', 'block_lp_reportbuilder') . '">' . $item . '</a>';
        }
    }
} // end of rb_source_course_choices class

