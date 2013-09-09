<?php
function xmldb_block_lp_hierarchy_install() {
    global $CFG, $DB, $OUTPUT;

    $result = true;
   
    // This is the initial set-up of the views that are used to render a 
    // hierarchy to five levels.  Currently views can't be configured
    // from the install.xml so for the meantime this code is only compatible
    // with MySQL.
    if ($result) {
    	echo $OUTPUT->notification('Adding vw_lp_hierarchy_level1 view...','notifysuccess');

    	$sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_level1`";
        $result = $DB->execute($sql);
    
        $sql = "CREATE VIEW `vw_lp_hierarchy_level1`
                AS
                SELECT 
                    0 AS `parentid`,
                    `h`.`id` AS `hierarchyid`,
                    `h`.`id` AS `hierarchyid1`, 
                    `h`.`fullname` AS `level1`,
                    NULL AS `hierarchyid2`,
                    NULL AS `level2`,
                    NULL AS `hierarchyid3`,
                    NULL AS `level3`,
                    NULL AS `hierarchyid4`,
                    NULL AS `level4`,
                    NULL AS `hierarchyid5`, 
                    NULL AS `level5`
                FROM `{$CFG->prefix}lp_hierarchy` `h` 
                JOIN `{$CFG->prefix}lp_hierarchy_depth` `hd` ON `h`.`depthid` = `hd`.`id`
                WHERE `hd`.`level` = 1";

        $result = $DB->execute($sql);
  
        echo $OUTPUT->notification('Adding vw_lp_hierarchy_level2 view...','notifysuccess');
    	
        $sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_level2`";
        $result = $DB->execute($sql);
        
        $sql = "CREATE VIEW `vw_lp_hierarchy_level2`
                AS
                SELECT 
                    `h`.`parentid` AS `parentid`,
                    `h`.`id` AS `hierarchyid`,
                    `l1`.`hierarchyid` AS `hierarchyid1`, 
                    `l1`.`level1` AS `level1`, 
                    `h`.`id` AS `hierarchyid2`, 
                    `h`.`fullname` AS `level2`,
                    NULL AS `hierarchyid3`,
                    NULL AS `level3`,
                    NULL AS `hierarchyid4`,
                    NULL AS `level4`,
                    NULL AS `hierarchyid5`, 
                    NULL AS `level5`
                FROM `{$CFG->prefix}lp_hierarchy` `h` 
                JOIN `{$CFG->prefix}lp_hierarchy_depth` `hd` ON `h`.`depthid` = `hd`.`id`
                JOIN `vw_lp_hierarchy_level1` `l1` ON `l1`.`hierarchyid` = `h`.`parentid` 
                WHERE `hd`.`level` = 2";

        $result = $DB->execute($sql);
        
        echo $OUTPUT->notification('Adding vw_lp_hierarchy_level3 view...','notifysuccess');
        
        $sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_level3`";
        $result = $DB->execute($sql);
        
        $sql = "CREATE VIEW `vw_lp_hierarchy_level3`
                AS
                SELECT `h`.`parentid` AS `parentid`,
                    `h`.`id` AS `hierarchyid`,
                    `l1`.`hierarchyid` AS `hierarchyid1`,
                    `l1`.`level1` AS `level1`, 
                    `l2`.`hierarchyid` AS `hierarchyid2`,
                    `l2`.`level2` AS `level2`,
                    `h`.`id` AS `hierarchyid3`,
                    `h`.`fullname` AS `level3`,
                    NULL AS `hierarchyid4`, 
                    NULL AS `level4`,
                    NULL AS `hierarchyid5`, 
                    NULL AS `level5`
                FROM `{$CFG->prefix}lp_hierarchy` `h` 
                JOIN `{$CFG->prefix}lp_hierarchy_depth` `hd` ON `h`.`depthid` = `hd`.`id`
                JOIN `vw_lp_hierarchy_level2` `l2` ON `l2`.`hierarchyid` = `h`.`parentid` 
                JOIN `vw_lp_hierarchy_level1` `l1` ON `l1`.`hierarchyid` = `l2`.`parentid`
                WHERE `hd`.`level` = 3";

        $result = $DB->execute($sql);
        
        echo $OUTPUT->notification('Adding vw_lp_hierarchy_level4 view...','notifysuccess');        
        
        $sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_level4`";
        $result = $DB->execute($sql);
        
        $sql = "CREATE VIEW `vw_lp_hierarchy_level4`
                AS
                SELECT `h`.`parentid` AS `parentid`,
                    `h`.`id` AS `hierarchyid`,
                    `l1`.`hierarchyid` AS `hierarchyid1`,
                    `l1`.`level1` AS `level1`, 
                    `l2`.`hierarchyid` AS `hierarchyid2`,
                    `l2`.`level2` AS `level2`,
                    `l3`.`hierarchyid` AS `hierarchyid3`,
                    `l3`.`level3` AS `level3`,
                    `h`.`id` AS `hierarchyid4`,
                    `h`.`fullname` AS `level4`,
                    NULL AS `hierarchyid5`, 
                    NULL AS `level5`
                FROM `{$CFG->prefix}lp_hierarchy` `h` 
                JOIN `{$CFG->prefix}lp_hierarchy_depth` `hd` ON `h`.`depthid` = `hd`.`id`
                JOIN `vw_lp_hierarchy_level3` `l3` ON `l3`.`hierarchyid` = `h`.`parentid` 
                JOIN `vw_lp_hierarchy_level2` `l2` ON `l2`.`hierarchyid` = `l3`.`parentid` 
                JOIN `vw_lp_hierarchy_level1` `l1` ON `l1`.`hierarchyid` = `l2`.`parentid`
                WHERE `hd`.`level` = 4";
        
        $result = $DB->execute($sql);
        
        echo $OUTPUT->notification('Adding vw_lp_hierarchy_level5 view...','notifysuccess');
        
        $sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_level5`";
        $result = $DB->execute($sql);                

        $sql = "CREATE VIEW `vw_lp_hierarchy_level5`
                AS
                SELECT `h`.`parentid` AS `parentid`,
                    `h`.`id` AS `hierarchyid`,
                    `l1`.`hierarchyid` AS `hierarchyid1`,
                    `l1`.`level1` AS `level1`, 
                    `l2`.`hierarchyid` AS `hierarchyid2`,
                    `l2`.`level2` AS `level2`,
                    `l3`.`hierarchyid` AS `hierarchyid3`,
                    `l3`.`level3` AS `level3`,
                    `l4`.`hierarchyid` AS `hierarchyid4`,
                    `l4`.`level4` AS `level4`,
                    `h`.`id` AS `hierarchyid5`,
                    `h`.`fullname` AS `level5`
                FROM `{$CFG->prefix}lp_hierarchy` `h` 
                JOIN `{$CFG->prefix}lp_hierarchy_depth` `hd` ON `h`.`depthid` = `hd`.`id`
                JOIN `vw_lp_hierarchy_level4` `l4` ON `l4`.`hierarchyid` = `h`.`parentid` 
                JOIN `vw_lp_hierarchy_level3` `l3` ON `l3`.`hierarchyid` = `h`.`parentid` 
                JOIN `vw_lp_hierarchy_level2` `l2` ON `l2`.`hierarchyid` = `l3`.`parentid` 
                JOIN `vw_lp_hierarchy_level1` `l1` ON `l1`.`hierarchyid` = `l2`.`parentid`
                WHERE `hd`.`level` = 5";

        $result = $DB->execute($sql);

        echo $OUTPUT->notification('Adding vw_lp_hierarchy_all view...','notifysuccess');
        
        $sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_all`";
        $result = $DB->execute($sql);                
        
        $sql = "CREATE VIEW `vw_lp_hierarchy_all`
                AS
                SELECT parentid, hierarchyid, hierarchyid1, level1, hierarchyid2, level2, hierarchyid3, level3, hierarchyid4, level4, hierarchyid5, level5
                FROM vw_lp_hierarchy_level1
                UNION
                SELECT parentid, hierarchyid, hierarchyid1, level1, hierarchyid2, level2, hierarchyid3, level3, hierarchyid4, level4, hierarchyid5, level5
                FROM vw_lp_hierarchy_level2
                UNION
                SELECT parentid, hierarchyid, hierarchyid1, level1, hierarchyid2, level2, hierarchyid3, level3, hierarchyid4, level4, hierarchyid5, level5
                FROM vw_lp_hierarchy_level3
                UNION
                SELECT parentid, hierarchyid, hierarchyid1, level1, hierarchyid2, level2, hierarchyid3, level3, hierarchyid4, level4, hierarchyid5, level5
                FROM vw_lp_hierarchy_level4
                UNION
                SELECT parentid, hierarchyid, hierarchyid1, level1, hierarchyid2, level2, hierarchyid3, level3, hierarchyid4, level4, hierarchyid5, level5
                FROM vw_lp_hierarchy_level5";

        $result = $DB->execute($sql);
        
        echo $OUTPUT->notification('Adding vw_lp_hierarchy_freetext view...','notifysuccess');
        
        $sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_freetext`";
        $result = $DB->execute($sql);     
        
        $sql = "CREATE VIEW `vw_lp_hierarchy_freetext`
                AS
                SELECT uh.userid, 
                    l2.hierarchyid1,
                    l2.level1,
                    CASE uh.freetext 
                        WHEN NULL THEN hierarchyid2
                        ELSE NULL
                    END AS 'hierarchyid2',
                    CASE uh.freetext 
                        WHEN NULL THEN level2 
                        ELSE uh.freetext
                    END AS 'level2',
                    l2.hierarchyid3,
                    l2.level3,
                    l2.hierarchyid4,
                    l2.level4,
                    l2.hierarchyid5,
                    l2.level5
                FROM {$CFG->prefix}lp_user_hierarchy uh
                INNER JOIN vw_lp_hierarchy_level2 l2 ON l2.parentid = uh.hierarchyid
                UNION
                SELECT uh.userid, 
                    l3.hierarchyid1,
                    l3.level1,
                    l3.hierarchyid2,
                    l3.level2,
                    CASE uh.freetext 
                        WHEN NULL THEN hierarchyid3
                        ELSE NULL
                    END AS 'hierarchyid3',
                    CASE uh.freetext 
                        WHEN NULL THEN level3 
                        ELSE uh.freetext
                    END AS 'level3',
                    l3.hierarchyid4,
                    l3.level4,
                    l3.hierarchyid5,
                    l3.level5
                FROM {$CFG->prefix}lp_user_hierarchy uh
                INNER JOIN vw_lp_hierarchy_level3 l3 ON l3.parentid = uh.hierarchyid
                UNION
                SELECT uh.userid, 
                    l4.hierarchyid1,
                    l4.level1,
                    l4.hierarchyid2,
                    l4.level2,
                    l4.hierarchyid3,
                    l4.level3,
                    CASE uh.freetext 
                        WHEN NULL THEN hierarchyid4
                        ELSE NULL
                    END AS 'hierarchyid4',
                    CASE uh.freetext 
                        WHEN NULL THEN level4 
                        ELSE uh.freetext
                    END AS 'level4',
                    l4.hierarchyid5,
                    l4.level5
                FROM {$CFG->prefix}lp_user_hierarchy uh
                INNER JOIN vw_lp_hierarchy_level4 l4 ON l4.parentid = uh.hierarchyid
                UNION
                SELECT uh.userid, 
                    l5.hierarchyid1,
                    l5.level1,
                    l5.hierarchyid2,
                    l5.level2,
                    l5.hierarchyid3,
                    l5.level3,
                    l5.hierarchyid4,
                    l5.level4,
                    CASE uh.freetext 
                        WHEN NULL THEN hierarchyid5
                        ELSE NULL
                    END AS 'hierarchyid5',
                    CASE uh.freetext 
                        WHEN NULL THEN level5 
                        ELSE uh.freetext
                    END AS 'level5'
                FROM {$CFG->prefix}lp_user_hierarchy uh
                INNER JOIN vw_lp_hierarchy_level5 l5 ON l5.parentid = uh.hierarchyid";
        
        $result = $DB->execute($sql);        
    }
    
    if ($result) {
    	echo $OUTPUT->notification('Adding new profile fields...','notifysuccess');
    	
    	// Insert the category
    	$new_category = new object();
    	
    	$new_category->name = 'Directorate / Department / Sub-department';    	
        $new_category->sortorder = $DB->count_records('user_info_category') + 1;
        
        $categoryid = $DB->insert_record('user_info_category', $new_category, true);
        
        // Insert the new profile field to store the hierarchy
        $new_field = new object();
        
        $new_field->shortname = 'hierarchyid';
        $new_field->name = 'Hierarchy';
        $new_field->datatype = 'hierarchy';
        $new_field->description = '<p>Used by the LP Hierarchy plug-in to store details of the position within an organisation</p>';
        $new_field->descriptionformat = 1;
        $new_field->categoryid = $categoryid;
        $new_field->sortorder = $DB->count_records('user_info_field') + 1;
        $new_field->required = 0;
        $new_field->locked = 1;
        $new_field->signup = 1;
        $new_field->visible = 2;
        
        $result = $DB->insert_record('user_info_field', $new_field, false);
    }
    
    if ($result) {
    	echo $OUTPUT->notification('Adding vw_lp_hierarchy_count view...','notifysuccess');
        
        $sql = "DROP VIEW IF EXISTS `vw_lp_hierarchy_count`";
        $result = $DB->execute($sql);     
        
        $sql = "CREATE VIEW `vw_lp_hierarchy_count`
                AS
                SELECT COUNT(u_active.id) as 'count', COUNT(u_deleted.id) as 'deleted_count', uh.hierarchyid
                FROM {$CFG->prefix}lp_user_hierarchy uh
                LEFT JOIN {$CFG->prefix}user u_active ON u_active.id = uh.userid AND u_active.deleted = 0
                LEFT JOIN {$CFG->prefix}user u_deleted ON u_deleted.id = uh.userid AND u_deleted.deleted = 1
                GROUP BY uh.hierarchyid";
                
        $result = $DB->execute($sql);  
    }
    
    if ($result) {
    	echo $OUTPUT->notification('Adding profile field to allow free-text hierarchy level...','notifysuccess');
    	
    	$field_record = $DB->get_record('user_info_field', array('datatype' => 'hierarchy'));
    	
    	$categoryid = $field_record->categoryid;
    	
        // Insert the new profile field to store the hierarchy
        $new_field = new object();
        
        $new_field->shortname = 'hierarchytext';
        $new_field->name = 'Hierarchy Text';
        $new_field->datatype = 'hierarchytext';
        $new_field->description = '<p>Used by the LP Hierarchy plug-in to store details of any free-text hierarchy levels</p>';
        $new_field->descriptionformat = 1;
        $new_field->categoryid = $categoryid;
        $new_field->sortorder = $DB->count_records('user_info_field') + 1;
        $new_field->required = 0;
        $new_field->locked = 0;
        $new_field->visible = 2;
        
        $result = $DB->insert_record('user_info_field', $new_field, false);    	
    }
    
    if ($result) {
        echo $OUTPUT->notification('Adding vw_lp_user_hierarchy_view...','notifysuccess');
    	
        $sql = "DROP VIEW IF EXISTS `vw_lp_user_hierarchy`";
        $result = $DB->execute($sql);     
        
        $sql = "CREATE VIEW `vw_lp_user_hierarchy`
                AS        
                SELECT h.hierarchyid AS hierarchyid,
                    h.parentid AS parentid,
                    h.hierarchyid1 AS hierarchyid1,
                    h.level1 AS level1,
                    h.hierarchyid2 AS hierarchyid2,
                    h.level2 AS level2,
                    h.hierarchyid3 AS hierarchyid3,
                    h.level3 AS level3,
                    h.hierarchyid4 AS hierarchyid4,
                    h.level4 AS level4,
                    h.hierarchyid5 AS hierarchyid5,
                    h.level5 AS level5,
                    u.userid AS userid,
                    u.freetext AS freetext,
                    CONCAT_WS(' / ', h.level1, h.level2, h.level3, h.level4, h.level5) AS hierarchy 
                FROM vw_lp_hierarchy_all h 
                INNER JOIN {$CFG->prefix}lp_user_hierarchy u ON u.hierarchyid = h.hierarchyid";
                
        $result = $DB->execute($sql); 
    }

    return $result;
}