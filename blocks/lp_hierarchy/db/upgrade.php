<?php
function xmldb_block_lp_hierarchy_upgrade($oldversion = 0) {
    global $CFG, $DB;

    $result = true;

    $dbman = $DB->get_manager();
    
    if ($result && $oldversion < 2012032000) {
    	notify('Updating vw_lp_hierarchy_count view...','notifysuccess');

    	$sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_count`";
        $result = $DB->execute($sql);

        $sql = "CREATE VIEW `vw_lp_hierarchy_count`
                AS
                SELECT COUNT(u_active.id) as 'count', COUNT(u_deleted.id) as 'deleted_count', uh.hierarchyid
                FROM {lp_user_hierarchy} uh
                LEFT JOIN {user} u_active ON u_active.id = uh.userid AND u_active.deleted = 0
                LEFT JOIN {user} u_deleted ON u_deleted.id = uh.userid AND u_deleted.deleted = 1
                GROUP BY uh.hierarchyid";
        $result = $DB->execute($sql);
    }

    if ($result && $oldversion < 2012061100) {

        if ($DB->count_records('lp_hierarchy_depth') == 1) {
            notify('Defaulting block_lp_hierarchy_depth to 1', 'notifysuccess');

            $result = set_config('block_lp_hierarchy_depth', 1);
        }
    }

    if ($result && $oldversion < 2012062000) {

        $hierarchy_record = $DB->get_record('user_info_field', array('shortname' => 'hierarchyid'));

        if ($hierarchy_record) {
            $hierarchy_record->required = 1;

            $result = $DB->update_record('user_info_field', $hierarchy_record);

            notify('Defaulting hierarchyid as a required field', 'notifysuccess');
        }
    }

    if ($result && $oldversion < 2013022500) {        
        $table = new xmldb_table('lp_user_hierarchy');

        $index = new xmldb_index('lp_user_hierarchy_uix', XMLDB_INDEX_UNIQUE, array('userid', 'hierarchyid'));

        // Conditionally launch add the unique index
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
    }
        
    return $result;
}