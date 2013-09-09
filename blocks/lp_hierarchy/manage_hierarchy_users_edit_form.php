<?php
require_once($CFG->dirroot.'/lib/formslib.php');

class manage_hierarchy_users_edit_form extends moodleform {
    // Define the form
    function definition() {
        global $CFG;
        $mform =& $this->_form;

        $hierarchy = Hierarchy::get_instance();
        $items 	= $hierarchy->get_all_leaves_with_path();

        $leaves = array();
        if ($items) {
            //first entry is a blank one so we can capture "change" correctly
            $leaves[0] = "-";
            foreach ($items as $leaf) {
                $leaves[$leaf->id] = trim( $leaf->path );
            }
        }

        if (count($leaves) === 0) {
            // Ensure that at least the 'Top' value is displayed
            $leaves[0] = get_string('top', 'block_lp_hierarchy');
        }

        // Org Unit Selection
        // $mform->addElement('select', 'sourceid', get_string('label:column_a', 'block_lp_hierarchy') , $leaves, array("id"=>'sourceid'));
        // $mform->addElement('select', 'targetid', get_string('label:column_b', 'block_lp_hierarchy') , $leaves, array("id"=>'targetid'));

        // Swapping Lists with controls
        // $mform->addElement('select', 'source_users', null, null, array('multiple'=>'multiple', 'class'=>'user-list'));
        // $mform->addElement('html', '<div class="addremove">');
        // $mform->addElement('xbutton', 'lp_move_users_right', '<span>&gt;</span>', array('title'=>get_string('label:move_user_atob', 'block_lp_hierarchy')));
        // $mform->addElement('xbutton', 'addall', '<span>&raquo;</span>', array('title'=>get_string('label:move_allusers_atob', 'block_lp_hierarchy')));
        // $mform->addElement('xbutton', 'removeall', '<span>&laquo;</span>', array('title'=>get_string('label:move_allusers_btoa', 'block_lp_hierarchy')));
        // $mform->addElement('xbutton', 'lp_move_users_left', '<span>&lt;</span>', array('title'=>get_string('label:move_user_btoa','block_lp_hierarchy')));
        // $mform->addElement('html', '</div>');
        // $mform->addElement('select', 'target_users', null, null, array('multiple'=>'multiple', 'class'=>'user-list'));

        // Column A
        $grp_col_a = array();
        $grp_col_a[] =& $mform->createElement('select', 'sourceid', get_string('label:column_a', 'block_lp_hierarchy') , $leaves, array("id"=>'sourceid'));
        $grp_col_a[] =& $mform->createElement('text', 'search_source_users', get_string('label:search', 'block_lp_hierarchy'), array('class'=>'user-search', 'placeholder'=>get_string('label:search', 'block_lp_hierarchy'), 'title'=>get_string('label:search', 'block_lp_hierarchy')));
        $grp_col_a[] =& $mform->createElement('select', 'source_users', null, null, array('multiple'=>'multiple', 'class'=>'user-list'));
        $mform->addGroup($grp_col_a,'group_col_a', get_string('label:column_a', 'block_lp_hierarchy'), array(' '), false);

        // Column Controls
        $grp_col_ctrl = array();
        $grp_col_ctrl[] =& $mform->createElement('xbutton', 'lp_move_users_right', '<span>&gt;</span>', array('title'=>get_string('label:move_user_atob', 'block_lp_hierarchy')));
        $grp_col_ctrl[] =& $mform->createElement('xbutton', 'addall', '<span>&raquo;</span>', array('title'=>get_string('label:move_allusers_atob', 'block_lp_hierarchy')));
        $grp_col_ctrl[] =& $mform->createElement('xbutton', 'removeall', '<span>&laquo;</span>', array('title'=>get_string('label:move_allusers_btoa', 'block_lp_hierarchy')));
        $grp_col_ctrl[] =& $mform->createElement('xbutton', 'lp_move_users_left', '<span>&lt;</span>', array('title'=>get_string('label:move_user_btoa','block_lp_hierarchy')));
        $mform->addGroup ($grp_col_ctrl, 'group_col_ctrl', 'Controls', array(' '), false);

        // Column B
        $grp_col_b = array();
        $grp_col_b[] =& $mform->createElement('select', 'targetid', get_string('label:column_b', 'block_lp_hierarchy') , $leaves, array("id"=>'targetid'));
        $grp_col_b[] =& $mform->createElement('text', 'search_target_users', get_string('label:search', 'block_lp_hierarchy'), array('class'=>'user-search', 'placeholder'=>get_string('label:search', 'block_lp_hierarchy'),'title'=>get_string('label:search', 'block_lp_hierarchy')));
        $grp_col_b[] =& $mform->createElement('select', 'target_users', null, null, array('multiple'=>'multiple', 'class'=>'user-list'));
        $mform->addGroup($grp_col_b, 'group_col_b', get_string('label:column_b', 'block_lp_hierarchy'), array(' '), false);

        //this is instead of the get_action_buttons call so that the cancel does not submit
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('xbutton', 'submitbutton', get_string('label:savechanges','block_lp_hierarchy'), array('type'=>'submit'));
        $buttonarray[] = &$mform->createElement('xbutton', 'resetbutton', get_string('label:revert', 'block_lp_hierarchy'), array('type'=>'reset'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        // Form Legend
        $mform->addElement('html', '<div class="foot-note"><span>'.get_string('text:footnote', 'block_lp_hierarchy').'</span></div>');

    }
}
