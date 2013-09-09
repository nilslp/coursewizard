<?php


function render_user_search_form($buttonId, $inputTargetId, $labelTargetId, $divId = 'local_search_users') {
    global $PAGE;        

    $jsmodule = array(
        'name'     => 'local_learningpool_userpicker',
        'fullpath' => '/local/learningpool/userpicker/javascript/userpicker.js',
        'requires' => array('base', 'event', 'io', 'node', 'json', 'panel', 'dd-plugin','recordset','datatable','selector-css3','datatable-scroll'),
        'strings' => array(
            array('confirm', 'moodle'),
            array('cancel', 'moodle'),
            array('fullname', 'moodle'),
            array('email', 'moodle')
        )
    );

    $PAGE->requires->js_init_call('M.local_learningpool_userpicker.init', array($buttonId, $divId, $inputTargetId, $labelTargetId), false, $jsmodule);

    $mform = new learningpool_search_user();
    //$mform = new mod_facetoface_search_users(null,compact('sessionid'));
    echo html_writer::start_tag('div',array('style'=>'position:absolute; left: -1000px;'));
    echo html_writer::start_tag('div',array('id'=>'local_search_users'));
    echo html_writer::start_tag('div',array('id'=>'yui3-widget-bd'));
    $mform->display();
    echo html_writer::end_tag('div');
    echo html_writer::tag('div','&nbsp;', array('id'=>'searchresults','class'=>'yui-dt'));
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}

class learningpool_search_user extends moodleform {
    public function definition() {
        $mform = &$this->_form;
        $mform->addElement('hidden','type','searchusers');
        $mform->addElement('text','searchtext',get_string('fullnamecontains','local_learningpool'));
        $mform->addElement('submit','submit',get_string('search'));
    }
}
