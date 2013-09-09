<?php

class rb_course_usage_embedded extends rb_base_embedded {

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
        
        $this->url = '/blocks/lp_reportbuilder/reports/course_usage.php';
        $this->source = 'courses_users';
        $this->defaultsortcolumn = 'course_fullname';
        $this->shortname = 'course_usage';
        $this->fullname = get_string('reportnamecourseusage', 'block_lp_reportbuilder');
        $this->description = get_string('reportnamecourseusagedescription', 'block_lp_reportbuilder');
        $this->columns = array(
            array(
                'type' => 'course',
                'value' => 'fullname',
                'heading' => get_string('coursename', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'user_hierarchy',
                'value' => 'hierarchy',
                'heading' => $hierarchylabel,
            ),
            array(
                'type' => 'user',
                'value' => 'namelink',
                'heading' => get_string('userfullname', 'block_lp_reportbuilder'),
            ), /*
            array(
                'type' => 'user',
                'value' => 'email',
                'heading' => get_string('useremail', 'block_lp_reportbuilder'),
            ), */        
            array(
                'type' => 'course_user',
                'value' => 'coursefirstaccessed',
                'heading' => get_string('coursefirstaccessed', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'course_user',
                'value' => 'courselastaccessed',
                'heading' => get_string('courselastaccessed', 'block_lp_reportbuilder'),
            ),                                 
            array(
                'type' => 'course_user',
                'value' => 'completionstatus',
                'heading' => get_string('completionstatus', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'course_user',
                'value' => 'coursecompletiondate',
                'heading' => get_string('coursecompletiondate', 'block_lp_reportbuilder'),
            ), 
        );

        $this->filters = array(
            array(
                'type' => 'course',
                'value' => 'fullname',
                'advanced' => 0
            ),
            array(
                'type' => 'user_hierarchy',
                'value' => 'hierarchyid',
                'advanced' => 0
            ),
            array(
                'type' => 'course_user',
                'value' => 'coursefirstaccessed',
                'advanced' => 1
            ),
            array(
                'type' => 'course_user',
                'value' => 'courselastaccessed',
                'advanced' => 1
            ),
            array(
                'type' => 'course_user',
                'value' => 'coursecompletiondate',
                'advanced' => 0
            ),
        );

        // no restrictions
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        // don't include the front page (site-level course)
        $this->embeddedparams = array(
           
        );

        parent::__construct();
    }
}
