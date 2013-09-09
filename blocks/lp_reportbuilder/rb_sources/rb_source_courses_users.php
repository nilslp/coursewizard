<?php

defined('MOODLE_INTERNAL') || die();

class rb_source_courses_users extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;
    public $extrawhereclause;

    function __construct() {
        global $CFG;
        $extrawhere = array();
        
        // This source is a special case it is a view and the base must 
        // be a sub-select if a specific hierarchy is to be excluded
        // For performance reasons the previous derived tables have been replaced
        // through using the 'extrawhereclause' property
        if (!get_config('reportbuilder', 'allowdeletedusers')) {
            $extrawhere[] = 'base.deleted = 0';
        }
        
        if (isset($CFG->block_lp_hierarchy_excluded) && !empty($CFG->block_lp_hierarchy_excluded)) {
            $extrawhere[] = "base.hierarchyid NOT IN ($CFG->block_lp_hierarchy_excluded)";
        }        
        
        $accessible_hierarchy_list = $this->get_accessible_hierarchy_list();
        
        if (!empty($accessible_hierarchy_list)) {
            $extrawhere[] = "base.hierarchyid IN ($accessible_hierarchy_list)";
        }
        
        if (count($extrawhere) > 0) {
            $this->base = 'vw_lp_course_students';
            $this->extrawhereclause = $extrawhere;
        }
        else {
            $this->base = 'vw_lp_course_students';
        }

        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->requiredfilters = $this->define_requiredfilters();
        $this->sourcetitle = get_string('sourcetitlecourseusers', 'block_lp_reportbuilder');
        
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
                'course_user_stats',
                'LEFT',
                $CFG->prefix . 'report_builder_course_comp',
                'course_user_stats.userid = base.userid AND course_user_stats.course = base.courseid',
                REPORT_BUILDER_RELATION_MANY_TO_MANY,
                array('base')
            ),
            new rb_join(
                'course',
                'INNER',            
                $CFG->prefix . 'course',
                'course.id = base.courseid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                    array('course_user_stats')
            ),
            new rb_join(
                'course_user',
                'INNER',
                $CFG->prefix . 'user',
                'course_user.id = base.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'user_hierarchy',
                'LEFT',
                $CFG->prefix . 'lp_user_hierarchy',
                'user_hierarchy.userid = course_user.id',
                REPORT_BUILDER_RELATION_MANY_TO_MANY,
                array('course_user')
            ),
        );

        // Include the course category
        $this->add_course_category_table_to_joinlist($joinlist,
            'course', 'category');

        $this->add_user_custom_fields_to_joinlist($joinlist, 'base', 'userid');
        
        return $joinlist;
    }

    function define_columnoptions() {
        // Include some standard columns     
        $columnoptions = array();
        $this->add_user_fields_to_columns($columnoptions, 'course_user');
        $this->add_user_custom_fields_to_columns($columnoptions);
        $this->add_user_hierarchy_fields_to_columns($columnoptions, 'user_hierarchy');
        $this->add_course_fields_to_columns($columnoptions, 'course');
        $this->add_course_user_fields_to_columns($columnoptions, 'course_user_stats');
        $this->add_course_category_fields_to_columns($columnoptions, 'course_category');
        
        $columnoptions[] = new rb_column_option(
            'course_user',
            'dateenrolled',
            get_string('dateenrolled', 'block_lp_reportbuilder'),
            'base.timecreated',
            array(
                'joins' => 'base',
                'displayfunc' => 'nice_date',
                'defaultheading' => get_string('dateenrolled', 'block_lp_reportbuilder')
            )
        );
        
        $columnoptions[] = new rb_column_option(
            'course',
            'enrolmentstart',
            get_string('enrolmentstart', 'block_lp_reportbuilder'),
            'base.timestart',
            array(
                'joins' => 'base',
                'displayfunc' => 'nice_date',
                'defaultheading' => get_string('enrolmentstart', 'block_lp_reportbuilder')
            )
        );
        
        $columnoptions[] = new rb_column_option(
            'course',
            'enrolmentend',
            get_string('enrolmentend', 'block_lp_reportbuilder'),
            'base.timeend',
            array(
                'joins' => 'base',
                'displayfunc' => 'nice_date',
                'defaultheading' => get_string('enrolmentend', 'block_lp_reportbuilder')
            )
        );
        
        $columnoptions[] = new rb_column_option(
            'course',
            'enrol',
            get_string('enrollmenttype', 'block_lp_reportbuilder'),
            'base.enrol'
        );
           
        return $columnoptions;
    }

    function define_filteroptions() {
        global $CFG;
        require_once $CFG->dirroot . '/blocks/lp_hierarchy/lib.php';
        
        $hierarchy = Hierarchy::get_instance();
        
        $hierarchylabel = $hierarchy->get_hierarchy_field_label_text(true);
        
        // Include some standard filters
        $this->add_course_fields_to_filters($filteroptions, 'course', 'id');
        $this->add_course_category_fields_to_filters($filteroptions, 'category', 'id');
        $this->add_course_user_fields_to_filters($filteroptions);
        $this->add_user_fields_to_filters($filteroptions);
        
        $filteroptions[] = new rb_filter_option(
            'course_user',
            'coursefirstaccessed',
            get_string('coursefirstaccessed','block_lp_reportbuilder'),
            'date');
        
        $filteroptions[] = new rb_filter_option(
            'course_user',
            'courselastaccessed',
            get_string('courselastaccessed', 'block_lp_reportbuilder'),
            'date');

        $filteroptions[] = new rb_filter_option(
            'course_user',
            'coursecompletiondate',
            get_string('coursecompletiondate', 'block_lp_reportbuilder'),
            'date');

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
        
        
         $filteroptions[] = new rb_filter_option(
            'course',
            'enrolmentstart',
            get_string('enrolmentdate', 'block_lp_reportbuilder'),
            'date');

        $filteroptions[] = new rb_filter_option(
            'course_user',
            'dateenrolled',
            get_string('dateenrolled', 'block_lp_reportbuilder'),
            'date');
        
        return $filteroptions;
    }

    function define_contentoptions() {
        $contentoptions = array(
            new rb_content_option(
                'date',
                get_string('enrolmentstart', 'block_lp_reportbuilder'),
                'base.timestart'
            ),
            // issue with adding multiple date content options - see rb_ex_date_content in classes/rb_base_content.php DMPH
            new rb_content_option(
                'ex_date',
                get_string('coursecompletiondate', 'block_lp_reportbuilder'),
                'course_user_stats.timecompleted'
            ),
        );
        return $contentoptions;
    }

    function define_paramoptions() {
      $paramoptions = array(
            new rb_param_option(
                'courseid',
                'base.courseid'
            ),
            new rb_param_option(
                'completionstatus',
                'course_user_stats.completion_status'
            ),
            new rb_param_option(
                'd',
                'course_user.deleted'
            ),
            new rb_param_option(
                'hier',
                'base.hierarchyid'
            ),
        );

        return $paramoptions;
    }

    function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'course',
                'value' => 'courselink',
            ),
        );
        return $defaultcolumns;
    }

    function define_defaultfilters() {
        $defaultfilters = array(
           /* array(
                'type' => 'course',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'course_category',
                'value' => 'id',
                'advanced' => 0,
            ), */
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
            new rb_column(
                'course_user',
                'userid',
                get_string('useridnumber', 'block_lp_reportbuilder'),
                'base.userid',
                array(
                    'hidden' => 1
                )
            ),
            new rb_column(
                'course',
                'id',
                get_string('courseidnumber', 'block_lp_reportbuilder'),
                'course.id',
                array(
                    'hidden' => 1
                )
            )
        );
        return $requiredcolumns;
    }

    function define_requiredfilters() {
        $requiredfilters = array(
           /* array(
                'type' => 'course_user',
                'value' => 'coursecompletiondate',
                'advanced' => 0,
            )*/
        );

        return $requiredfilters;
    }

    function rb_display_quiz_result($item, $row) {
        if ($item === null) {
            return '-';
        }
        else {
            $item = explode('|',$item);
            $a = new stdClass;
            $a->score = isset($item[0]) ? $item[0] : '0';
            $a->max = isset($item[1]) ? $item[1] : '0';

            return get_string('formatquizresult', 'block_lp_reportbuilder', $a);
        }
    }
    
    function rb_display_format_nullable_number($item, $row) {
        global $CFG, $OUTPUT;
            
        if($item === null) {
            // No value found, return a dash
            return '-';
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
        	// Format the number two 2 decimal places
            return number_format($item, 2, '.', ',');
        } 
    }
    

} // end of rb_source_courses_users class
