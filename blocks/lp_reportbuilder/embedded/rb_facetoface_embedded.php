<?php
global $CFG;
if(file_exists($CFG->dirroot.'/mod/facetoface/lib.php')){
class rb_facetoface_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;
    public $defaultsortcolumn, $defaultsortorder;
    public $description;

    public function __construct($data) {
        global $CFG;
        
        $this->url = '/mod/facetoface/reports/facetoface.php';
        $this->source = 'facetoface';
        $this->defaultsortcolumn = 'name';
        $this->shortname = 'facetoface';
        $this->fullname = get_string('sourcetitlef2f', 'block_lp_reportbuilder');
        $this->description = '';
        $this->columns = array(
            array(
                'type' => 'face_to_face',
                'value' => 'name',
                'heading' => get_string('f2fname', 'block_lp_reportbuilder'),
            ),
            array(
                'type'=>'course',
                'value'=>'courselink',
                'heading' => get_string('coursename', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face',
                'value' => 'datecreated',
                'heading' => get_string('f2fcreated', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face',
                'value' => 'lastmodified',
                'heading' => get_string('f2fmodified', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face',
                'value' => 'past',
                'heading' => get_string('f2fsessionprevious', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face',
                'value' => 'future',
                'heading' => get_string('f2fsessionfuture', 'block_lp_reportbuilder'),
            ),
        );

        $this->filters = array(
            array(
                'type' => 'face_to_face',
                'value' => 'name',
                'advanced' => 0
            ),
            array(
                'type'=>'course',
                'value'=>'courselink',
                'advanced'=>0,
            ),
            array(
                'type'=>'face_to_face',
                'value'=>'datecreated',
                'advanced'=>0,
            ),
            array(
                'type'=>'face_to_face',
                'value'=>'lastmodified',
                'advanced'=>0,
            ),
        );

        // no restrictions
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        $this->embeddedparams = array(
           //'base.id' => optional_param('f2fid', '', PARAM_INT),
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