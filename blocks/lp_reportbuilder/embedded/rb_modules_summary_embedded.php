<?php

class rb_modules_summary_embedded extends rb_base_embedded {

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
        
        $this->url = '/blocks/lp_reportbuilder/reports/modules_summary.php';
        $this->source = 'modules';
        $this->defaultsortcolumn = 'user_fullname';
        $this->shortname = 'modules_summary';
        $this->fullname = get_string('reportnamemodulessummary', 'block_lp_reportbuilder');
        $this->description = get_string('reportnamemodulessummarydescription', 'block_lp_reportbuilder');
        $this->columns = array(
            array(
                'type' => 'course',
                'value' => 'courselink',
                'heading' => get_string('coursename', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'user',
                'value' => 'fullname',
                'heading' => get_string('userfullname', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'user_hierarchy',
                'value' => 'hierarchy',
                'heading' => $hierarchylabel
            ),
          /*  array(
                'type' => 'course_modules',
                'value' => 'instance',
                'heading' => get_string('modulename', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'course_modules',
                'value' => 'moduletype',
                'heading' => get_string('moduletype', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'completion',
                'value' => 'completionstate',
                'heading' => get_string('completionstate', 'block_lp_reportbuilder')
            ),*/
            array(
                'type' => 'completion',
                'value' => 'timecomplete',
                'heading' => get_string('timecomplete', 'block_lp_reportbuilder')
            )
        );

        $this->filters = array(
        );
        // no restrictions
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
        $this->embeddedparams = array(           
        );
        $context = get_context_instance(CONTEXT_SYSTEM);

        parent::__construct();
    }
}
