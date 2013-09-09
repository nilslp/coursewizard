<?php

class rb_trent_export_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;
    public $defaultsortcolumn, $defaultsortorder;
    public $description;

    public function __construct() {
        $this->url = '/blocks/lp_reportbuilder/reports/trent_export.php';
        $this->source = 'courses_users';
        $this->defaultsortcolumn = 'user_lastname';
        $this->shortname = 'trent_export';
        $this->fullname = get_string('reportnametrentexport', 'block_lp_reportbuilder');
        $this->description = '<p>Use the filters to report on specific users.</p>';
        $columns = array(
            array(
                'type' => 'user',
                'value' => 'username',
                'heading' => get_string('username', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'user',
                'value' => 'firstname',
                'heading' => get_string('userfirstname', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'user',
                'value' => 'lastname',
                'heading' => get_string('userlastname', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'user',
                'value' => 'email',
                'heading' => get_string('useremail', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'course',
                'value' => 'fullname',
                'heading' => get_string('coursename', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'course_user',
                'value' => 'coursecompletiondate',
                'heading' => get_string('coursecompletiondate', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'course_user',
                'value' => 'completionstatus',
                'heading' => get_string('coursestatus', 'block_lp_reportbuilder')
            )
        );

        if (get_config('reportbuilder', 'allowdeletedusers') == 1) {
            $columns[] = array('type' => 'user', 'value' => 'deleted', 'heading' => get_string('userdeleted', 'block_lp_reportbuilder'));
        }
        
        $this->columns = $columns;
        
        // no filters
        $filters = array(
            array(
                'type' => 'course_user',
                'value' => 'coursecompletiondate',
                'advanced' => 0,
            ),
            array(
                'type' => 'course_user',
                'value' => 'courselastaccessed',
                'advanced' => 0,
            )
        );

        if (get_config('reportbuilder', 'allowdeletedusers') == 1) {
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
