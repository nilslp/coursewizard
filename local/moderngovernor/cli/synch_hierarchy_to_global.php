<?php
/**
 *  CLI Script to synchronise local hierarchy assignments to the global users table. Burn after reading.
 */

define('CLI_SCRIPT',true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(dirname(__FILE__)).'/lib.php');
global $CFG, $DB;

echo "\nBeginning migration of updated hierarchy info to global table ...";

$mg_cfg = get_moderngovernor_config();
$level1 = $mg_cfg->map['level1'];
$level2 = $mg_cfg->map['level2'];
$countfailed = 0;
$countsucceeded = 0;

$transaction = $DB->start_delegated_transaction();

try {     
    // update lea maps - taken from the stored procedure 
    $hierids = $DB->get_records_sql("SELECT h.id,
                                            h.oldid 
                                    FROM {lp_hierarchy} h 
                                    JOIN `{$mg_cfg->db}`.`mdl_mg_lea` l 
                                        ON h.oldid = l.lngLevel 
                                    WHERE h.parentid=0 
                                        AND l.id <> '' 
                                        AND l.instance_id=? ",
                                        array($mg_cfg->instanceid));

    foreach ($hierids as $hid) {
        $DB->execute("UPDATE `{$mg_cfg->db}`.`mdl_mg_lea` SET `{$level1}` = ? WHERE `{$level1}` = ? AND `instance_id` = ? ", array($hid->id, $hid->oldid, $mg_cfg->instanceid));
    }


    echo "\nBuilding map ... ";
    // build a map for directorates and departments:
    $depth1 = $DB->get_field('lp_hierarchy_depth','id',array('level'=>1));
    $depth2 = $DB->get_field('lp_hierarchy_depth','id',array('level'=>2));
    $directorates = $DB->get_records('lp_hierarchy',array('depthid'=>$depth1),'oldid ASC','oldid,id');
    $departments = $DB->get_records('lp_hierarchy',array('depthid'=>$depth2),'oldid ASC','oldid,id');
    echo "done!";

    // we need to update the global user table with data from the newly migrated hierarchy:
    $users = $DB->get_records('user', null, 'id ASC', 'id,email,directorateid,departmentid');
    echo "\nIterating users ... ";
    foreach ($users as $user) {
        // this script should have been run AFTER the entries in mdl_mg_lea have been updated
        $dirmap = isset($directorates[$user->directorateid]) ? $directorates[$user->directorateid] : 0;
        $depmap = isset($departments[$user->departmentid]) ? $departments[$user->departmentid] : 0;

        if (empty($dirmap) || empty($depmap)) {
            echo "\nCould not find dir/dep mapping for user with email '{$user->email}'. Skipping.";
            ++$countfailed;
            continue;
        }

        $leaid = get_moderngovernor_global_lea_id($dirmap->id);
        echo "\nUpdating user with email '{$user->email}' in LEA {$leaid}: {$level1} : {$user->directorateid} => {$dirmap->id}, {$level2} : {$user->departmentid} => {$depmap->id} ";
        if ($DB->execute("UPDATE `{$mg_cfg->db}`.`{$mg_cfg->table}` SET `{$level1}` = ?, `{$level2}` = ? WHERE email=? AND `{$level1}` = ? AND `{$level2}` = ? AND lea_id = ?",
                array($dirmap->id, $depmap->id, $user->email, $dirmap->oldid, $depmap->oldid, $leaid))) {
            ++$countsucceeded;
        } else {
            ++$countfailed;
        }    
    }
    
    $transaction->allow_commit();
} catch (Exception $e) {
    $transaction->rollback();
    echo "\nSomething went wrong: ".$e->getMessage();
    echo "\nExiting\n";
    exit(1);
}    
echo "\nDone. Succeeded: {$countsucceeded}, Failed: {$countfailed}.\n";

