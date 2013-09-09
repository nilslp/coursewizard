<?php

class rb_user_search_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;
    public $defaultsortcolumn, $defaultsortorder;
    public $description;

    public function __construct() {
        global $CFG;
        
        require_once $CFG->dirroot . '/blocks/lp_hierarchy/lib.php';
        
        $hierarchy = Hierarchy::get_instance();
        
        $hierarchylabel = $hierarchy->get_hierarchy_field_label_text(true);
        
        $this->url = '/blocks/lp_reportbuilder/reports/user_search.php';
        $this->source = 'user';
        $this->defaultsortcolumn = 'user_lastname';
        $this->shortname = 'user_search';
        $this->fullname = get_string('reportnamesearchusers', 'block_lp_reportbuilder');
        $this->description = get_string('reportnamesearchusersdescription', 'block_lp_reportbuilder');
        $columns = array(
            array(
                'type' => 'user_hierarchy',
                'value' => 'hierarchy',
                'heading' => $hierarchylabel,
            ),
            array(
                'type' => 'user',
                'value' => 'lastname',
                'heading' => get_string('lastname'),
            ),
            array(
                'type' => 'user',
                'value' => 'firstname',
                'heading' => get_string('firstname'),
            ),
            array(
                'type' => 'user',
                'value' => 'username',
                'heading' => get_string('username'),
            ),
            array(
                'type' => 'user',
                'value' => 'email',
                'heading' => get_string('email'),
            ),
        );
        
        if (get_config('reportbuilder', 'allowdeletedusers') == 1) {
            $columns[] = array('type' => 'user', 'value' => 'deleted', 'heading' => get_string('userdeleted', 'block_lp_reportbuilder'));
        }
        
        $this->columns = $columns;
/*
        // no filters
        $this->filters = array(
            array(
                'type' => 'user',
                'value' => 'username',
                'advanced' => 0,
            ),
            array(
                'type' => 'user',
                'value' => 'lastname',
                'advanced' => 0,
            ),
            array(
                'type' => 'user_hierarchy',
                'value' => 'hierarchyid',
                'advanced' => 0,
            ),
        );
*/

        // Define the default filters        
        $filters = array();
        
        $filters[] = array('type' => 'user', 'value' => 'username', 'advanced' => 0,);
        
        $filters[] = array('type' => 'user', 'value' => 'firstname', 'advanced' => 0,);
        
        $filters[] = array('type' => 'user', 'value' => 'lastname', 'advanced' => 0,);
                    
        $filters[] = array('type' => 'user_hierarchy', 'value' => 'hierarchyid', 'advanced' => 0,);
        
        // Add the filter for showing/hiding deleted users (if applicable)
        if (get_config('reportbuilder', 'allowdeletedusers') == 1) {            
            // there should be an option to include deleted users
            $filters[] = array('type' => 'user', 'value' => 'deleted', 'advanced' => 1);
        }
        
        $this->filters = $filters;
        
        // no restrictions
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        // don't include the front page (site-level course)
        $this->embeddedparams = array(
           
        );
        
        parent::__construct();
    }
}
