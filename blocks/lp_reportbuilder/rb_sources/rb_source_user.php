<?php
/**
 * A report builder source for the "user" table.
 */
class rb_source_user extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;
    public $profilefield;
    public $hierarchylabel;
    /**
     * Whether the "staff_facetoface_sessions" report exists or not (used to determine
     * whether or not to display icons that link to it)
     * @var boolean
     */
    private $staff_f2f;

    /**
     * Constructor
     * @global object $CFG
     */
    public function __construct() {
        global $CFG, $DB;
        require_once $CFG->dirroot . '/blocks/lp_hierarchy/lib.php';
        
        $hierarchy = Hierarchy::get_instance();
        
        $this->hierarchylabel = $hierarchy->get_hierarchy_field_label_text(true);       
        
        $this->base = $CFG->prefix . 'user';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->staff_f2f = $DB->get_field('report_builder', 'id', array('shortname'=>'staff_facetoface_sessions'));
        $this->sourcetitle = get_string('sourcetitleuser', 'block_lp_reportbuilder');
        
        // Retrieve the user_info_field corresponding to the hierarchy
        // There is a dependenc on the lp_hierarchy plugin for this to be configured
        $profile_field_id = $DB->get_field('user_info_field', 'id', array('datatype'=>'hierarchy'));
        
        $this->profilefield = 'user_profile_user_' . $profile_field_id;        	
        
        parent::__construct();
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    /**
     * Creates the array of rb_join objects required for this->joinlist
     *
     * @global object $CFG
     * @return array
     */
    private function define_joinlist() {
        global $CFG;
         
        // The following few lines work in conjunction with the setting on the
        // hierarchy plugin to restrict reporting to exclude a DLE user
        $hierarchy_join_clause = 'base.id = user_hierarchy.userid';
        
        if (!get_config('reportbuilder', 'allowdeletedusers')) {
            $hierarchy_join_clause = $hierarchy_join_clause 
                . ' AND base.id NOT IN (SELECT id FROM {user} WHERE deleted = 1) ';
        }       
        
        if (isset($CFG->block_lp_hierarchy_excluded)) {
            $hierarchy_join_clause = ($CFG->block_lp_hierarchy_excluded == '')? 
                $hierarchy_join_clause : 
                $hierarchy_join_clause . " AND user_hierarchy.hierarchyid NOT IN ($CFG->block_lp_hierarchy_excluded)";
        }
        
        $accessible_hierarchy_list = $this->get_accessible_hierarchy_list();
        
        if ($accessible_hierarchy_list != '') {
            $hierarchy_join_clause .= " AND user_hierarchy.hierarchyid IN ($accessible_hierarchy_list)";
        }
        
        $joinlist = array(
            new rb_join(
                'user_hierarchy',
                'INNER',
                'vw_lp_user_hierarchy',
                $hierarchy_join_clause,
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                array('base'))
            );
        
        $this->add_user_custom_fields_to_joinlist($joinlist, 'base', 'id');
 
        return $joinlist;
    }

    /**
     * Creates the array of rb_column_option objects required for
     * $this->columnoptions
     *
     * @return array
     */
    private function define_columnoptions() {    	
        $this->add_user_fields_to_columns($columnoptions, 'base');
        $this->add_user_custom_fields_to_columns($columnoptions);
        $this->add_user_hierarchy_fields_to_columns($columnoptions, 'user_hierarchy');
                
        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     * @return array
     */
    private function define_filteroptions() {
        global $CFG;
        require_once $CFG->dirroot . '/blocks/lp_hierarchy/lib.php';
        
        $hierarchy = Hierarchy::get_instance();
        
        $hierarchylabel = $hierarchy->get_hierarchy_field_label_text(true);
        
        $filteroptions = array();

        $this->add_user_fields_to_filters($filteroptions);
        $this->add_user_custom_fields_to_filters($filteroptions);
            
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


    function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelinkicon',
            ),
            array(
                'type' => 'user',
                'value' => 'username',
            ),
            array(
                'type' => 'user',
                'value' => 'lastlogin',
            ),
        );
        return $defaultcolumns;
    }

    function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
            ),
            array(
                'type' => 'user',
                'value' => 'lastlogin',
            )
        );

        return $defaultfilters;
    }
    
    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    private function define_contentoptions() {
        $contentoptions = array();

        // Include the rb_user_content content options for this report
        $contentoptions[] = new rb_content_option(
            'user',
            get_string('users', 'block_lp_reportbuilder'),
            'base.id');
        return $contentoptions;
    }

    function rb_display_user_profile_hierarchy_as_text($user, $row) {
        global $CFG, $OUTPUT;
        	
        require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');
        
        $hierarchy = Hierarchy::get_instance();
        $hierarchy_list = $hierarchy->get_hierarchy_list();

        // This value is dynamically retrieved from the database in the constructor
        $property = $this->profilefield; 

        if (array_key_exists($row->$property, $hierarchy_list)) {
            return $hierarchy_list[$row->$property]->value;
        }
        else {
            return "";
        }        
    }
    
    function rb_display_user_with_links($user, $row) {
        global $CFG, $OUTPUT;
        $userid = $row->user_id;

        $picuser = new stdClass();
        $picuser->id = $userid;
        $picuser->picture = $row->userpic_picture;
        $picuser->imagealt = $row->userpic_imagealt;
        $picuser->firstname = $row->userpic_firstname;
        $picuser->lastname = $row->userpic_lastname;
        
        $user_pic = $OUTPUT->user_picture($picuser, null);
        //$user_pic = print_user_picture($picuser, 1, null, null, true);

        //$recordstr = get_string('records', 'rb_source_user');
       // $planstr = get_string('plans', 'rb_source_user');
        $profilestr = "Profile"; //get_string('profile', 'rb_source_user');
       // $bookingstr = get_string('bookings', 'rb_source_user');

      //  $rol_link = "<a href=\"{$CFG->wwwroot}/local/plan/record/courses.php?userid={$userid}\">{$recordstr}</a>";
      //  $plan_link = "<a href=\"{$CFG->wwwroot}/local/plan/index.php?userid={$userid}\">{$planstr}</a>";
        $profile_link = "<a href=\"{$CFG->wwwroot}/user/view.php?id={$userid}\">{$profilestr}</a>";
      //  $booking_link = "<a href=\"{$CFG->wwwroot}/my/bookings.php?userid={$userid}\">{$bookingstr}</a>";

        $show_plan_link = dp_can_view_users_plans($userid);

        $return = '<div class="picture">'.$user_pic.' <span class="username">'.$user.'</span></div>'.'<div class="links">';
//        $return .= $show_plan_link ? ($plan_link.'&nbsp;|&nbsp;') : '';
        $return .= $profile_link.'&nbsp;|&nbsp;';
  //      $return .= $booking_link.'&nbsp;|&nbsp;';
    //    $return .= $rol_link.'</div>';

        return $return;
    }

    function define_requiredcolumns() {
        $requiredcolumns = array(
            new rb_column(
                'user_hierarchy',
                'hierarchyid',
                get_string('hierarchyid', 'block_lp_reportbuilder'),
                'user_hierarchy.hierarchyid',
                array(
                    'hidden' => 1,
                    'joins' => 'user_hierarchy'
                )
            )
        );
        return $requiredcolumns;
    }
    
    function rb_display_count($result) {
        return $result ? $result : 0;
    }

    function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'deleted',
                'base.deleted'
            ),
        );

        return $paramoptions;
    }
}

// end of rb_source_user class

