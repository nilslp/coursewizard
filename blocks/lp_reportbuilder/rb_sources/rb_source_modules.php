<?php

defined('MOODLE_INTERNAL') || die();

class rb_source_modules extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;

    function __construct() {
        global $CFG;
        // $this->base = '{course_modules_completion}';
        
        // check hierarchy access
        $hierarchy_clause = ' 1=1 ';
        
        if (isset($CFG->block_lp_hierarchy_excluded) && !empty($CFG->block_lp_hierarchy_excluded)) {
            $hierarchy_clause .= " AND hierarchyid NOT IN ($CFG->block_lp_hierarchy_excluded) ";
        }
//        $excludedarray = explode(',',$CFG->block_lp_hierarchy_excluded);
//        
//        $tempexcluded = reset($excludedarray);
//        if (!empty($tempexcluded)) {
//            $hierarchy_clause = " hierarchyid NOT IN ($CFG->block_lp_hierarchy_excluded) ";
//        }
        
        $accessible_hierarchy_list = $this->get_accessible_hierarchy_list();
        
        if (!empty($accessible_hierarchy_list)) {
            $hierarchy_clause .= " AND hierarchyid IN ($accessible_hierarchy_list) ";
        }
        
//        $access = $this->get_accessible_hierarchy_list();
// 
//        $accessarray = explode(',',$access);
// 
//        $tempaccess = reset($accessarray);
//        if (!empty($tempaccess)) {
//            $hierarchy_clause = " AND hierarchyid IN ($access) ";
//        }   
        
        // check deleted config
        $deleted_clause = intval(get_config('reportbuilder', 'allowdeletedusers')) ? '' : ' AND deleted = 0 ';  
        
        $this->base = "(SELECT * 
                        FROM {report_builder_module_comp} 
                        WHERE  
                        $hierarchy_clause
                        $deleted_clause AND completionenabled <> 0)";
        
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitlemodules', 'block_lp_reportbuilder');

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
                "base.userid=user_hierarchy.userid",
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            );
        
        // include some standard joins      
        $this->add_user_table_to_joinlist($joinlist, 'base', 'userid' );
               
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
                'completion',
                'completionviewed',
                get_string('completionviewed', 'block_lp_reportbuilder'),
                'base.viewed',
                array(
                    'displayfunc' => 'yes_or_no'
                )
            )
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
                'moduleid',
                'base.moduleid'
            ),
            new rb_param_option(
                'courseid',
                'base.courseid'
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
                'type' => 'course_modules',
                'value' => 'instance',
            ),
            array(
                'type' => 'course_modules',
                'value' => 'moduletype',
            ),
            array(
                'type' => 'completion',
                'value' => 'completionstate',
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
            new rb_column(
                'completion',
                'completionstate',
                get_string('completionstate', 'block_lp_reportbuilder'),
                'base.completionstate',
                array(
                    'displayfunc' => 'module_completionstate'
                )
            ),
            new rb_column(
                'course_modules',
                'instance',
                get_string('moduleinstance', 'block_lp_reportbuilder'),
                'base.moduleinstance',
                array(
                    'displayfunc' => 'module_instancename'
                )
            ),
            new rb_column(
                'course_modules',
                'moduletype',
                get_string('moduletype', 'block_lp_reportbuilder'),
                'base.moduletype'
            ),
            new rb_column(
                'base',
                'userid',
                get_string('useridnumber', 'block_lp_reportbuilder'),
                'base.userid',
                array(
                    'hidden' => 1
                )
            ),
            new rb_column(
                'base',
                'moduleid',
                get_string('moduleidnumber', 'block_lp_reportbuilder'),
                'base.moduleid',
                array(
                    'hidden' => 1
                )
            ),
            new rb_column(
                'base',
                'courseid',
                'course id',
                'base.courseid',
                array(
                    'hidden' => 1
                )
            )
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
    
    /**
     *
     * @global moodle_database $DB
     * @param object $item
     * @param object $row
     * @return string 
     */
    function rb_display_module_instancename($item, $row) {      
        global $DB;
        
        return $DB->get_field($row->course_modules_moduletype,'name',array('id'=>$item));
    }
        
    /**
     * displays the user completions status hyperlinked to quiz/scorm where applicable
     * 
     * @global type $CFG
     * @param type $item
     * @param type $row
     * @return type 
     */
    function rb_display_module_completionstate($item, $row) {   
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
        
        switch ($row->course_modules_moduletype) {
            case 'scorm':
                return html_writer::link($CFG->wwwroot."/blocks/lp_reportbuilder/reports/scorm_summary.php?autosubmit=1&userid={$row->base_userid}&moduleid={$row->base_moduleid}&courseid={$row->base_courseid}", $text,array('title'=>get_string('viewdetails','block_lp_reportbuilder')));
            case 'quiz':
                return html_writer::link($CFG->wwwroot."/blocks/lp_reportbuilder/reports/quiz_summary.php?autosubmit=1&userid={$row->base_userid}&moduleid={$row->base_moduleid}&courseid={$row->base_courseid}", $text,array('title'=>get_string('viewdetails','block_lp_reportbuilder')));
            default:
                break;
        }
  
        return $text;
    }
    
    //
    //
    // Source specific filter display methods
    //
    //
    /*
    function rb_filter_[name]() {
     
    }
    */
} // end of rb_source_quiz class

