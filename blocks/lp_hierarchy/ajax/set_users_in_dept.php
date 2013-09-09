<?php

require_once('../../../config.php');

//output is json
header('Content-type: application/json');

$leftdeptid = required_param('left_dept', PARAM_INT);
$rightdeptid = required_param('right_dept', PARAM_INT);
$movedtoleft = json_decode(required_param('moved_to_left', PARAM_TEXT));
$movedtoright = json_decode(required_param('moved_to_right', PARAM_TEXT));
echo set_users_in_depts($leftdeptid, $rightdeptid, $movedtoleft, $movedtoright);

//should this function be kept in lib??
function set_users_in_depts($leftdeptid, $rightdeptid, $movedtoleft, $movedtoright) {
    global $DB,$CFG;

    $message = '';
    
    // check if we need to replicate to the global table
    $synchierarchy = (int)get_config('local_moderngovernor','synchierarchy');
    
    // since we're (potentially) going to be doing a bit more, let's wrap things in a transaction - DPMH
    $transaction = $DB->start_delegated_transaction();
    
    //first do the ones on the left
    if (count($movedtoleft) > 0) {
        foreach($movedtoleft as $userid){
            update_profile($userid, $leftdeptid);
        }
        $moveleftstr = implode(",", $movedtoleft);
        try {
            $DB->set_field_select('lp_user_hierarchy', 'hierarchyid', $leftdeptid, "userid IN ({$moveleftstr})");
            if ($synchierarchy) {                
                $mgpath = $CFG->dirroot.'/local/moderngovernor/lib.php';
                if (file_exists($mgpath)) {
                    require_once($mgpath);
                    if (!moderngovernor_move_users($leftdeptid, $moveleftstr)) {
                        throw new Exception("Could not update hierarchy in global table!");
                    }
                }
            }
            
        } catch (Exception $e) {
            $transaction->rollback($e);
            $message .= $e->getMessage();
            return $message;
        }
    }
    //then the ones on the right
    if (count($movedtoright) > 0) {
        foreach($movedtoright as $userid){
            update_profile($userid, $rightdeptid);
        }
        $moverightstr = implode(",", $movedtoright);
        try {
            $DB->set_field_select('lp_user_hierarchy', 'hierarchyid', $rightdeptid, "userid IN ({$moverightstr})");            
            if ($synchierarchy) {                
                $mgpath = $CFG->dirroot.'/local/moderngovernor/lib.php';
                if (file_exists($mgpath)) {
                    require_once($mgpath);
                    if (!moderngovernor_move_users($rightdeptid, $moverightstr)) {
                        throw new Exception("Could not update hierarchy in global table!");                        
                    }
                }
            }
        } catch (Exception $e) {
            $transaction->rollback($e);
            $message .= $e->getMessage();
            return $message;
        }
    }
    
    // if we got this far, everything succeeded
    $transaction->allow_commit();
    
    return $message ? $message : json_encode(array("success" => true));
}

function update_profile($userid, $deptid){
    global $DB;
    //first get the dept id hierarchy by concatting the stuff in vw_lp_hierarchy_all
    $hierarchyset = $DB->get_record_sql("SELECT Concat_ws(',' , hierarchyid1, hierarchyid2, hierarchyid3 ) AS hierarchy FROM  vw_lp_hierarchy_all WHERE hierarchyid = ?", array($deptid) );
    
    if($hierarchyset){
        $userfieldset = $DB->get_record_sql("SELECT d.id AS id FROM {user_info_field} f JOIN {user_info_data} d ON f.id = d.fieldid WHERE f.shortname = ? AND d.userid = ?", array('hierarchyid', $userid));
   
        $DB->set_field_select('user_info_data', 'data', $hierarchyset->hierarchy, " id = $userfieldset->id "  );
    }
}