<?php
global $CFG;
if(file_exists($CFG->dirroot.'/mod/facetoface/lib.php')){
class rb_facetoface_sessions_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;
    public $defaultsortcolumn, $defaultsortorder;
    public $description;

    public function __construct($data) {
        global $CFG;
        
        $this->url = '/mod/facetoface/reports/facetoface_sessions.php';
        $this->source = 'facetoface_sessions';
        $this->shortname = 'facetoface_sessions';
        $this->fullname = get_string('reportnamesessions', 'block_lp_reportbuilder');
        $this->description = '';
        $this->columns = array(
            array(
                'type' => 'face_to_face_sessions',
                'value' => 'name',
                'heading' => get_string('f2fname', 'block_lp_reportbuilder'),
            ),
            array(
                'type'=>'course',
                'value'=>'courselink',
                'heading' => get_string('coursename', 'block_lp_reportbuilder'),
            ),
            array(
                'type'=>'face_to_face_sessions',
                'value'=>'session_teachers',
                'heading' => get_string('f2fsessteachers', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face_sessions',
                'value' => 'session_start_date',
                'heading' => get_string('f2fsessionstartdate', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face_sessions',
                'value' => 'session_finish_date',
                'heading' => get_string('f2fsessionfinishdate', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face_sessions',
                'value' => 'session_capacity',
                'heading' => get_string('f2fsessioncapacity', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face_sessions',
                'value' => 'session_capacity_remaining',
                'heading' => get_string('f2fsessioncapacityremaining', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face_sessions',
                'value' => 'session_capacity_taken',
                'heading' => get_string('f2fsessioncapacitytaken', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face_sessions',
                'value' => 'session_venue_name',
                'heading' => get_string('f2fsessionvenuename', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face_sessions',
                'value' => 'session_venue_location',
                'heading' => get_string('f2fsessionvenuelocation', 'block_lp_reportbuilder'),
            ),
            
        );

        $this->filters = array(
            array(
                'type' => 'face_to_face_sessions',
                'value' => 'name',
                'advanced' => 0
            ),
            array(
                'type'=>'course',
                'value'=>'courselink',
                'advanced'=>0,
            ),
            array(
                'type'=>'face_to_face_sessions',
                'value'=>'session_start_date',
                'advanced'=>0,
            ),
            array(
                'type'=>'face_to_face_sessions',
                'value'=>'session_finish_date',
                'advanced'=>0,
            ),
            array(
                'type'=>'face_to_face_sessions',
                'value'=>'session_venue_name',
                'advanced'=>0,
            ),
            array(
                'type'=>'face_to_face_sessions',
                'value'=>'session_venue_location',
                'advanced'=>0,
            ),
        );

        // no restrictions
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        $this->embeddedparams = array(
           'base.facetoface' => optional_param('f2fid', '', PARAM_INT),
        );

        $context = get_context_instance(CONTEXT_SYSTEM);
        //if(!has_capability('moodle/site:doanything', $context)) {
            // don't show hidden courses to none-admins
        //    $this->embeddedparams['visible'] = 1;
        //}

        parent::__construct();
    }
}
}
