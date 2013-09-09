<?php
/**
 *  CLI Script to migrate reports admins
 */

define('CLI_SCRIPT',true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
global $CFG, $DB;

// get options
$options = getopt('', array('show-children'));
$showchildren = isset($options['show-children']);

// get the list of users from rpt_admin
$admins = $DB->get_records_sql('SELECT * FROM rpt_admin');

echo "\nAttempting to migrate access for ".count($admins)." users. Showing children: " . (bool)$showchildren;

// enumerate admins and migrate access
foreach ($admins as $admin) {
    // first confirm the user exists
    if (!$DB->record_exists('user',array('id' => $admin->lnguser))) {
        continue;
    }
    
    
    // each list of ids for access will exists at different depth in the hierarchy, so run for each depth.
    // NB: if there are no entries in the field for the subsequent hierarchy level, it means that all children
    // in that level are visible to the user and $showchildren is set
    if (!empty($admin->lngdiraccesslist)) {
        migrate_hierarchy_access_for_user($admin->lnguser, $admin->lngdiraccesslist, 1, (int)(empty($admin->lngdeptaccesslist) && $showchildren));
    }
        
    if (!empty($admin->lngdeptaccesslist)) {
        migrate_hierarchy_access_for_user($admin->lnguser, $admin->lngdeptaccesslist, 2, (int)(empty($admin->lngsubaccesslist) && $showchildren));
    }
    
    if (!empty($admin->lngsubaccesslist)) {
        migrate_hierarchy_access_for_user($admin->lnguser, $admin->lngsubaccesslist, 3, 0); // show children makes no sense at this level
    }
}

echo "\nMigrated access for ".count($admins)." users.";
echo "\nDone!\n";

/**
 * Utility functions 
 */

/**
 * Takes a user id and a list of ids from the 1.9 dir/dep/sub model and adds 
 * entries for each id to the report_builder_hierarchy_access table by mapping the old
 * ids to the corresponding hierarchy ids using the depth parameter
 * 
 * @param int $userid
 * @param string $oldlist
 * @param int $depth 
 * @param int $showchildren 
 */
function migrate_hierarchy_access_for_user( $userid, $oldlist, $depth, $showchildren=0 ) {
    global $DB;
    
    if (empty($oldlist)) {
        echo("\nInvalid hierarchy list passed to migrate_hierarchy_access_for_user!");
        return;
    }
    
    // handle cases where csv not correctly formatted
    $oldlist = explode(',',$oldlist);
    array_filter($oldlist);
    $oldlist = implode(',',$oldlist);
    
    $depthid = $DB->get_field('lp_hierarchy_depth','id',array('level'=>$depth));
    if (empty($depthid)) {
        echo("\nInvalid depth passed to migrate_hierarchy_access_for_user!");
        return;
    }
    
    $record = new stdClass();
    $record->userid = $userid;
    $record->showchildren = $showchildren;
    
    $hierids = $DB->get_records_sql("SELECT DISTINCT(id) AS id FROM {lp_hierarchy} WHERE oldid IN ({$oldlist}) AND depthid=$depthid");
    if (empty($hierids)) {
        echo "\nFound no corresponding hierarchies for user $userid.";
        return;
    }
    $hierids = array_keys($hierids);
    
    echo "\nMigrating access for userid $userid ... ";
    
    // add records for each hierid
    foreach ($hierids as $hid) {
        $record->hierarchyid = $hid;
        $DB->insert_record('report_builder_hierarchy_access',$record);
    }
    
    echo " created ".count($hierids)." records.";
}









