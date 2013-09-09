<?php

class rb_scorm_summary_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;
    public $defaultsortcolumn, $defaultsortorder;
    public $description;

    public function __construct() {
        $this->url = '/blocks/lp_reportbuilder/reports/scorm_summary.php';
        $this->source = 'scorm';
        $this->defaultsortcolumn = 'scorm_title';
        $this->shortname = 'scorm_summary';
        $this->fullname = get_string('reportnamescormsummary', 'block_lp_reportbuilder');
        $this->description = get_string('scormsummarydescription', 'block_lp_reportbuilder');
        $this->columns = array(
            array(
                'type' => 'user',
                'value' => 'namelink',
                'heading' => get_string('fullname')
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
                'heading' => get_string('coursename', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'scorm',
                'value' => 'title',
                'heading' => get_string('scormtitle', 'block_lp_reportbuilder')
            ),
//            array(
//                'type' => 'sco',
//                'value' => 'title',
//                'heading' => get_string('title', 'block_lp_reportbuilder')
//            ),
            array(
                'type' => 'sco',
                'value' => 'attempt',
                'heading' => get_string('attemptnum', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'sco',
                'value' => 'starttime',
                'heading' => get_string('timestart', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'sco',
                'value' => 'totaltime',
                'heading' => get_string('totaltime', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'sco',
                'value' => 'scoreraw',
                'heading' => get_string('rawscore', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'completion',
                'value' => 'completionstate',
                'heading' => get_string('completionstate', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'completion',
                'value' => 'laststatusupdate',
                'heading' => get_string('laststatusupdate', 'block_lp_reportbuilder')
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
