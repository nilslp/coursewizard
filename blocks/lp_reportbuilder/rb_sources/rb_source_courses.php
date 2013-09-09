<?php

defined('MOODLE_INTERNAL') || die();

class rb_source_courses extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;
    public $hidedeleted;
    public $hierarchyfilterlist;

    function __construct() {
        global $CFG;
        $this->base = "(SELECT * FROM {$CFG->prefix}course WHERE category != 0)"; //$CFG->prefix . 'course';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitlecourse', 'block_lp_reportbuilder');
        parent::__construct();
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    function define_joinlist() {
        global $CFG;
               
        $hierarchy_join_clause = '';
        
        if (isset($CFG->block_lp_hierarchy_excluded)) {
            $hierarchy_join_clause = ($CFG->block_lp_hierarchy_excluded == '')? 
                $hierarchy_join_clause : 
                    $hierarchy_join_clause . " AND s.hierarchyid NOT IN ($CFG->block_lp_hierarchy_excluded) ";
        }
        
        $accessible_hierarchy_list = $this->get_accessible_hierarchy_list();
        
        if ($accessible_hierarchy_list != '') {
            $hierarchy_join_clause .= " AND s.hierarchyid IN ($accessible_hierarchy_list) ";
        }
      
        // BEGIN
        // Filtering based on the config regarding hierarchy and deleted users            
        $hierarchy_list = array();
        $hierarchy_where_clause = '';
        
        if (isset($_REQUEST['course_users-hierarchyid_op'])) {
            $hierarchy_operator = optional_param('course_users-hierarchyid_op', 0, PARAM_INT);
            
            if ($hierarchy_operator != 0) {
                // Hierarchy should be factored into the nested SELECT statements for the aggregate function
                $hierarchy_id = optional_param('course_users-hierarchyid', 0, PARAM_INT);
                $include_sub = optional_param('course_users-hierarchyid_rec', -1, PARAM_INT);

                if ($hierarchy_id != 0) {
                    // An organisation was selected
                    if ($include_sub == 1) {
                        require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');

                        $hierarchy = Hierarchy::get_instance();

                        // Get the orgs beneath this one
                        $children = $hierarchy->get_all_children($hierarchy_id);

                        $hierarchy_list = explode(',', $hierarchy_id . ',' . $children);                    
                    }
                    else {
                        $hierarchy_list[] = $hierarchy_id;
                    }

                    // Store the hierarchy filter
                    $this->hierarchyfilterlist = implode(',', $hierarchy_list);
                    
                    // Build the WHERE clause
                    switch ($hierarchy_operator) {
                        case 1:
                            // "Is equal to"
                            $hierarchy_where_clause = " AND s.hierarchyid IN (" . implode(',', $hierarchy_list) . ") ";
                            break;

                        case 2:
                            // "Isn't equal to"
                            $hierarchy_where_clause = " AND s.hierarchyid NOT IN (" . implode(',', $hierarchy_list) . ") ";
                            break;
                    }
                    
                    $_SESSION['hierarchy_where_clause'] = $hierarchy_where_clause;
                }
            }
            else {
                // Clear any pre-existing hierarchy
                if (key_exists('hierarchy_where_clause', $_SESSION)) {
                    unset($_SESSION['hierarchy_where_clause']);
                }
                
                $this->hierarchyfilterlist = null;
            }
        }
        else {
            // Check the session, in case this is an export
            if (isset($_SESSION['hierarchy_where_clause'])) {
                $hierarchy_where_clause = $_SESSION['hierarchy_where_clause'];
            }
        }
            
        // This is the 'Include deleted users' option
        if (isset($_REQUEST['user-deleted'])) {
            $deleted_flag = ($_REQUEST['user-deleted'] != '0') ? 1 : 0;
            
            $_SESSION['course_search_user-deleted'] = $deleted_flag;
        }
        else {
            // Check the session, in case this is an export
            if (!isset($_SESSION['course_search_user-deleted'])) {
                if (get_config('reportbuilder', 'allowdeletedusers') == 1) {
                    $deleted_flag = 1;
                }
                else {
                    $deleted_flag = 0;
                }
            }
            else {
                $deleted_flag = $_SESSION['course_search_user-deleted'];
            }            
        }
        
        $user_delete_where_clause = '';
        
        if ($deleted_flag != 1) {
            $this->hidedeleted = true;
            $user_delete_where_clause = " AND u.deleted = 0 ";
        }
        else {
            $this->hidedeleted = false;
        }

        // End custom filtering
        
        $joinlist = array(
            new rb_join(
                'course_users',
                'LEFT',            
                "(SELECT s.courseid, COUNT(s.id) AS user_count
                    FROM vw_lp_course_students s
                    INNER JOIN {$CFG->prefix}user u ON u.id = s.userid
                    WHERE (1=1) $hierarchy_join_clause 
                        $user_delete_where_clause 
                        $hierarchy_where_clause
                    GROUP BY courseid)",
                'course_users.courseid = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'completions',
                'LEFT OUTER',
                "(
                    SELECT c.course, COUNT(c.id) AS completions_count
                    FROM {$CFG->prefix}report_builder_course_comp c
                    INNER JOIN vw_lp_course_students s ON s.userid = c.userid AND s.courseid = c.course
                        $hierarchy_join_clause
                    INNER JOIN {$CFG->prefix}user u ON u.id = s.userid
                    WHERE c.completion_status = 2
                        $user_delete_where_clause 
                        $hierarchy_where_clause
                    GROUP BY c.course
                )",
                'completions.course = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'incompletions',
                'LEFT OUTER',
                 "(
                    SELECT c.course, COUNT(c.id) AS incompletions_count
                    FROM {$CFG->prefix}report_builder_course_comp c
                    INNER JOIN vw_lp_course_students s ON s.userid = c.userid AND s.courseid = c.course
                        $hierarchy_join_clause
                    INNER JOIN {$CFG->prefix}user u ON u.id = s.userid
                    WHERE c.completion_status = 1
                        $user_delete_where_clause 
                        $hierarchy_where_clause
                    GROUP BY c.course
                )",
                'incompletions.course = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'non_attempts',
                'LEFT OUTER',
                "(
                    SELECT c.course, COUNT(c.id) AS non_attempts_count
                    FROM {$CFG->prefix}report_builder_course_comp c
                    INNER JOIN vw_lp_course_students s ON s.userid = c.userid AND s.courseid = c.course
                        $hierarchy_join_clause
                    INNER JOIN {$CFG->prefix}user u ON u.id = s.userid
                    WHERE c.completion_status = 0
                        $user_delete_where_clause 
                        $hierarchy_where_clause
                    GROUP BY c.course
                )",
                'non_attempts.course = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'average_time_spent',
                'LEFT OUTER',
                "(
                    SELECT c.course, ROUND(AVG(c.timecompleted - c.timestarted)) AS average
                    FROM {$CFG->prefix}report_builder_course_comp c
                    INNER JOIN vw_lp_course_students s ON s.userid = c.userid AND s.courseid = c.course
                        $hierarchy_join_clause
                    INNER JOIN {$CFG->prefix}user u ON u.id = s.userid
                    WHERE c.completion_status = 2 AND c.timecompleted > c.timestarted
                        $user_delete_where_clause 
                        $hierarchy_where_clause
                    GROUP BY c.course
                )",
                'average_time_spent.course = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'active_users',
                'LEFT OUTER',
                "(
                    SELECT la.courseid AS course, COUNT(la.id) AS active
                    FROM {$CFG->prefix}user_lastaccess la
                    INNER JOIN {$CFG->prefix}user u ON u.id = la.userid
                    INNER JOIN vw_lp_course_students s ON s.userid = u.id AND s.courseid = la.courseid
                    WHERE la.timeaccess + 7948800 >= UNIX_TIMESTAMP() 
                    GROUP BY la.courseid
                )",
                'active_users.course = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),                        
        );

        // Include the course category
        $this->add_course_category_table_to_joinlist($joinlist,
            'base', 'category');

        return $joinlist;
    }

    function define_columnoptions() {
        // Include standard course and course categories columns
        $this->add_course_fields_to_columns($columnoptions, 'base');
        $this->add_course_category_fields_to_columns($columnoptions, 'course_category', 'base');

        // Begin generating the aggregated statistics columns
        // Count of enrolments
        $columnoptions[] = new rb_column_option(
            'course_statistics',
            'course_users',
            get_string('statenrolments', 'block_lp_reportbuilder'),
            "IFNULL(course_users.user_count, 0)",
            array('joins' => 'course_users')
        );
        
        // Count of enrolments (link)
        $columnoptions[] = new rb_column_option(
            'course_statistics',
            'course_users_link',
            get_string('statenrolmentslink', 'block_lp_reportbuilder'),
            "IFNULL(course_users.user_count, 0)",
            array(
                'joins' => 'course_users',
                'displayfunc' => 'link_course_users',
                'defaultheading' => get_string('statenrolments', 'block_lp_reportbuilder')
            )
        );
        
        // Count of completions
        $columnoptions[] = new rb_column_option(
            'course_statistics',
            'completions',
            get_string('statcompletions', 'block_lp_reportbuilder'),
            'IFNULL(completions.completions_count, 0)',
            array('joins' => 'completions', 'displayfunc' => 'format_nullable_number')
        );
        
        // Count of completions (link)
        $columnoptions[] = new rb_column_option(
            'course_statistics',
            'completions_link',
            get_string('statcompletionslink', 'block_lp_reportbuilder'),
            'IFNULL(completions.completions_count, 0)',
            array('joins' => 'completions', 
                'displayfunc' => 'link_course_completions',
                'defaultheading' => get_string('statcompletions', 'block_lp_reportbuilder'))
        );
        
        // Count of incompletions
        $columnoptions[] = new rb_column_option(
            'course_statistics',
            'incompletions',
            get_string('statincompletions', 'block_lp_reportbuilder'),
            'IFNULL(incompletions.incompletions_count, 0)',
            array('joins' => 'incompletions', 'displayfunc' => 'format_nullable_number')
        );
        
        // Count of incompletions (link)
        $columnoptions[] = new rb_column_option(
            'course_statistics',
            'incompletions_link',
            get_string('statincompletionslink', 'block_lp_reportbuilder'),
            'IFNULL(incompletions.incompletions_count, 0)',
            array('joins' => 'incompletions', 
                'displayfunc' => 'link_course_incompletions',
                'defaultheading' => get_string('statincompletions', 'block_lp_reportbuilder'))
        );
        
        // Count of non-attempts
        $columnoptions[] = new rb_column_option(
            'course_statistics',
            'non_attempts',
            get_string('statnonattempts', 'block_lp_reportbuilder'),
            'IFNULL(non_attempts.non_attempts_count, 0)',
            array('joins' => 'non_attempts', 'displayfunc' => 'format_nullable_number')
        );
        
        // Count of non-attempts (link)
        $columnoptions[] = new rb_column_option(
            'course_statistics',
            'non_attempts_link',
            get_string('statnonattemptslink', 'block_lp_reportbuilder'),            
            'IFNULL(non_attempts.non_attempts_count, 0)',
            array('joins' => 'non_attempts', 
                'displayfunc' => 'link_course_nonattempts',
                'defaultheading' => get_string('statnonattempts', 'block_lp_reportbuilder'))
        );
        
        // Average time spent
        $columnoptions[] = new rb_column_option(
            'course_statistics',
            'average_time_spent',
            get_string('stataveragetimespentinmins', 'block_lp_reportbuilder'),
            'average_time_spent.average',
            array('joins' => 'average_time_spent', 'displayfunc' => 'format_time_as_minutes')
        );
        
        // Count of active users
        $columnoptions[] = new rb_column_option(
            'course_statistics',
            'active_users_count',
            get_string('statactive', 'block_lp_reportbuilder'),
            'IFNULL(active_users.active, 0)',
            array('joins' => 'active_users', 
                'defaultheading' => get_string('statactive', 'block_lp_reportbuilder'))
        );
                
        return $columnoptions;
    }

    function define_filteroptions() {
        // Include some standard filters
        $this->add_course_fields_to_filters($filteroptions, 'base', 'id');
        $this->add_course_category_fields_to_filters($filteroptions, 'base', 'category');
                
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
            new rb_param_option(
                'courseid',
                'base.id'
            ),
            new rb_param_option(
                'visible',
                'base.visible'
            ),
            new rb_param_option(
                'category',
                'base.category'
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
} // end of rb_source_courses class

