<?php

class rb_quiz_summary_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;
    public $defaultsortcolumn, $defaultsortorder;
    public $description;

    public function __construct() {
        $this->url = '/blocks/lp_reportbuilder/reports/quiz_summary.php';
        $this->source = 'quiz';
        $this->defaultsortcolumn = 'quiz_title';
        $this->shortname = 'quiz_summary';
        $this->fullname = get_string('reportnamequizsummary', 'block_lp_reportbuilder');
        $this->description = get_string('quizsummarydescription', 'block_lp_reportbuilder');
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
                'type' => 'quiz',
                'value' => 'title',
                'heading' => get_string('quizname', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'quiz',
                'value' => 'attempt',
                'heading' => get_string('attemptnum', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'quiz',
                'value' => 'status',
                'heading' => get_string('quizstatus', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'quiz_grades',
                'value' => 'grade',
                'heading' => get_string('quizresult', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'quiz',
                'value' => 'timestart',
                'heading' => get_string('timestart', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'quiz',
                'value' => 'timefinish',
                'heading' => get_string('timefinish', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'quiz',
                'value' => 'duration',
                'heading' => get_string('duration', 'block_lp_reportbuilder')
            ),
            array(
                'type' => 'user',
                'value' => 'lastaccess',
                'heading' => get_string('lastaccess')
            )
        );

        $this->filters = array(
            array(
                'type' => 'quiz',
                'value' => 'title',
                'advanced' => 0
            ),
            array(
                'type' => 'course',
                'value' => 'fullname',
                'advanced' => 0
            ), 
        );
        
        // no restrictions
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
        $this->embeddedparams = array(           
        );
        $context = get_context_instance(CONTEXT_SYSTEM);

        parent::__construct();
    }
}
