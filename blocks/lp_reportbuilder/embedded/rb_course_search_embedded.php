<?php

class rb_course_search_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;
    public $defaultsortcolumn, $defaultsortorder;
    public $description;

    public function __construct() {
        $this->url = '/blocks/lp_reportbuilder/reports/course_search.php';
        $this->source = 'courses';
        $this->defaultsortcolumn = 'course_fullname';
        $this->shortname = 'course_search';
        $this->fullname = get_string('reportnamesearchcourses', 'block_lp_reportbuilder');
        $this->description = get_string('searchcoursedescription', 'block_lp_reportbuilder');

        $this->columns = array(
            array(
                'type' => 'course_category',
                'value' => 'name',
                'heading' => get_string('category', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'course',
                'value' => 'fullname',
                'heading' => get_string('coursename', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'course',
                'value' => 'startdate',
                'heading' => get_string('report:startdate', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'course_statistics',
                'value' => 'course_users_link',
                'heading' => get_string('statenrolments', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'course_statistics',
                'value' => 'active_users_count',
                'heading' => get_string('statactive', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'course_statistics',
                'value' => 'completions_link',
                'heading' => get_string('statcompletions', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'course_statistics',
                'value' => 'non_attempts_link',
                'heading' => get_string('statnonattempts', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'course_statistics',
                'value' => 'incompletions_link',
                'heading' => get_string('statincompletions', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'course_statistics',
                'value' => 'average_time_spent',
                'heading' => get_string('stataveragetimespentinmins', 'block_lp_reportbuilder'),
            )
        );

        // Filters
        $filters = array(
            array(
                'type' => 'course_category',
                'value' => 'id',
                'advanced' => 0,
            ),
            array(
                'type' => 'course',
                'value' => 'name_and_summary',
                'advanced' => 0,
            ),
            array(
                'type' => 'course',
                'value' => 'startdate',
                'advanced' => 1,
            ),
        );

        if (get_config('reportbuilder', 'allowdeletedusers') == 1) {
            $filters[] = array('type' => 'course_users', 'value' => 'deleted', 'advanced' => 1); 
        }
        
        $this->filters = $filters;
        
        // no restrictions
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        parent::__construct();
    }
}
