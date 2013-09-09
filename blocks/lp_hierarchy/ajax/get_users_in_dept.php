<?php

//get the users in the depts according to the XHR request
require_once('../../../config.php');

header('Content-type: application/json');
$deptid = required_param('dept_id', PARAM_INT);
echo get_users_in_dept($deptid);

//should this function be in the lib file??
function get_users_in_dept($id) {
    global $DB;

    $sql = "SELECT u.id,
                CONCAT(u.lastname, ', ', u.firstname, ' (', u.email, ')') AS name,
                `deleted`
              FROM {user} u, {lp_user_hierarchy} uh
              WHERE uh.userid = u.id
              AND uh.hierarchyid = $id
              ORDER BY u.lastname";

    $results = $DB->get_records_sql($sql);
    if ($results) {

        $outarray = array();
        $outarray['users'] = array();
        $outarray["count_of_users"] = 0;
        foreach ($results as $result) {
            $outarray["count_of_users"] += 1;
            $outarray['users'][] = array("id" => $result->id, "name" => $result->name, "deleted" => $result->deleted);
        }

        return json_encode($outarray);
    }
    return '{"count_of_users": 0}';
}
