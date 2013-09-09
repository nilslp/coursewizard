<?php

defined('MOODLE_INTERNAL') || die();

class rb_source_courses_nonattempts extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;

    function __construct() {
        global $CFG;

        $this->base = "(SELECT id, userid, course
                        FROM {$CFG->prefix}report_builder_course_comp
                        WHERE completion_status = 0)";
                        
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitlecoursenonattempts', 'block_lp_reportbuilder');
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
                'course',
                'INNER',            
                $CFG->prefix . 'course',
                'course.id = base.course',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'user_hierarchy',
                'INNER',
                'vw_lp_user_hierarchy',
                'user_hierarchy.userid = base.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'course_user',
                'INNER',
                $CFG->prefix . 'user',
                'course_user.id = base.userid',
                REPORT_BUILDER_RELATION_MANY_TO_MANY,
                array('dependencies'=>'user_hierarchy')
            ),
        );

        // Include the course category
       // $this->add_course_category_table_to_joinlist($joinlist,
       //     'base', 'category');

        return $joinlist;
    }

    function define_columnoptions() {
        // Include some standard columns        
        $this->add_user_fields_to_columns($columnoptions, 'course_user');
        $this->add_course_fields_to_columns($columnoptions, 'course');
        $this->add_user_hierarchy_fields_to_columns($columnoptions, 'user_hierarchy');
        
        return $columnoptions;
    }

    function define_filteroptions() {
        // Include some standard filters
        $this->add_course_fields_to_filters($filteroptions, 'course', 'id');

        return $filteroptions;
    }

    function define_contentoptions() {
        $contentoptions = array();
        
        return $contentoptions;
    }

    function define_paramoptions() {
      $paramoptions = array(
            new rb_param_option(
                'courseid',
                'base.courseid'
            ),
            new rb_param_option(
                'd',
                'course_user.deleted'
            ),
            new rb_param_option(
                'hier',
                'user_hierarchy.hierarchyid'
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
        $defaultfilters = array();
        
        return $defaultfilters;
    }

    function define_requiredcolumns() {
        $requiredcolumns = array();
        
        return $requiredcolumns;
    }
} // end of rb_source_courses_nonattempts class
