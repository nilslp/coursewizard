<?php
global $CFG;
if(file_exists($CFG->dirroot.'/mod/facetoface/lib.php')){
class rb_facetoface_signups_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;
    public $defaultsortcolumn, $defaultsortorder;
    public $description;

    public function __construct($data) {
        global $CFG;
        
        $this->url = '/mod/facetoface/reports/facetoface_signups.php';
        $this->source = 'facetoface_signups';
        $this->defaultsortcolumn = 'userid';
        $this->shortname = 'facetoface_signups';
        $this->fullname = get_string('reportnamesignups', 'block_lp_reportbuilder');
        $this->description = '';
        $this->columns = array(
            array(
                'type' => 'face_to_face_signups',
                'value' => 'name',
                'heading' => get_string('f2fname', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
                'heading' => get_string('coursename', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'user',
                'value' => 'namelink',
                'heading' => get_string('f2fsignupsuser', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face_signups',
                'value' => 'linemanager',
                'heading' => get_string('f2fsignupslinemanager', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face_signups',
                'value' => 'specialrequirements',
                'heading' => get_string('f2fsignupsspecialrequirements', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face_signups',
                'value' => 'reasonforattending',
                'heading' => get_string('f2fsignupsreasonforattending', 'block_lp_reportbuilder'),
            ),
            array(
                'type' => 'face_to_face_signups',
                'value' => 'discountcode',
                'heading' => get_string('f2fsignupsdiscountcode', 'block_lp_reportbuilder'),
            ),
            
            
        );

        $this->filters = array(
            array(
                'type' => 'face_to_face_signups',
                'value' => 'name',
                'advanced' => 0
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
                'advanced' => 0
            ),
            array(
                'type' => 'face_to_face_signups',
                'value' => 'linemanager',
                'advanced' => 0
            ),
            array(
                'type' => 'face_to_face_signups',
                'value' => 'specialrequirements',
                'advanced' => 0
            ),
            array(
                'type' => 'face_to_face_signups',
                'value' => 'reasonforattending',
                'advanced' => 0
            ),
            array(
                'type' => 'face_to_face_signups',
                'value' => 'discountcode',
                'advanced' => 0
            ),
        );

        // no restrictions
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        $this->embeddedparams = array(
           //'base.sessionid' => optional_param('f2fsessid', '', PARAM_INT),
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
