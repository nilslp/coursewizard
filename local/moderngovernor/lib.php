<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');

function populate_user_info($user){
    global $DB;

    if (!(int)get_config('local_moderngovernor','synchierarchy')) {
        return;
    }
    
    $cfg = get_moderngovernor_config();    
    $level1 = $cfg->map['level1'];
    $level2 = $cfg->map['level2'];
    
    $result = $DB->get_record_sql("SELECT `$level1`, `$level2` FROM `{$cfg->db}`.`{$cfg->table}` WHERE email = ?", array('email' => $user->email));
    $fieldid = $DB->get_field('user_info_field','id',array('shortname'=>'hierarchyid'));
    $insert = false;
    
    if (!$record = $DB->get_record('user_info_data', array('userid' => $user->id, 'fieldid'=>$fieldid))) {
        $record = new object();
        $record->userid = $user->id;
        $record->fieldid = $fieldid;     
        $insert = true;
    }
    
    $record->data = $result->$level1.','.$result->$level2;   
    
    if ($insert) {
        $DB->insert_record('user_info_data', $record, true);	
    }else{
        $DB->update_record('user_info_data', $record, true);
    }
    
    $hierarchy = Hierarchy::get_instance();
    $hierarchy->sync_hierarchy($user->id);

}

function get_moderngovernor_config() {
    global $CFG, $DB;
    
    static $cfg = false;
    
    if (!empty($cfg)) {
        return $cfg;
    }
    
    if (!is_enabled_auth('db')) {
        print_error(get_string('error:authdbfailed','local_moderngovernor',$CFG->wwwroot.'/admin/settings.php?section=manageauths'));
    }
    
    $dbauth = get_config('auth/db');
    
    if ($CFG->dbhost != $dbauth->host) {        
        print_error(get_string('error:authdbinvalidhost','local_moderngovernor'));
    }
    
    $instancename = get_config('local_moderngovernor','instancename');
    if (empty($instancename)) {
        print_error(get_string('error:noinstancename','local_moderngovernor'));
    }
    
    $cfg = new object();
    $cfg->db = $dbauth->name;
    $cfg->table = $dbauth->table;
    $cfg->leatable = 'mdl_mg_lea';
    $cfg->instanceid = $DB->get_field_sql("SELECT id FROM {$dbauth->name}.mdl_instance WHERE name = ? ", array($instancename)); // required to find the correct lea_id
    $cfg->map = array(
        'level1' => strtolower($dbauth->field_map_directorateid),
        'level2' => strtolower($dbauth->field_map_departmentid)
        );    
    
    return $cfg;
}

function get_moderngovernor_leas_for_select() {
    global $DB;
    
    $depthid = $DB->get_field('lp_hierarchy_depth','id',array('level'=>1));    
    $results = $DB->get_records('lp_hierarchy', array('depthid'=>$depthid),'id,fullname');
    $options = array(get_string('selectlea','local_moderngovernor'));
    foreach ($results as $r) {
        $options[$r->id] = $r->fullname;
    }
    
    return $options;
}

function get_moderngovernor_school_count($lea='',$search='',$status=-1) {
    global $DB;
        
    $depthid = $DB->get_field('lp_hierarchy_depth','id',array('level'=>2));
    $sql = "SELECT COUNT('x') FROM {lp_hierarchy} WHERE depthid={$depthid} ";
    $params = null;
    
    if (!empty($search)) {
        $search = '%'.implode('%',explode(' ', $search)).'%';
        $sql .= " AND fullname LIKE ? ";
        $params = array($search);
    }
    
    if (!empty($lea)) {
        $sql .= " AND parentid = {$lea} ";
    }
    
    if ($status >= 0) {
        $sql .= " AND visible = {$status} ";
    }
    
    return $DB->count_records_sql($sql, $params);
}

function get_moderngovernor_schools($lea='',$search='',$status=-1,$start=0,$count=50,$sort='') {
    global $DB;
    
    $depthid = $DB->get_field('lp_hierarchy_depth','id',array('level'=>2));
    
    $enabled = get_string('enabled', 'local_moderngovernor');
    $disabled = get_string('disabled', 'local_moderngovernor');
    $params = null;
    
    $sql = "SELECT  l2.id,
                    l2.parentid,
                    l2.fullname AS school,
                    l1.fullname AS lea,
                    IF(l2.visible,'{$enabled}','{$disabled}') AS status,
                    l2.visible
               FROM {lp_hierarchy} l2 
               JOIN {lp_hierarchy} l1
                    ON l1.id=l2.parentid
              WHERE l2.depthid={$depthid} ";    
    
    if (!empty($search)) {
        $search = '%'.implode('%',explode(' ', $search)).'%';
        $sql .= " AND l2.fullname LIKE ? ";
        $params = array($search);
    }
    
    if (!empty($lea)) {
        $sql .= " AND l2.parentid = {$lea} ";
    }
    
    if ($status >= 0) {
        $sql .= " AND l2.visible = {$status} ";
    }
    
    if (!empty($sort)) {
        $sql .= " ORDER BY {$sort} ";
    }
    
    $sql .= " LIMIT $start,$count ";        
    
    $records = $DB->get_records_sql($sql, $params);
    $data = array();
    $item = array();
    foreach ($records as $row) {            
        foreach ($row as $index => $val) {
            $item[$index] = '<span class="'.$index.'">'.$val.'</span>';
        }
        $item ['checkbox']= get_moderngovernor_school_checkbox($row->id);
        $item ['options']= get_moderngovernor_school_options($row->id, $row->visible);
        $data []= $item;
    }
    
    return $data;
}

function get_moderngovernor_school_options($schoolid, $status) {
    global $CFG, $OUTPUT;
        
    $options = array();
    if ($status) {
        $icon = html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url('i/hide')), get_string('disable', 'local_moderngovernor'));
        $options []= html_writer::link('javascript:void(0);', $icon, array('title' => get_string('disable', 'local_moderngovernor'), 'class' => 'enabled toggleschool', 'id' => "update_sch_{$schoolid}"));
    } else {
        $icon = html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url('i/show')), get_string('enable', 'local_moderngovernor'));
        $options []= html_writer::link('javascript:void(0);', $icon, array('title' => get_string('enable', 'local_moderngovernor'), 'class' => 'disabled toggleschool', 'id' => "update_sch_{$schoolid}"));
    }
    
    $icon = html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url('i/edit')), get_string('edit'));
    $options []= html_writer::link($CFG->wwwroot.'/blocks/lp_hierarchy/org_unit/edit.php?id='.$schoolid, $icon, array('title' => get_string('edit')));
        
    return implode('', $options);
}

function get_moderngovernor_user_options($userid, $confirmed, $synched=0) {
    global $CFG, $OUTPUT;
    
    $ret = array();
    
    if ($synched) {
        $icon = html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url('t/reload')), get_string('resetuserpass', 'local_moderngovernor'));
        $ret []= html_writer::link('javascript:void(0);', $icon, array('title' => get_string('resetuserpass', 'local_moderngovernor'), 'class' => 'reset useraction', 'id' => "reset_{$userid}"));
    }
    
    if (!$confirmed) {
        $icon = html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url('i/tick_green_small')), get_string('confirmuser', 'local_moderngovernor'));
        $ret []= html_writer::link('javascript:void(0);', $icon, array('title' => get_string('confirmuser', 'local_moderngovernor'), 'class' => 'confirm useraction', 'id' => "confirm_{$userid}"));
    }
    
    return implode('',$ret);
}

function get_moderngovernor_school_checkbox($schoolid) {
    $cbname = "sch_{$schoolid}";
    return html_writer::checkbox($cbname, "", false, '', array('id' => $cbname));
}

function get_moderngovernor_global_lea_id($hierarchyid=false) {
    global $DB;
    
    $mg_cfg = get_moderngovernor_config();
    
    if (!empty($hierarchyid)) {
        return $DB->get_field_sql("SELECT id FROM {$mg_cfg->db}.mdl_mg_lea WHERE instance_id=? AND lngLevel=?", array($mg_cfg->instanceid, $hierarchyid));
    } else {
        return $DB->get_records_sql("SELECT id FROM {$mg_cfg->db}.mdl_mg_lea WHERE instance_id=?", array($mg_cfg->instanceid));
    }
}

function get_moderngovernor_lea_insql() {
    $leas = array_keys(get_moderngovernor_global_lea_id());
    // append param for safe insql
    $leas []= -1;
    return implode(",",$leas);
}

function moderngovernor_toggle_school($schoolid) {
    global $DB, $CFG;
    
    $success = false;
    $mg_cfg = get_moderngovernor_config();
    $level1 = $mg_cfg->map['level1'];
    $level2 = $mg_cfg->map['level2'];
    $append = ".DELETED";
    
    // school must exist ...
    if (!$school = $DB->get_record('lp_hierarchy',array('id'=>$schoolid))) {
        return false;
    }
    
    // get the correct lea id
    $leaid = get_moderngovernor_global_lea_id($school->parentid);
    
    // get users for update
    $users = $DB->get_records_sql("SELECT * FROM {$mg_cfg->db}.{$mg_cfg->table} WHERE `lea_id`=? AND `{$level1}`=? AND `{$level2}`=?",array($leaid, $school->parentid, $school->id));
            
    // capture all in a transaction
    $transaction = $DB->start_delegated_transaction();
    
    try {        
        if ((int)$school->visible) {
            // we're disabling ...
            $success = $DB->set_field('lp_hierarchy','visible',0,array('id'=>$schoolid));
            
            // update users in the global table - check for existence of .DELETED in fields first
            //  - thinking that this is faster to check in php than using SQL?
            foreach ($users as $user) {
                $username = (false === strrpos($user->username,$append)) ? $user->username.$append : $user->username;
                $password = (false === strrpos($user->password,$append)) ? $user->password.$append : $user->password;
                $DB->execute("UPDATE {$mg_cfg->db}.{$mg_cfg->table} SET username=?,password=? WHERE id=?",array($username, $password, $user->id));                
            }                        
            
        } else {
            // we're reviving
            $success = $DB->set_field('lp_hierarchy','visible',1,array('id'=>$schoolid));
            
            // update users in the global table - check for existence of .DELETED in fields first
            foreach ($users as $user) {
                $username = (false === strrpos($user->username,$append)) ? $user->username : str_replace($append, '', $user->username);
                $password = (false === strrpos($user->password,$append)) ? $user->password : str_replace($append, '', $user->password);
                $DB->execute("UPDATE {$mg_cfg->db}.{$mg_cfg->table} SET username=?,password=? WHERE id=?",array($username, $password, $user->id));                
            }                        
        }
        
        // exceptions would've killed us by now
        $success = true;
        $transaction->allow_commit();
    } catch (Exception $e) {
        $success = false;
        $transaction->rollback();
    }    
    
    return $success;
}

function moderngovernor_combine_schools($newname, $newlea, $schoolids) {    
    global $DB, $CFG, $USER;
    
    $success = false;
    $mg_cfg = get_moderngovernor_config();
    $level1 = $mg_cfg->map['level1'];
    $level2 = $mg_cfg->map['level2'];
    $depthid = $DB->get_field('lp_hierarchy_depth','id',array('level'=>2));
    
    // lea must exist ...
    if (!$newlea = $DB->get_record('lp_hierarchy', array('id' => $newlea))) {
        return false;
    }
    
    // validate school ids for "select ... in"
    $schoolids = explode(',', $schoolids);
    $schoolids = array_filter($schoolids);
    $schoolids = implode(',',$schoolids);
    
    // get schools and parents ...
    $schools = $DB->get_records_sql("SELECT id,parentid FROM {lp_hierarchy} WHERE id IN ({$schoolids})");
        
    // get all users from the global table that are in the selected schools
    $usersql = "SELECT * FROM {$mg_cfg->db}.{$mg_cfg->table} WHERE ";
    
    // construct a few OR clauses 
    $schoolsql = array();
    foreach ($schools as $school) {
        // get the correct lea id
        $leaid = get_moderngovernor_global_lea_id($school->parentid);
        $schoolsql []= " ( lea_id = {$leaid} AND {$level1} = {$school->parentid} AND {$level2} = {$school->id} ) ";
    }    
    $usersql .= implode('OR',$schoolsql);
    
    // get global users for update ...
    $gusers = $DB->get_records_sql($usersql);
    
    // ... and local users
    $lusers = $DB->get_records_select('lp_user_hierarchy', "hierarchyid IN ($schoolids)");    
    
    // need sort order for instance
    $sortorder = $DB->get_field_sql("SELECT MAX(sortorder)+1 AS maxsortorder FROM {lp_hierarchy}");
    $time = time();
    $fieldid = $DB->get_field('user_info_field','id',array('shortname'=>'hierarchyid'));
    
    // capture all in a transaction
    $transaction = $DB->start_delegated_transaction();
        
    try {        
        // first create the new hierarchy record ...
        $newschool = new object();
        $newschool->shortname = 'dep_'.strtolower(str_replace(' ', '_', $newname)).'_'.$time; 
        $newschool->fullname = $newname;
        $newschool->parentid = $newlea->id;
        $newschool->depthid = $depthid;
        $newschool->timemodified = $time;
        $newschool->modifierid = $USER->id;
        $newschool->visible = 1;
        $newschool->sortorder = $sortorder;
        $newschool->timecreated = $time;
        
         // Increment the sortorder for all other items 
        $DB->execute("UPDATE {lp_hierarchy} SET sortorder=sortorder+1 WHERE sortorder > {$sortorder}");
        
        // insert the record and save the id - we need it to update the path!
        $newschool->id = $DB->insert_record('lp_hierarchy', $newschool);
        $newschool->path = $newlea->path."/".$newschool->id;
        $DB->set_field('lp_hierarchy', 'path', $newschool->path, array('id' => $newschool->id));      
        
        // keep the hier path for the user records
        $userpath = $newlea->id.','.$newschool->id;
        
        // update users in the global table 
        foreach ($gusers as $user) {
            $DB->execute("UPDATE {$mg_cfg->db}.{$mg_cfg->table} SET {$level1}=?,{$level2}=? WHERE id=?",array($newlea->id, $newschool->id, $user->id));                
        }            
        
        // update local users 
        $hierarchy = Hierarchy::get_instance();
        foreach ($lusers as $user) {            
            $insert = false;            
            if (!$record = $DB->get_record('user_info_data', array('userid' => $user->userid, 'fieldid'=>$fieldid))) {
                // need to create record if it does not yet exist!
                $record = new object();
                $record->userid = $user->userid;
                $record->fieldid = $fieldid;     
                $insert = true;
            }
            $record->data = $userpath;   

            if ($insert) {
                $DB->insert_record('user_info_data', $record);	
            }else{
                $DB->update_record('user_info_data', $record);
            }            
            
            $hierarchy->sync_hierarchy($user->userid);
        }
        
        // finally, remove the old hierarchy entries ...
        $DB->execute("DELETE FROM {lp_hierarchy} WHERE id IN ($schoolids)");
        
        // exceptions would've killed us by now
        $success = true;
        $transaction->allow_commit();
    } catch (Exception $e) {
        $success = false;
        $transaction->rollback($e);
    }    
    
    return $success;
}

function moderngovernor_undelete_user($userid) {
    global $CFG, $DB;
    
    $mg_cfg = get_moderngovernor_config();
    $append = ".DELETED";
    $user = $DB->get_record_sql("SELECT u.id,u.email,h.hierarchyid FROM {user} u JOIN {lp_user_hierarchy} h ON h.userid=u.id WHERE u.id=?", array($userid));
    if (!empty($user)) {
        $lealist = get_moderngovernor_lea_insql();
        $guser = $DB->get_record_sql("SELECT id,username,password FROM `{$mg_cfg->db}`.`{$mg_cfg->table}` WHERE email=? AND lea_id IN ($lealist) ",array($user->email));
        if (!empty($guser)) {
            $username = (false === strrpos($guser->username, $append)) ? $guser->username : str_replace($append, '', $guser->username);
            $password = (false === strrpos($guser->password, $append)) ? $guser->password : str_replace($append, '', $guser->password);
            return $DB->execute("UPDATE `{$mg_cfg->db}`.`{$mg_cfg->table}` SET username=?,password=? WHERE id=?",array($username,$password,$guser->id));
        }
    }
    
    return false;
}

function moderngovernor_delete_user($userid) {
    global $CFG, $DB;
    
    $mg_cfg = get_moderngovernor_config();
    $append = ".DELETED";
    $user = $DB->get_record_sql("SELECT u.id,u.email,h.hierarchyid FROM {user} u JOIN {lp_user_hierarchy} h ON h.userid=u.id WHERE u.id=?", array($userid));
    if (!empty($user)) {
        $lealist = get_moderngovernor_lea_insql();
        $guser = $DB->get_record_sql("SELECT id,username,password FROM `{$mg_cfg->db}`.`{$mg_cfg->table}` WHERE email=? AND lea_id IN ($lealist) ",array($user->email));
        if (!empty($guser)) {
            $username = (false === strrpos($guser->username, $append)) ? $guser->username.$append : $guser->username;
            $password = (false === strrpos($guser->password, $append)) ? $guser->password.$append : $guser->password;
            return $DB->execute("UPDATE `{$mg_cfg->db}`.`{$mg_cfg->table}` SET username=?,password=? WHERE id=?",array($username,$password,$guser->id));
        }
    }
    
    return false;
}

function get_moderngovernor_unconfirmed_users_count($search='', $status=-1) {
    global $DB;
   
    $mg_cfg = get_moderngovernor_config();
    
    $leas = $DB->get_field_sql("SELECT GROUP_CONCAT(id) FROM `{$mg_cfg->db}`.`mdl_mg_lea` WHERE instance_id = ?",array($mg_cfg->instanceid));
    
    if (empty($leas)) {
        return 0;
    }
    
    $sql = "SELECT COUNT('x') FROM `{$mg_cfg->db}`.`{$mg_cfg->table}` u WHERE u.lea_id IN ({$leas})  ";
    $params = null;
    
    if (!empty($search)) {
        $search = '%'.implode('%',explode(' ', $search)).'%';
        $sql .= " AND u.email LIKE ? ";
        $params = array($search);
    }
        
    if ($status >= 0) {
        $sql .= " AND u.confirmed = {$status} ";
    }
    
    return $DB->count_records_sql($sql, $params);    
}

function moderngovernor_get_unconfirmed_users($search='', $status=-1, $start=0, $count=50, $sort='') {
    global $DB;
   
    $mg_cfg = get_moderngovernor_config();
    $depthid = $DB->get_field('lp_hierarchy_depth','id',array('level'=>2));
    $level2 = $mg_cfg->map['level2'];
    $confirmed = get_string('confirmed', 'local_moderngovernor');
    $unconfirmed = get_string('unconfirmed', 'local_moderngovernor');    
    
    $leas = $DB->get_field_sql("SELECT GROUP_CONCAT(id) FROM `{$mg_cfg->db}`.`mdl_mg_lea` WHERE instance_id = ?",array($mg_cfg->instanceid));
    
    if (empty($leas)) {
        return 0;
    }
    
    $sql = "SELECT u.id,
                   u.username,
                   u.email,
                   l2.fullname AS school,
                   l1.fullname AS lea,
                   u.confirmed,
                   IF(u.confirmed,'{$confirmed}','{$unconfirmed}') AS status
              FROM `{$mg_cfg->db}`.`{$mg_cfg->table}` u 
              JOIN {lp_hierarchy} AS l2
                ON l2.id=u.`{$level2}` AND l2.depthid={$depthid}
              JOIN {lp_hierarchy} AS l1
                ON l1.id=l2.parentid
             WHERE u.lea_id IN ({$leas}) ";
             
    $params = null;
    
    if (!empty($search)) {
        $search = '%'.implode('%',explode(' ', $search)).'%';
        $sql .= " AND u.email LIKE ? ";
        $params = array($search);
    }
    
    if ($status >= 0) {
        $sql .= "  AND u.confirmed = {$status} ";
    }
    
    if (!empty($sort)) {
        $sql .= " ORDER BY {$sort} ";
    }
    
    $sql .= " LIMIT $start,$count ";      
    
    $records = $DB->get_records_sql($sql, $params);
    $synched = (int)  get_config('local_moderngovernor', 'synchierarchy');
    $data = array();
    $item = array();
    foreach ($records as $row) {            
        foreach ($row as $index => $val) {
            $item[$index] = '<span class="'.$index.'">'.$val.'</span>';
        }
        $item ['options']= get_moderngovernor_user_options($row->id, $row->confirmed, $synched);
        $data []= $item;
    }
    
    return $data;        
}

function moderngovernor_confirm_user($userid) {
    global $CFG, $DB;
    
    $mg_cfg = get_moderngovernor_config();
    return $DB->execute("UPDATE `{$mg_cfg->db}`.`{$mg_cfg->table}` SET confirmed=?, rand_key= ? WHERE id=?", array(1,'',$userid));
}

function moderngovernor_reset_user($userid) {
    global $CFG, $DB;
    
    $mg_cfg = get_moderngovernor_config();
    $password = md5('welcome');
    return $DB->execute("UPDATE `{$mg_cfg->db}`.`{$mg_cfg->table}` SET password=? WHERE id=?", array($password, $userid));
}

function moderngovernor_move_users($newschool, $userlist='') {
    global $CFG, $DB;
    
    $userlist = explode(',', $userlist);
    $userlist = array_filter($userlist);
    $userlist = implode(',',$userlist);
    if (empty($userlist)) {
        return false;
    }
    
    $emaillist = $DB->get_records_select('user', "  id IN ($userlist) ", null, '', 'email');
    if (empty($emaillist)) {
        return false;
    }
    
    $emaillist = "'".implode("','",array_keys($emaillist))."'";    
    $lealist = get_moderngovernor_lea_insql();
    $mg_cfg = get_moderngovernor_config();
    return $DB->execute("UPDATE `{$mg_cfg->db}`.`{$mg_cfg->table}` SET school_id=? WHERE email IN ($emaillist) AND lea_id IN ($lealist)", array($newschool));
}
