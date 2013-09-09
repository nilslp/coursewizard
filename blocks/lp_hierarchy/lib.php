<?php

// $Id$
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com     //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * lp_hierarchy/lib.php
 *
 * Re-write of the original abstract class to be a singleton prevent duplication
 * of DB work
 *
 * @copyright Learning Pool
 * @author Brian Quinn
 * @author Rob Moore [converted to singleton]
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lp_hierarchy
 */

/**
 * An singleton object that holds methods and attributes common to all hierarchy objects.
 * @singleton
 */
class Hierarchy {

    private static $me;
    private $_depths = false;
    private $_depth = -1;

    private function __construct() {
        
    }

    public function __clone() {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

    public function __wakeup() {
        trigger_error('Unserializing is not allowed.', E_USER_ERROR);
    }

    public static function get_instance() {
        if (!isset(self::$me)) {
            self::$me = new self();
        }
        return self::$me;
    }

    /**
     * Load into the class the hierarchy records to prevent future trips to the
     * DB and increase speed.
     *
     * @global object $DB
     * @return array
     */
    private function load_depths() {
        global $DB;
        if ($this->_depths === false) {
            $this->_depths = $DB->get_records('lp_hierarchy_depth', array(), 'level');
        }
        return $this->_depths;
    }

    /**
     * Get a hierarchy depth from a given ID
     * @param $id Unique identifier of the lp_hierarchy_depth record
     * @return lp_hierarchy_depth record
     */
    function get_depth_by_id($id) {
        foreach ($this->get_depths() as $depth) {
            if ($depth->id == $id) {
                return $depth;
            }
        }
        return false;
    }

    /**
     * Get a hierarchy depth at a given level
     * @param $level The level to be retrieved
     * @return lp_hierarchy_depth record
     */
    function get_depth_by_level($level) {
        foreach ($this->get_depths() as $depth) {
            if ($depth->level == $level) {
                return $depth;
            }
        }
        return false;
    }

    /*     * *
     * Get the org unit from a given ID
     * @param $id Unique identifier of the lp_hierarchy record
     * @return lp_hierarchy record
     */

    function get_org_unit_by_id($id) {
        global $DB;
        return $DB->get_record('lp_hierarchy', array('id' => $id));
    }

    /**
     * Check the DB for the next level for hierarchy depth
     *
     * @return int
     */
    function get_next_depth_level() {
        global $DB;

        $sql = "SELECT @rownum:=@rownum AS 'id', COALESCE(MAX(level), 0) + 1 AS 'depth_level'
    			FROM {lp_hierarchy_depth}, (SELECT @rownum:=0) r";

        $result = $DB->get_records_sql($sql);

        return $result[0]->depth_level;
    }

    /**
     * get_hierarchy_list
     * use the DB view vw_lp_hierarchy_all
     *
     * @return type
     */
    function get_hierarchy_list() {
        global $DB;

        $sql = "SELECT CONCAT_WS(',', hierarchyid1, hierarchyid2, hierarchyid3, hierarchyid4, hierarchyid5) AS 'Key',
                    CONCAT_WS(' / ', level1, level2, level3, level4, level5) AS 'Value'
                FROM vw_lp_hierarchy_all
                ORDER BY Value ASC";

        return $DB->get_records_sql($sql);
    }

    /**
     * Returns the saved hierarchy reporting permissions for a particular user
     * in a format which is usable by the hierarchy treeview renderer
     * @global moodle_database $DB
     * @param type $userid Unique identifier for user
     * @return type Comma-separated list of hierarchies for the UI, with depths
     * separated by underscores
     */
    function get_report_builder_hierarchy_access_list_for_UI($userid) {
        global $DB;
        
        $hierarchyarray = array();
        $csvlist = '';
        
        // Retrieve all the hierarchies visible to the specified user
        $sql = "SELECT hierarchy_display
                FROM
                (
                    SELECT CONCAT_WS('_', hierarchyid1, hierarchyid2, hierarchyid3) AS hierarchy_display
                    FROM vw_lp_hierarchy_all
                    WHERE hierarchyid IN (
                        SELECT hierarchyid
                        FROM {report_builder_hierarchy}
                        WHERE userid = $userid
                    )
                    OR hierarchyid IN (
                        SELECT vha.hierarchyid 
                        FROM vw_lp_hierarchy_all vha
                        JOIN {lp_hierarchy} mh ON vha.hierarchyid = mh.id
                        JOIN {report_builder_hierarchy} mbah ON FIND_IN_SET(mbah.hierarchyid,REPLACE(mh.path,'/',','))
                        WHERE mbah.userid = $userid
                            AND mbah.showchildren = 1
                    )
                    ORDER BY hierarchy_display ASC
                ) t";

        $records = $DB->get_records_sql($sql);
        
        foreach ($records as $record) {
            $hierarchyarray[] = $record->hierarchy_display;
        }

        $csvlist = implode(',', $hierarchyarray);
        
        return $csvlist;
    }

    /**
     * Makes a recursive DB call to retrieve all the child hierarchyIDs for a
     * given parent hierarchyID
     * @global moodle_database $DB
     * @param type $hierarchyid The parent hierarchyID
     * @return type Comma-separated list of hierarchyIDs
     */
    function get_all_children($hierarchyid) {
        global $DB;
        
        // This SQL statement will only work to a maximum depth of 3 levels
        $sql = "SELECT id
                FROM {lp_hierarchy}
                WHERE parentid = $hierarchyid
                UNION
                SELECT id
                FROM {lp_hierarchy}
                WHERE parentid IN (
                    SELECT id
                    FROM {lp_hierarchy}
                    WHERE parentid = $hierarchyid
                )
                UNION
                SELECT id
                FROM {lp_hierarchy}
                WHERE parentid IN (
                    SELECT id
                    FROM {lp_hierarchy}
                    WHERE parentid IN (
                        SELECT id
                        FROM {lp_hierarchy}
                        WHERE parentid = $hierarchyid
                    )
                )";

        $ids = $DB->get_fieldset_sql($sql);
        
        return implode(',', $ids);
    }

    /**
     * Returns a comma-separated list of hierarchy ID values that the specified
     * report_admin user has been granted access to.  Note that when no valid
     * hierarchies are found, -1 is returned
     * @global type $DB
     * @param type $userid Unique identifier of the user record, e.g. $USER->id
     * @return string Comma-separated list of hierarchy IDs, or -1 of none found
     */
    function get_report_builder_hierarchy_access($userid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/blocks/lp_reportbuilder/lib.php");

        $result_no_children = '';
        $result_children = '';

        // Retrieve the hierarchies where no children should be displayed
        $sql_no_children = "SELECT DISTINCT(hierarchyid)
                FROM {report_builder_hierarchy}
                WHERE userid = $userid AND showchildren = 0";

        $result_no_children = $DB->get_records_sql($sql_no_children);
        if (!empty($result_no_children)) {
            $result_no_children = implode(',',array_keys($result_no_children));
        } else {
            $result_no_children = '';
        }

        // Retrieve the hierarchies accessible for this user and where children
        // should be displayed
        $user_access_list_with_children = $DB->get_records('report_builder_hierarchy', array('userid' => $userid, 'showchildren' => 1));

        if ($user_access_list_with_children) {

            $sql_children = "SELECT GROUP_CONCAT(hierarchyid) FROM vw_lp_hierarchy_all WHERE
							hierarchyid in (
								SELECT vha.hierarchyid FROM
								  vw_lp_hierarchy_all vha
								JOIN {lp_hierarchy} mh ON
								  vha.hierarchyid = mh.id
								JOIN {report_builder_hierarchy} mbah ON
								  FIND_IN_SET(mbah.hierarchyid,REPLACE(mh.path,'/',','))
								WHERE
								  mbah.userid = $userid
								AND
								  mbah.showchildren = 1)";
			
			// This DB call should return all hierarchies into one row
			$result_children = $DB->get_field_sql($sql_children);
            if (!empty($result_children)) {
                $result_children = implode(',',array_keys($result_children));
            } else {
                $result_children = '';
            }
        }

        // Return the concatenated hierarchy IDs
        if ($result_children == '' && $result_no_children == '') {
            // No permissions have been defined for this user, we must examine
            // the global settings to see what has been configured for this
            // eventuality
            $defaultreportpermissions = get_config('reportbuilder', 'defaultreportpermissions');

            if ($defaultreportpermissions) {
                $hierarchy_filter = '';

                if ($defaultreportpermissions == REPORT_BUILDER_PERMISSION_CURRENT_HIERARCHY) {
                    $hierarchy_filter = $this->get_current_user_hierarchy($userid)->hierarchyid;
                } else {
                    // Fall-through for the 'all' permission
                    $hierarchy_filter = '';
                }

                return $hierarchy_filter;
            } else {
                // Don't include any hierarchy IDs
                return REPORT_BUILDER_PERMISSION_NONE;
            }
        } else {
            if ($result_no_children != '' && $result_children != '') {
                // Append a trailing comma to form a valid IN clause
                $result_children .= ',';
            }

            return $result_children . $result_no_children;
        }
    }

    /**
     * Makes a hierarchy list.  Use the returned &$list parameter.
     * @param $list A key-value list of organisations (and children)
     * @param $id Required for the recursive call -- leave NULL to return the full org structure
     * @param $showchildren Flag to indicate if an 'and children' option should be displayed
     * @param $shortname Required for the recursive call
     * @param $path Required for the recursive call
     * @param $records Required for the recursive call
     * @param $includeblankoption When True indicates that the first value in the
     * @param $excluded_hierarchy_list Comma-separated list of hierarchy IDs which should be excluded
     * @param $included_hierarchy_list Comma-separated list of hierarchy IDs which should be included
     * list should be a blank option
     * @return array
     */
    function make_hierarchy_list_for_filter(&$list, $id = null, $showchildren = true, $shortname = false, $path = "", $records = null, $includeblankoption = false, $excluded_hierarchy_list = '', $included_hierarchy_list = '') {
        global $DB;

        if (!is_array($list)) {
            $list = array();

            if ($includeblankoption) {
                // This includes an empty item in the list, for example, for
                // use in a drop-down
                $list[] = '';
            }
        }

        if (empty($id)) {
            // Start at the top level
            $id = 0;
        }

        if (empty($records)) {
            // When here it must be first time through function, get the records, and pass to
            // future uses to save DB calls

            if ($excluded_hierarchy_list != '' || $included_hierarchy_list != '') {
                // Restrict the hierarchy to only the ones that the current
                // user has been granted access to
                $sql = "SELECT id, fullname, shortname, parentid, path
                    FROM {lp_hierarchy}
                    WHERE visible = 1 ";

                if ($excluded_hierarchy_list != '') {
                    $sql .= " AND id NOT IN ($excluded_hierarchy_list)";
                }

                if ($included_hierarchy_list != '') {
                    $sql .= " AND id IN ($included_hierarchy_list)";
                }

                $sql .= "ORDER BY path ASC";

                $records = $DB->get_records_sql($sql);
            } else {
                //$records = $DB->get_records('lp_hierarchy', array('visible' => '1 AND path REGEXP ' . $this->sql_lvl_regexp()), 'path', 'id, fullname, shortname, parentid, path');
                $records = $DB->get_records_select('lp_hierarchy', 'visible = 1 AND path REGEXP \'' . $this->sql_lvl_regexp() . '\'', null, 'path', 'id, fullname, shortname, parentid, path');
            }
        }

        if ($id == 0) {
            $children = $this->get_all_root_items(true);
        } else if (!empty($records) && isset($records[$id])) {
            $item = $records[$id];
            $name = ($shortname) ? $item->shortname : $item->fullname;

            if ($path) {
                $path = $path . ' / ' . $name;
            } else {
                $path = $name;
            }

            // Add item
            $list[$item->id] = $path;

            $descendants = array();
            $children = array();
            foreach ($records as $key => $record) {
                // If 'children' should be displayed then display the "..and children" option
                // This does the same as:
                // $descendants = $this->get_item_descendants($id);
                // but without the DB calls
                if ($showchildren === true) {

                    // TODO Test on a 3-level DLE
                    $seperator = strpos($record->path, '/', 1);

                    if ($seperator === false) {
                        $str = $record->path;
                    } else {
                        $str = substr($record->path, 0, $seperator);
                    }

                    if ($str === $item->path) {
                        $descendants[$key] = $record;
                    }
                }

                // This does the same as:
                // $children = $this->get_items_by_parent($id);
                // but without the DB calls
                if ($record->parentid == $id) {
                    $children[$key] = $record;
                }
            }

            if ($showchildren === true && count($descendants) > 1) {
                // add comma separated list of all children too
                $idstr = implode(',', array_keys($descendants));
                $list[$idstr] = $path . " (and all children)";
            }
        }

        // Now deal with children of this item
        if (!empty($children)) {
            foreach ($children as $child) {
                $this->make_hierarchy_list_for_filter($list, $child->id, $showchildren, $shortname, $path, $records);
            }
        }
    }

    /**
     * 
     * @global object $DB
     * @param type $excluded_hierarchy_list
     * @param type $included_hierarchy_list
     * @return type
     * 
     */
    /* function hierarchy_simple_select($excluded_hierarchy_list = '', $included_hierarchy_list = '') {
      global $DB;

      $list = array();
      $depths = $this->get_depths();
      foreach ($depths as $depth) {
      // Restrict the hierarchy to only the ones that the current
      // user has been granted access to
      $sql = "SELECT id, fullname, parentid
      FROM {lp_hierarchy}
      WHERE visible = ? AND depthid = ? ";

      if (!empty($excluded_hierarchy_list)) {
      $sql .= " AND id NOT IN ($excluded_hierarchy_list)";
      }

      if (!empty($included_hierarchy_list)) {
      $sql .= " AND id IN ($included_hierarchy_list)";
      }

      $sql .= "ORDER BY path ASC";

      $list[$depth->level] = $DB->get_records_sql($sql, array(1,$depth->id));
      }

      return $list;
      } */

    /**
     * Returns all org units which don't have a parent
     * @return unknown_type
     */
    function get_all_root_items() {
        global $DB;

        return $DB->get_records('lp_hierarchy', array('parentid' => '0 AND', 'visible' => 1), 'fullname');
    }

    /**
     * Returns all org units that are parents of other items
     * @return unknown_type
     */
    function get_all_parent_items() {
        global $DB;

        $parents = $DB->get_records_sql("SELECT DISTINCT(parentid) AS id FROM {lp_hierarchy}");
        if (empty($parents)) {
            $parents = "0";
        } else {
            $parents = implode(',', array_keys($parents));
        }

        return $DB->get_records_select('lp_hierarchy', " id IN($parents) ", null, 'fullname');
    }

    /**
     * get_hierarchy_field_label_text
     *
     * @param boolean $showfullpath
     * @return string
     */
    function get_hierarchy_field_label_text($showfullpath = false) {
        global $CFG;

        $depths = $this->get_depths();
        $text = '';

        if ($showfullpath) {
            if ($this->is_freetext(true)) {
                $max_level = $this->get_max_depth();

                foreach ($depths as $depth) {
                    if ($depth->level < $max_level) {
                        $text .= $depth->fullname . ' / ';
                    } else {
                        break;
                    }
                }
            } else if ($this->is_freetext()) {
                $max_level = $this->get_real_depth();

                foreach ($depths as $depth) {
                    if ($depth->level < $max_level) {
                        $text .= $depth->fullname . ' / ';
                    } else {
                        break;
                    }
                }
            } else {
                foreach ($depths as $depth) {
                    $text .= $depth->fullname . ' / ';
                }
            }

            $text = rtrim($text, ' / ');
        } else {
            $text = $this->get_depth_by_level($this->get_real_depth())->fullname;
        }

        return $text;
    }

    /**
     * Returns the number of users in a specified org unit
     * @param $hierarchyid Unique identifier of the org unit
     * @return (int) A count of the nubmer of users assigned to the hierarchy
     */
    function get_org_unit_user_count($hierarchyid = 0) {
        global $DB;

        $sql = "SELECT COUNT(id)
                FROM {lp_user_hierarchy}
                WHERE hierarchyid = $hierarchyid ";

        return $result = $DB->get_field_sql($sql);
    }

    /**
     *
     */
    private function &_hierarchy_arrays_build_structure(&$orig, $ilvl, $stdepth, $iclvl = 0) {

        $ret = null;
        if ($iclvl < $ilvl) {

            $orig[0] = array();

            $ret = &$orig[0];

            $tmp = &$this->_hierarchy_arrays_build_structure($orig[0], $ilvl, $stdepth, $iclvl + 1);

            if ($tmp !== null && $iclvl < ($ilvl - 2)) {
                $ret = &$tmp;
            }
        } else {
            $orig[0] = $stdepth;
            if ($ilvl === 0) {
                $ret = &$orig;
            }
        }

        return $ret;
    }

    /**
     * Gets any child org units for a specified parent
     *
     * @param $parentids Array of parent ids
     * @param $excludeids Array of ids to be excluded
     * @param $restrictids Array of ids to restrict recordset to
     * @return (array) Any lp_hierarchy records containing children of the parent
     */
    function get_items_by_parents($parentids = array(), $excludeids = array(), $restrictids = array()) {
        global $DB;

        $exids = '';
        if (count($excludeids) > 0) {
            $exids = ' AND id not in (' . implode(',', $excludeids) . ')';
        }

        $resids = '';
        if (is_array($restrictids) && count($restrictids) > 0) {
            $resids = ' AND id IN (' . implode(',', $restrictids) . ')';
        }

        return $DB->get_records_select('lp_hierarchy', 'visible = 1 AND parentid in (' . implode(',', $parentids) . ')' . $exids . $resids, null, 'fullname', 'id,fullname,parentid');
    }

    /*     * *
     * This function is called on the user's profile to render the 'hierselect' element,
     * i.e. the cascading dropdown lists that will allow selection of the hierarchy
     * 
     * @param array $options Array of options to apply to return array
     * 
     * @return array 
     */


    function get_hierarchy_arrays($options = null) {
        global $CFG;

        $build_options = array('append' => null, 'exclude' => null, 'restrictparents' => null, 'createdummies' => null);
        if ($options !== null) {
            $build_options = array_merge($build_options, $options);
        }

        $depths = $this->get_depths();

        /* $depthcount = count($depths);

          if ($this->is_freetext()) {
          // If free-text departments or sub-departments are in use
          // then decrement the count so that an empty array is not returned
          $depthcount = $depthcount - 1;
          } */

        $depthcount = $this->get_real_depth();

        $result = array();

        $excluded_org_array = array();
        $restrict_org_array = array();
        
        $sitecontext = get_context_instance(CONTEXT_SYSTEM);

        // If the user doesn't have the capability to manage users, the hierarchy must be restricted
        if (!has_capability('moodle/user:update', $sitecontext) && !empty($CFG->block_lp_hierarchy_excluded)) {
            $excluded_org_array = explode(',', $CFG->block_lp_hierarchy_excluded);
        }

        // Check if certain org IDs should be hidden from the signup form
        if (!has_capability('moodle/user:update', $sitecontext) && !empty($CFG->block_lp_hierarchy_restrict_from_signup_list)) {
            if (strpos($CFG->block_lp_hierarchy_restrict_from_signup_list, ',')) {
                $restricted = explode(',', $CFG->block_lp_hierarchy_restrict_from_signup_list);
                $excluded_org_array = array_merge($excluded_org_array, $restricted);    
            }
            else {
                $excluded_org_array[] = $CFG->block_lp_hierarchy_restrict_from_signup_list;
            }
        }

        if ($build_options['exclude'] !== null) {
            $excluded_org_array += $build_options['exclude'];
        }

        if ($build_options['restrictparents'] !== null) {
            $restrict_org_array = $build_options['restrictparents'];
        }

        $current_depth = 0;

        $previous_ids = array(0 => '');

        $next_ids = array(0);


        $current_array = null;

        $dummy = false;
        if ($build_options['createdummies']) {
            $dummy = new stdClass();
            $dummy->id = -1;
            $dummy->parentid = 0;
            $dummy->fullname = ' -- All -- ';
        }

        foreach ($depths as $depth) {
            if ($current_depth === $depthcount) {
                break;
            }
            $result[$current_depth] = array();

            $this->_hierarchy_arrays_build_structure($result[$current_depth], $current_depth, sprintf("-- %s --", $depth->fullname));

            if ($records = $this->get_items_by_parents($next_ids, $excluded_org_array, $restrict_org_array)) {

                $restrict_org_array = array();

                $next_ids = array();

                $current_parent = -1;

                foreach ($records as $rec) {

                    if ($rec->parentid !== $current_parent) {
                        $current_array = &$result[$current_depth];
                        if ($previous_ids[$rec->parentid] !== '') {

                            $tmparr = explode(',', trim($previous_ids[$rec->parentid], ', '));
                            foreach ($tmparr as $nkey) {
                                if (!isset($current_array[$nkey])) {
                                    $current_array[$nkey] = array();
                                }

                                $current_array = &$current_array[$nkey];
                            }
                        }
                        $current_parent = $rec->parentid;
                    }
                    
                    if ($dummy) {
                        $dummy->parentid = $current_parent;
                        if (!isset($current_array[$dummy->id])) {
                            $current_array[$dummy->id] = $dummy->fullname;
                        }
                    }   
                    
                    $current_array[$rec->id] = $rec->fullname;

                    if (!empty($rec->parentid)) {
                        //$previous_ids[$rec->id] = ($previous_ids[$rec->parentid] == ''?'0':$previous_ids[$rec->parentid]) . ",$rec->parentid";
                        $previous_ids[$rec->id] = $previous_ids[$rec->parentid] . ",$rec->id";
                    } else {
                        $previous_ids[$rec->id] = $rec->id;
                    }

                    $next_ids[] = $rec->id;
                }
                
            } else {
                break;
            }

            $current_depth++;
            if ($current_depth === $depthcount) {
                if ($build_options['append'] !== null) {
                    $this->append_child($result[$current_depth - 1], $build_options['append']);
                }
            }
        }

        unset($previous_ids);

        return $result;
    }

    private function append_child(&$arr_in, $to_append) {
        $bappend = true;
        if (is_array($arr_in)) {
            foreach ($arr_in as &$v) {
                if (is_array($v)) {
                    $bappend = false;
                    $this->append_child($v, $to_append);
                }
            }
        }
        if ($bappend) {
            $arr_in += $to_append;
        }
    }
        
    /**
     * get_parents_for_display
     * @return type
     */
    function get_parents_for_display() {

        $items = $hierarchy->get_parent_items();
        $parents = array();

        if ($items) {
            // Cache breadcrumbs
            $breadcrumbs = array();

            foreach ($items as $parent) {
                // Convert 'path' to text
                // Remove the initial forward-slash (/)
                $path = preg_replace('/^\//', '', $parent->path);
                $indexes = explode('/', $path);

                foreach ($indexes as $i) {
                    $breadcrumbs[] = $items[$i]->fullname;
                }

                // Make display text
                $display = implode(' / ', $breadcrumbs);
                $parents[$parent->id] = $display;
                unset($breadcrumbs);
            }
        }

        return $parents;
    }

    /**
     * Get depths for the hierarchy
     * @var array $extra_data optional - specifying extra info to be fetched and returned
     * @return array|false
     * @uses $CFG when extra_data specified
     */
    function get_depths($conditions = array(), $sort = 'level') {
        global $DB;

        if (empty($conditions) && $sort === 'level') {
            return $this->load_depths();
        }

        return $DB->get_records('lp_hierarchy_depth', $conditions, $sort);
    }

    /**
     * Returns a list of users.  This is used in to populate the org unit contact drop-down.
     * @param $conditions
     * @param $sort A SQL ordering clause
     * @return (array) List of user records
     */
    function get_org_unit_contacts($conditions = array(), $sort = 'firstname ASC') {
        global $DB;

        return $DB->get_records('user', $conditions, $sort);
    }

    /**
     * Deletes a specified org unit
     * @param $id Unique identifier
     * @return unknown_type
     */
    function delete_org_unit($id) {
        global $DB;

        return $DB->delete_records('lp_hierarchy', array('id' => $id));
    }

    /**
     * Returns text for 'Turn editing on/off'
     * @param $edit
     * @return HTML button containing 'Turn editing on/off'
     */
    function get_editing_button($edit = -1) {
        global $CFG, $USER, $OUTPUT;

        if ($edit === 1) {
            $USER->{'hierarchyediting'} = 1;
        } else {
            $USER->{'hierarchyediting'} = 0;
        }

        // Work out the appropriate action.
        if ($USER->{'hierarchyediting'} !== 1) {
            $label = 'Turn editing on';
            $edit = 1;
        } else {
            $label = 'Turn editing off';
            $edit = 0;
        }

        // Generate the button HTML.
        $url = $CFG->wwwroot . '/blocks/lp_hierarchy/manage_hierarchy.php?edit=' . $edit;

        return $OUTPUT->single_button($url, $label, 'get');
    }

    /**
     * @return An array containing details on the hierarchy, ordered by sortoder
     * for display purposes
     */
    function get_hierarchy() {
        global $DB;

        $sql = "SELECT h.*, d.level, COALESCE(c.count, 0) `count`, COALESCE(c.deleted_count, 0) `deleted_count`
                FROM
                (
                        SELECT hierarchyid
                        FROM vw_lp_hierarchy_all
                        ORDER BY level1, level2, level3
                ) t
                INNER JOIN {lp_hierarchy} h ON h.id = t.hierarchyid
                INNER JOIN {lp_hierarchy_depth} d ON d.id = h.depthid AND d.level <= {$this->get_real_depth()}
                LEFT JOIN vw_lp_hierarchy_count c ON c.hierarchyid = h.id";

        return $result = $DB->get_records_sql($sql);
    }

    /**
     * Gets a count of the number of active users in a given org unit (and children)
     * @global type $DB
     * @global type $CFG
     * @param type $id Unique identifier for the org unit
     * @return type A count of the the number of active users in that org unit
     */
    function get_active_user_count($id) {
        global $DB;

        $children = $this->get_all_children($id);

        if ($children != '') {
            $children_clause = "OR hierarchyid IN ($children)";
        }

        $sql = "SELECT SUM(COALESCE(count, 0))
                FROM vw_lp_hierarchy_count
                WHERE hierarchyid = $id " . $children_clause;

        return (int) $DB->get_field_sql($sql);
    }

    /**
     * Gets the sort order of the last item in a given path
     * @param $path optional - the path of a given org unit item
     * @return (int) the sort order of the last item on this path
     */
    function get_maximum_hierarchy_sort_order($path = '') {
        global $DB;

        $sql = "SELECT MAX(sortorder) FROM {lp_hierarchy} ";

        if ($path) {
            $sql .= " WHERE path = '{$path}' OR path LIKE '{$path}/%'";
        }

        return (int) $DB->get_field_sql($sql);
    }

    /**
     * get_parent_items
     *
     * @return type
     */
    function get_parent_items() {
        global $DB;

        $conditions = array();

        return $DB->get_records('lp_hierarchy', $conditions, 'sortorder, fullname');
    }

    /**
     * Gets any child org units for a specified parent
     *
     * @param $parentid Unique identifier of the parent org unit
     * @return (array) Any lp_hierarchy records containing children of the parent
     */
    function get_items_by_parent($parentid = 0) {
        global $DB;

        $conditions = array('parentid' => "$parentid AND", 'visible' => 1);

        return $DB->get_records('lp_hierarchy', $conditions);
    }

    /**
     * Get descendants of an item
     * @param int $id
     * @return array|false
     */
    function get_item_descendants($id) {
        global $DB;

        $path = $DB->get_field('lp_hierarchy', 'path', array('id' => $id));

        if ($path) {
            $sql = "SELECT h.id, h.fullname, h.parentid, h.path, h.sortorder, h.depthid, d.level
                    FROM {lp_hierarchy} h
                    INNER JOIN {lp_hierarchy_depth} d ON d.id = h.depthid
                    WHERE h.path LIKE '$path/%'
                    ORDER BY h.sortorder";

            return $DB->get_records_sql($sql);
        } else {
            error('Call to get_item_descendants failed because path could not be found for $id = ' . $id);
        }
    }

    /**
     * Return the deepest hierarchy depth
     * @return int|null
     */
    function get_max_depth() {
        // Get depths
        $depths = $this->get_depths();

        if (!$depths) {
            return null;
        }

        // Get max depth level
        $max_depth = end($depths)->level;

        return $max_depth;
    }

    /**
     * Get the fullname text of the depth level
     * @return (string) depth.fullname
     */
    function get_max_depth_text() {
        $level = $this->get_max_depth();

        $depth = $this->get_depth_by_level($level);

        return $depth->fullname;
    }

    /**
     * Keeps the fieldset label on the user profile in sync with the actual
     * names of the depths
     * @return unknown_type
     */
    function reset_profile_fieldset_label() {
        global $CFG, $DB;

        $depths = $this->get_depths();

        if ($depths) {
            // Build up the label for the fieldset
            $label = '';

            foreach ($depths as $depth) {
                $label .= $depth->fullname . ' / ';
            }

            $label = rtrim($label, ' / ');

            // We have to work backwards to know the ID of the correct category
            // So get the known hierarchy custom field row
            $hierarchy_field_record = $DB->get_record('user_info_field', array('datatype' => 'hierarchy'));

            if ($hierarchy_field_record) {
                $category_record = $DB->get_record('user_info_category', array('id' => $hierarchy_field_record->categoryid));

                if ($category_record) {
                    $category_record->name = $label;

                    $DB->update_record('user_info_category', $category_record);
                }

                // BEGIN -- Keep the labels on the user profile.php page in sync
                // Change the label for display
                $hierarchy_field_record->name = $this->get_hierarchy_field_label_text(true);

                $DB->update_record('user_info_field', $hierarchy_field_record, true);

                if ($CFG->block_lp_hierarchy_allow_freetext) {
                    // Free-text DLEs have been enabled
                    // Retrieve the 'hierarchytext' field
                    $hierarchy_text_field_record = $DB->get_record('user_info_field', array('datatype' => 'hierarchytext'));

                    // Change the label for display
                    $hierarchy_text_field_record->name = $this->get_max_depth_text();

                    $DB->update_record('user_info_field', $hierarchy_text_field_record, true);
                }
                // END
            }
        }
    }

    /**
     * Converts a hierarchy user profile field, (e.g. 1,3) to a textual representation
     * of the hierarchy
     * @param $profile_field_value mdl_user_info_data.data for a given hierarchyid -- can be comma-separated.
     * @return A string containing the hierarchy
     */
    function convert_user_profile_value_to_hierarchy($profile_field_value = '') {
        if ($profile_field_value != '') {
            // If the field contains a comma, this is more than a one-level DLE
            // Check for the position of the comma
            $pos = strpos($profile_field_value, ',');

            if ($pos === false) {
                // There is only one item
                $org_unit = $this->get_org_unit_by_id($profile_field_value);

                if ($org_unit) {
                    return $org_unit->fullname;
                } else {
                    return '';
                }
            } else {
                // There are multiple items
                $org_unit_id_array = explode(',', $profile_field_value);
                $return_value = '';

                foreach ($org_unit_id_array as $org_unit_id) {
                    $org_unit = $this->get_org_unit_by_id($org_unit_id);

                    $return_value = $return_value . $org_unit->fullname . ' / ';
                }

                $return_value = rtrim($return_value, ' / ');

                return $return_value;
            }
        } else {
            return '';
        }
    }

    /**
     * Synchronises a user's hierarchy as stored in their profile with a user's
     * record in the lp_user_hierarchy table
     * @param $userid Unique identifier for the user
     * @return unknown_type
     */
    function sync_hierarchy($userid) {
        global $DB, $CFG;

        // Get the user's hierarchy as held in user_info_data
        $sql = "SELECT data
                FROM {user_info_data}
                WHERE userid = $userid
                AND fieldid IN (SELECT id FROM {user_info_field} WHERE datatype = 'hierarchy')";

        //$stored_value = $DB->get_record_sql($sql);

        if ($stored_value = $DB->get_record_sql($sql)) {
            if ($stored_value->data !== NULL) {
                // Convert the stored value to an array
                $array_data = explode(',', $stored_value->data);

                // Go to the last item in the returned array, as only the lowest level
                // has to be stored
                end($array_data);

                $hierarchyid = current($array_data);

                // Check if a row exists for this user in lp_user_hiearchy
                $count = $DB->count_records('lp_user_hierarchy', array('userid' => $userid));

                $user_hierarchy_record = new object();
                $user_hierarchy_record->userid = $userid;
                $user_hierarchy_record->hierarchyid = $hierarchyid;

                if ($CFG->block_lp_hierarchy_allow_freetext) {
                    // If freetext departments/sub-departements are defined
                    // the value of the given text must be retrieved
                    $sql = "SELECT data
	                        FROM {user_info_data}
	                        WHERE userid = $userid
	                        AND fieldid IN (SELECT id FROM {user_info_field} WHERE datatype = 'hierarchytext')";

                    $freetext_value = $DB->get_record_sql($sql);

                    if ($freetext_value->data !== NULL) {
                        $user_hierarchy_record->freetext = $freetext_value->data;
                    }
                } else {
                    $user_hierarchy_record->freetext = NULL;
                }

                if ($count == 0) {
                    $DB->insert_record('lp_user_hierarchy', $user_hierarchy_record, true);
                } else {
                    // The ID is required for the update to work, so retrieve the previous record
                    $saved_user_hierarchy_record = $DB->get_record('lp_user_hierarchy', array('userid' => $userid));

                    $user_hierarchy_record->id = $saved_user_hierarchy_record->id;

                    $DB->update_record('lp_user_hierarchy', $user_hierarchy_record, true);
                }
            }
        }
    }

    /**
     * Function to detect the current setup of the Hierarchy
     * @return array hierarchy information
     */
    function get_current_user_hierarchy($user_id = 1) {
        global $DB, $USER;

        if ($user_id <= 1) {
            $select = 'userid =' . $user_id;
            $user_hier = $DB->get_record_select('lp_user_hierarchy', $select);
        } else {
            $select = 'userid =' . $USER->id;
            $user_hier = $DB->get_record_select('lp_user_hierarchy', $select);
        }

        return $user_hier;
    }

    /**
     * Function to pull back the raw hierarchy values against a user
     * 
     * @param int ID (optional)User id to check, if omitted the current user id is checked
     * @return object Row from database 
     */
    function get_user_hierarchy_raw_ids($uid = -1) {
        global $DB, $CFG;

        if ($uid === -1) {
            $uid = $USER->id;
        }

        $sql = 'SELECT ud.id, ud.fieldid,ud.data 
				FROM {user_info_data} ud
				JOIN {user_info_field} uf ON
					uf.id = ud.fieldid
				WHERE
				  	uf.shortname = \'hierarchyid\'
				AND
				  ud.userid = ?';

        return $DB->get_record_sql($sql, array($uid), IGNORE_MULTIPLE);
    }

    /**
     * Performs a lookup to retrieve hierarchy information about the user
     * @global moodle_database $DB
     * @global type $USER
     * @param type $userid Unique identifier of the user (if 0 or not set the
     * $USER object is used instead)
     * @return type A row from the vw_lp_user_hierarchy view
     */
    function get_user_hierarchy($userid = 0) {
        global $DB, $USER;

        if ($userid == 0) {
            // Use the current user's ID
            $userid = $USER->id;
        }

        $sql = "SELECT *
                FROM vw_lp_user_hierarchy
                WHERE userid = $userid";

        return $DB->get_record_sql($sql);
    }

    /**
     * Constructs a bare tree data structure representation of the
     * hierarchy for easy use in rendering loops etc.
     * @param type $excluded
     * @return hierarchy_tree
     */
    public function build_hierarchy_tree($excluded = null) {
        $depth = (int) count($this->get_depths());
        $list = $this->get_hierarchy();
        $tree = new hierarchy_tree();

        if (!empty($list)) {
            foreach ($list as $id => $item) {
                if (!empty($excluded) && in_array($id, $excluded)) { // generally looking to exclude learning pool here
                    continue;
                }

                if ((int) $item->level === $depth) { // found leaf
                    $leaf = new hierarchy_tree_node($item->fullname, array());
                    $tree->insert_leaf_node($item->parentid, $id, $leaf);
                } else {                            // found branch
                    $tree->insert_branch_node($item->parentid, $id, $item->fullname);
                }
            }
        }

        return $tree;
    }

    /**
     * Gets the true depth of the DLE depth-freetext
     * @return int
     */
    public function get_real_depth() {
        global $CFG;

        if ($this->_depth === -1) {
            $this->_depth = $CFG->block_lp_hierarchy_depth - (($this->is_freetext()) ? 1 : 0);
            /* if(isset($CFG->block_lp_hierarchy_allow_freetext) && !empty($CFG->block_lp_hierarchy_allow_freetext)){
              $this->_depth-=$CFG->block_lp_hierarchy_allow_freetext;
              }else if(isset($CFG->block_lp_hierarchy_buyways) && !empty($CFG->block_lp_hierarchy_buyways)){
              $this->_depth-=$CFG->block_lp_hierarchy_buyways;
              } */
        }

        return $this->_depth;
    }

    /**
     * Function to calculate the regexp to applied in sql calls to the lp_hierarchy table
     * The resulting regexp will prevent levels greater than the required depth from being returned
     * N.B. Primary use is in buyways
     *
     * @return string
     */
    public function sql_lvl_regexp() {
        $d = $this->get_real_depth();
        return '^(/[0-9]+){1' . (($d > 0) ? ',' . $d : '') . '}$';
    }

    /**
     *
     * @staticvar bool $resp
     * @param type $strict
     * @return boolean
     */
    public function is_freetext($strict = false) {
        global $CFG;
        static $resp = -2;

        if ($resp === -2 && $strict == false) {

            if ((isset($CFG->block_lp_hierarchy_allow_freetext) && !empty($CFG->block_lp_hierarchy_allow_freetext)) || (isset($CFG->block_lp_hierarchy_buyways) && !empty($CFG->block_lp_hierarchy_buyways))) {
                $resp = true;
            } else {
                $resp = false;
            }
        }

        if ($strict == true) {
            if (isset($CFG->block_lp_hierarchy_allow_freetext) && !empty($CFG->block_lp_hierarchy_allow_freetext)) {
                return true;
            }
            return false;
        }

        return $resp;
    }

    /**
     * Returns an array of all the leaf nodes with path as text
     * array( array(hierarchyid=>int, path=>string ), ......)
     * @return array
     */
    public function get_all_leaves_with_path() {
        global $DB;
        //get max depth in order to build sql
        $depth = $this->get_max_depth();
        $concatstr = '';
        $orderstr = '';
        for ($i = 1; $i <= $depth; $i++) {
            $concatstr .= "level" . $i . ", ' / ', ";
            $orderstr .= "level" . $i . ",";
        }
        //chop off the bits we don't want
        $concatstr = trim($concatstr, " /,'");
        $orderstr = trim($orderstr, " ,");
        $sql = "SELECT hierarchyid AS id,
                CONCAT( {$concatstr}) AS path
                FROM vw_lp_hierarchy_all
                WHERE level{$depth} IS NOT NULL
                ORDER BY {$orderstr}";

        return $DB->get_records_sql($sql);
    }

}

/**
 * node class used in hierarchy_tree. children can contain other
 * nodes (branch) or an arbitrary object (leaf)
 */
class hierarchy_tree_node {

    public $name;           // identifier for the node
    public $children;       // an array of branch/leaf nodes
    public $data;           // extra data associated with the node
    public $context;

    public function __construct($name, $data = '') {
        $this->name = $name;
        $this->data = $data;
        $this->children = array();
    }

}

/**
 * Flat tree class - not optimised, but can represent a simple tree of arbitrary depth
 */
class hierarchy_tree {

    public $nodes;             // all nodes of the tree, always has one node 'root'

    public function __construct() {
        $this->nodes = new hierarchy_tree_node('root');
    }

    public function &insert_branch_node($parentid, $nodeid, $name, $data = '') {
        $node = new hierarchy_tree_node($name, $data);
        if ($parentid == 0) {
            $this->nodes->children[$nodeid] = $node;
            return $node;
        }

        $parent = &$this->find_parent($this->nodes->children, $parentid);
        if (!empty($parent)) {
            $parent->children[$nodeid] = $node;
        }

        return $node;
    }

    public function insert_leaf_node($parentid, $nodeid, $leaf) {
        if ($parentid == 0) {
            if (!isset($this->nodes->children[$nodeid])) {
                $this->nodes->children[$nodeid] = $leaf;
            }
            return;
        }

        $parent = &$this->find_parent($this->nodes->children, $parentid);

        if (!empty($parent) && isset($parent->children) && !isset($parent->children[$nodeid])) {
            $parent->children[$nodeid] = $leaf;
        }
    }

    public function &find_parent(&$list, $id) {
        if (isset($list[$id])) {
            return $list[$id];
        }

        foreach ($list as $n) {
            if (isset($n->children)) {
                $parent = &$this->find_parent($n->children, $id);
                if (!empty($parent) && isset($parent->children)) { // only break if parent is a branch node
                    break;
                }
            }
        }

        if (!empty($parent) && isset($parent->children)) {
            return $parent;
        }

        return $nothing;
    }

    public function &locate(&$list, $params) {
        $parent = false;

        foreach ($list as $n) {
            foreach ($params as $prop => $value) {
                if (isset($n->$prop) && ($value == $n->$prop)) {
                    return $n;
                }
            }

            if (isset($n->children)) {
                $parent = &$this->locate($n->children, $params);
                if (!empty($parent)) {
                    break;
                }
            }
        }

        return $parent;
    }

}
