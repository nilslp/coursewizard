<?php
require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');

/***
 *  The purpose of this method is to synchronise the lp_user_hierarchy table 
 *  with the user_info_data table's profile field
 */
function hierarchy_updated ($user) {
    $hierarchy = Hierarchy::get_instance();
    $hierarchy->sync_hierarchy($user->id);
}