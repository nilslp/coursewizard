<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010, 2011 Totara Learning Solutions LTD
 * 
 * This program is free software; you can redistribute it and/or modify  
 * it under the terms of the GNU General Public License as published by  
 * the Free Software Foundation; either version 2 of the License, or     
 * (at your option) any later version.                                   
 *                                                                       
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @package totara
 * @subpackage reportbuilder 
 */

/**
 * Abstract base class to be extended to create report builder sources
 */
abstract class rb_base_source {

/**
 * Class constructor
 *
 * Call from the constructor of all child classes with:
 *
 *  parent::__construct()
 *
 * to ensure child class has implemented everything necessary to work.
 *
 */
    function __construct() {
        // check that child classes implement required properties
        $properties = array(
            'base',
            'joinlist',
            'columnoptions',
            'filteroptions'
        );
        foreach($properties as $property) {
            if(!property_exists($this, $property)) {
                $a = new object();
                $a->property = $property;
                $a->class = get_class($this);
                throw new Exception(get_string('error:propertyxmustbesetiny', 'block_lp_reportbuilder', $a));
            }
        }

        // set sensible defaults for optional properties
        $defaults = array(
            'paramoptions' => array(),
            'requiredcolumns' => array(),
            'requiredfilters' => array(),
            'contentoptions' => array(),
            'preproc' => null,
            'grouptype' => 'none',
            'groupid' => null,
            'extrawhereclause' => null,
            'defaultdateformat' => '%d %b %Y',
            'defaultdatetimeformat' => '%d %b %Y at %H:%M',
            'defaulttimeformat' => '%H:%M'
        );
        foreach($defaults as $property => $default) {
            if(!property_exists($this, $property)) {
                $this->$property = $default;
            } else if ($this->$property === null) {
                $this->$property = $default;
            }
        }

        // Set the default date/time formats
        $defaultdateformat = get_config('reportbuilder', 'defaultdateformat');
        
        if ($defaultdateformat) {
            $this->defaultdateformat = $defaultdateformat;
        }
        
        $defaultdatetimeformat = get_config('reportbuilder', 'defaultdatetimeformat');
        
        if ($defaultdatetimeformat) {
            $this->defaultdatetimeformat = $defaultdatetimeformat;
        }
        
        $defaulttimeformat = get_config('reportbuilder', 'defaulttimeformat');
        
        if ($defaulttimeformat) {
            $this->defaulttimeformat = $defaulttimeformat;
        }
        
        // basic sanity checking of joinlist
        $this->validate_joinlist();
    }


    /**
     * Check the joinlist for invalid dependencies and duplicate names
     *
     * @return True or throws exception if problem found
     */
    private function validate_joinlist() {
        $joinlist = $this->joinlist;
        $joins_used = array();

        // don't let source define join with same name as an SQL
        // reserved word
        // from http://docs.moodle.org/en/XMLDB_reserved_words
        $reserved_words = explode(', ', 'access, accessible, add, all, alter, analyse, analyze, and, any, array, as, asc, asensitive, asymmetric, audit, authorization, autoincrement, avg, backup, before, begin, between, bigint, binary, blob, both, break, browse, bulk, by, call, cascade, case, cast, change, char, character, check, checkpoint, close, cluster, clustered, coalesce, collate, column, comment, commit, committed, compress, compute, condition, confirm, connect, connection, constraint, contains, containstable, continue, controlrow, convert, count, create, cross, current, current_date, current_role, current_time, current_timestamp, current_user, cursor, database, databases, date, day_hour, day_microsecond, day_minute, day_second, dbcc, deallocate, dec, decimal, declare, default, deferrable, delayed, delete, deny, desc, describe, deterministic, disk, distinct, distinctrow, distributed, div, do, double, drop, dual, dummy, dump, each, else, elseif, enclosed, end, errlvl, errorexit, escape, escaped, except, exclusive, exec, execute, exists, exit, explain, external, false, fetch, file, fillfactor, float, float4, float8, floppy, for, force, foreign, freetext, freetexttable, freeze, from, full, fulltext, function, goto, grant, group, having, high_priority, holdlock, hour_microsecond, hour_minute, hour_second, identified, identity, identity_insert, identitycol, if, ignore, ilike, immediate, in, increment, index, infile, initial, initially, inner, inout, insensitive, insert, int, int1, int2, int3, int4, int8, integer, intersect, interval, into, is, isnull, isolation, iterate, join, key, keys, kill, leading, leave, left, level, like, limit, linear, lineno, lines, load, localtime, localtimestamp, lock, long, longblob, longtext, loop, low_priority, master_heartbeat_period, master_ssl_verify_server_cert, match, max, maxextents, mediumblob, mediumint, mediumtext, middleint, min, minus, minute_microsecond, minute_second, mirrorexit, mlslabel, mod, mode, modifies, modify, national, natural, new,' .
            ' no_write_to_binlog, noaudit, nocheck, nocompress, nonclustered, not, notnull, nowait, null, nullif, number, numeric, of, off, offline, offset, offsets, old, on, once, online, only, open, opendatasource, openquery, openrowset, openxml, optimize, option, optionally, or, order, out, outer, outfile, over, overlaps, overwrite, pctfree, percent, perm, permanent, pipe, pivot, placing, plan, precision, prepare, primary, print, prior, privileges, proc, procedure, processexit, public, purge, raid0, raiserror, range, raw, read, read_only, read_write, reads, readtext, real, reconfigure, references, regexp, release, rename, repeat, repeatable, replace, replication, require, resource, restore, restrict, return, returning, revoke, right, rlike, rollback, row, rowcount, rowguidcol, rowid, rownum, rows, rule, save, schema, schemas, second_microsecond, select, sensitive, separator, serializable, session, session_user, set, setuser, share, show, shutdown, similar, size, smallint, some, soname, spatial, specific, sql, sql_big_result, sql_calc_found_rows, sql_small_result, sqlexception, sqlstate, sqlwarning, ssl, start, starting, statistics, straight_join, successful, sum, symmetric, synonym, sysdate, system_user, table, tape, temp, temporary, terminated, textsize, then, tinyblob, tinyint, tinytext, to, top, trailing, tran, transaction, trigger, true, truncate, tsequal, uid, uncommitted, undo, union, unique, unlock, unsigned, update, updatetext, upgrade, usage, use, user, using, utc_date, utc_time, utc_timestamp, validate, values, varbinary, varchar, varchar2, varcharacter, varying, verbose, view, waitfor, when, whenever, where, while, with, work, write, writetext, x509, xor, year_month, zerofill');

        foreach($joinlist as $item) {
            // check join list for duplicate names
            if(in_array($item->name, $joins_used)) {
                $a = new object();
                $a->join = $item->name;
                $a->source = get_class($this);
                throw new ReportBuilderException(get_string('error:joinxusedmorethanonceiny', 'block_lp_reportbuilder', $a));
            } else {
                $joins_used[] = $item->name;
            }

            if(in_array($item->name, $reserved_words)) {
                $a = new object();
                $a->join = $item->name;
                $a->source = get_class($this);
                throw new ReportBuilderException(get_string('error:joinxisreservediny', 'block_lp_reportbuilder', $a));
            }
        }

        foreach($joinlist as $item) {
            // check that dependencies exist
            if(isset($item->dependencies) &&
                is_array($item->dependencies)) {

                foreach($item->dependencies as $dep) {
                    if($dep == 'base') {
                        continue;
                    }
                    if(!in_array($dep, $joins_used)) {
                        $a = new object();
                        $a->join = $item->name;
                        $a->source = get_class($this);
                        $a->dependency = $dep;
                        throw new ReportBuilderException(get_string('error:joinxhasdependencyyinz', 'block_lp_reportbuilder', $a));
                    }
                }
            } else if (isset($item->dependencies) &&
                $item->dependencies != 'base') {

                if(!in_array($item->dependencies, $joins_used)) {
                    $a = new object();
                    $a->join = $item->name;
                    $a->source = get_class($this);
                    $a->dependency = $item->dependencies;
                    throw new ReportBuilderException(get_string('error:joinxhasdependencyyinz', 'block_lp_reportbuilder', $a));
                }
            }
        }
        return true;
    }
    
    //
    //
    // General purpose source specific methods
    //
    //

    /**
     * Returns a new rb_column object based on a column option from this source
     *
     * If $heading is given use it for the heading property, otherwise use
     * the default heading property from the column option
     *
     * @param string $type The type of the column option to use
     * @param string $value The value of the column option to use
     * @param string $heading Heading for the new column
     * @param boolean $customheading True if the heading has been customised
     * @return object A new rb_column object with details copied from this
     *                rb_column_option
     */
    function new_column_from_option($type, $value, $heading=null, $customheading=true, $hidden=0) {
        $columnoptions = $this->columnoptions;
        $joinlist = $this->joinlist;
        if($coloption =
            reportbuilder::get_single_item($columnoptions, $type, $value)) {

            // make sure joins are defined before adding column
            if(!reportbuilder::check_joins($joinlist, $coloption->joins)) {
                $a = new object();
                $a->type = $coloption->type;
                $a->value = $coloption->value;
                $a->source = get_class($this);
                throw new ReportBuilderException(get_string('error:joinsfortypexandvalueynotfoundinz', 'block_lp_reportbuilder', $a));
            }

            if($heading === null) {
                $heading = ($coloption->defaultheading !== null) ?
                    $coloption->defaultheading : $coloption->name;
            }
            return new rb_column(
                $type,
                $value,
                $heading,
                $coloption->field,
                array(
                    'joins' => $coloption->joins,
                    'displayfunc' => $coloption->displayfunc,
                    'extrafields' => $coloption->extrafields,
                    'required' => false,
                    'capability' => $coloption->capability,
                    'noexport' => $coloption->noexport,
                    'grouping' => $coloption->grouping,
                    'nosort' => $coloption->nosort,
                    'style' => $coloption->style,
                    'hidden' => $hidden,
                    'customheading' => $customheading,
                )
            );
        } else {
            $a = new object();
            $a->type = $type;
            $a->value = $value;
            $a->source = get_class($this);
            throw new ReportBuilderException(get_string('error:columnoptiontypexandvalueynotfoundinz', 'block_lp_reportbuilder', $a));
        }
    }


    /**
     * Returns a new rb_filter object based on a filter option from this source
     *
     * If $advanced is given use it for the advanced property, otherwise use
     * the default advanced property from the filter option
     *
     * @param string $type The type of the filter option to use
     * @param string $value The value of the filter option to use
     * @param string $advanced If the filter should be an advanced option
     * @return object A new rb_filter object with details copied from this
     *                rb_filter_option
     */
    function new_filter_from_option($type, $value, $advanced=null) {
        $filteroptions = $this->filteroptions;
        $columnoptions = $this->columnoptions;
        $joinlist = $this->joinlist;
                
        if(!$filteroption =
            reportbuilder::get_single_item($filteroptions, $type, $value)) {

            $a = new object();
            $a->type = $type;
            $a->value = $value;
            $a->source = get_class($this);
            throw new ReportBuilderException(get_string('error:filteroptiontypexandvalueynotfoundinz', 'block_lp_reportbuilder', $a));
        }
        if(!$columnoption =
            reportbuilder::get_single_item($columnoptions, $type, $value)) {
            
            $a = new object();
            $a->type = $type;
            $a->value = $value;
            $a->source = get_class($this);
            throw new ReportBuilderException(get_string('error:columnoptiontypexandvalueynotfoundinz', 'block_lp_reportbuilder', $a));
        }

        // make sure joins are defined before adding column
        if(!reportbuilder::check_joins($joinlist, $columnoption->joins)) {
            $a = new object();
            $a->type = $columnoption->type;
            $a->value = $columnoption->value;
            $a->source = get_class($this);
            throw new ReportBuilderException(get_string('error:joinsforfiltertypexandvalueynotfoundinz', 'block_lp_reportbuilder', $a));

        }

        if($advanced === null) {
            $advanced = $filteroption->defaultadvanced;
        }
        return new rb_filter(
            $type,
            $value,
            $advanced,
            $filteroption->label,
            $filteroption->filtertype,
            $columnoption->field,
            array(
                'selectfunc' => $filteroption->selectfunc,
                'selectchoices' => $filteroption->selectchoices,
                'selectoptions' => $filteroption->selectoptions,
                'grouping' => $columnoption->grouping,
                'src' => $this,
            )
        );

    }

    //
    //
    // Generic column display methods
    //
    //


    /**
     * Reformat a timestamp into a date, showing nothing if invalid or null
     *
     * @param integer $date Unix timestamp
     * @param object $row Object containing all other fields for this row
     *
     * @return string Date in a nice format
     */
    function rb_display_nice_date($date, $row) {       
        if (empty($date)) {
            return get_string('nullcolumnvalue', 'block_lp_reportbuilder');
        }
        
        if($date && intval($date) > 0) {
            return userdate($date, $this->defaultdateformat);
        }
        
        return get_string('nullcolumnvalue', 'block_lp_reportbuilder');
    }
    
    function rb_display_completion_status_tf($value, $row) {
        $return_string = '';
        
        switch ($value) {
            case 'completed':
            case 'passed':
                $return_string = 'T';
                break;
            
            default:
                $return_string = 'F';
        }
        
        return $return_string;
    }
    
    /**
     * Reformat a course status as a text description, i.e. not attempted, incomplete, complete
     * @param type $value
     * @param type $row
     * @return string
     */
    function rb_display_course_status_as_text($value, $row) {
        $return_string = '';
                
        switch ($value) {
            case null:
                $return_string = get_string('nullcolumnvalue', 'block_lp_reportbuilder');
                break;
            case 0:
                $return_string = get_string('notattempted', 'block_lp_reportbuilder');
                break;
            case 1:
                $return_string = get_string('incomplete', 'block_lp_reportbuilder');
                break;
            case 2:
                $return_string = get_string('complete', 'block_lp_reportbuilder');
                break;
            default:
                $return_string = get_string('nullcolumnvalue', 'block_lp_reportbuilder');
                break;
        }
  
        return $return_string;
    }

    /**
     * Reformat a course status as a text description, i.e. not attempted, incomplete, complete
     * @param type $value
     * @param type $row
     * @return string
     */
    function rb_display_link_course_status_as_text($value, $row) {
        global $CFG;
        
        $courseid = $row->course_id;
        
        if (!isset($row->user_id)) {
            // Not ideal!
            $userid = $row->course_user_userid;
        }else {
            $userid = $row->user_id;
        }
        
        $return_string = '';
        
        switch ($value) {
            case 0:
                $return_string = get_string('notattempted', 'block_lp_reportbuilder');
                break;
            case 1:
                $return_string = get_string('incomplete', 'block_lp_reportbuilder');
                break;
            
            case 2:
                $return_string = get_string('complete', 'block_lp_reportbuilder');
                break;
            
            default:
                $return_string = get_string('nullcolumnvalue', 'block_lp_reportbuilder');
        }
  
        return "<a href=\"{$CFG->wwwroot}/blocks/lp_reportbuilder/reports/modules_summary.php?autosubmit=1&courseid={$courseid}&userid={$userid}\">$return_string</a>";
    }
    
    /**
     * Reformat a timestamp into a time, showing nothing if invalid or null
     *
     * @param integer $date Unix timestamp
     * @param object $row Object containing all other fields for this row
     *
     * @return string Time in a nice format
     */
    function rb_display_nice_time($date, $row) {       
        if (empty($date)) {
            return get_string('nullcolumnvalue', 'block_lp_reportbuilder');
        }
        
        if($date && intval($date) > 0) {
            return userdate($date, $this->defaulttimeformat);
        }
        
        return get_string('nullcolumnvalue', 'block_lp_reportbuilder');
    }

    /**
     * Reformat a time interval (in seconds) to the time in minutes
     * @param type $item
     * @param type $row
     * @return type
     */
    function rb_display_timestamp_in_mins($item, $row) {
        if ($item === null) {
            return get_string('nullcolumnvalue', 'block_lp_reportbuilder');  
        }
        
        return ceil(floatval($item / 60));
    }
    
    function rb_display_assessment_type($item, $row) {
        if ($item === null) {
            return get_string('nullcolumnvalue', 'block_lp_reportbuilder');  
        }

        return get_string('assementtype' . $item, 'block_lp_reportbuilder');
    }
    
    /**
     * Reformat a timestamp into a date and time, showing nothing if invalid or null
     *
     * @param integer $date Unix timestamp
     * @param object $row Object containing all other fields for this row
     *
     * @return string Date and time in a nice format
     */
    function rb_display_nice_datetime($date, $row) {         
        if (empty($date)) {
            return get_string('nullcolumnvalue', 'block_lp_reportbuilder');
        }
        
        if($date && intval($date) > 0) {
            return userdate($date, $this->defaultdatetimeformat);
        } 
        
        return get_string('nullcolumnvalue', 'block_lp_reportbuilder');
    }


    /**
     * Convert 1 and 0 to 'Yes' and 'No'
     *
     * @param integer $value input value
     *
     * @return string yes or no, or null for values other than 1 or 0
     */
    function rb_display_yes_or_no($value, $row) {
        if((int)$value == 1) {
            return get_string('yes');
        } else if ((int)$value == 0) {
            return get_string('no');
        } else {
            return '';
        }
    }


    /**
     * Convert first letters of each word to uppercase
     *
     * @param string $item A string to convert
     * @param object $row Object containing all other fields for this row
     *
     * @return string The string with words capitialized
     */
    function rb_display_ucfirst($item, $row) {
        return ucfirst($item);
    }

    // convert floats to 2 decimal places
    function rb_display_round2($item, $row) {
        return $item === null ? null : sprintf('%.2f', $item);
    }

    // converts number to percentage with 1 decimal place
    function rb_display_percent($item, $row) {
        return $item === null ? null : sprintf('%.1f%%', $item);
    }

    // link user's name to profile page
    // requires the user_id extra field
    // in column definition
    function rb_display_link_user($user, $row) {
        global $CFG;
        $userid = $row->user_id;
        return "<a href=\"{$CFG->wwwroot}/user/view.php?id={$userid}\">{$user}</a>";
    }

    function rb_display_link_user_icon($user, $row) {
        global $CFG, $OUTPUT;
        $userid = $row->user_id;

        $picuser = new stdClass();
        $picuser->id = $userid;
        $picuser->picture = $row->userpic_picture;
        $picuser->imagealt = $row->userpic_imagealt;
        $picuser->firstname = $row->userpic_firstname;
        $picuser->lastname = $row->userpic_lastname;
        $picuser->email = '';  // This property is now required

        return $OUTPUT->user_picture($picuser);
     }

    /**
     * A rb_column_options->displayfunc helper function for showing a user's
     * profile picture
     * @param integer $itemid ID of the user
     * @param object $row The rest of the data for the row
     * @return string
     */
    function rb_display_user_picture($itemid, $row) {
        global $OUTPUT;
        
    	$picuser = new stdClass();
        $picuser->id = $itemid;
        $picuser->picture = $row->userpic_picture;
        $picuser->imagealt = $row->userpic_imagealt;
        $picuser->firstname = $row->userpic_firstname;
        $picuser->lastname = $row->userpic_lastname;

        return $OUTPUT->user_picture($picuser);
    }


    // convert a course name into a link to that course
    function rb_display_link_course($course, $row) {
        global $CFG;
        $courseid = $row->course_id;
        $cssclass = (isset($row->course_visible) && $row->course_visible == 0) ? ' class="dimmed"' : '';
        return "<a $cssclass href=\"{$CFG->wwwroot}/course/view.php?id={$courseid}\">{$course}</a>";
    }

    function rb_display_link_course_to_module_report($course, $row) {
        global $CFG;
        $courseid = $row->course_id;
        $cssclass = (isset($row->course_visible) && $row->course_visible == 0) ? ' class="dimmed"' : '';
        return "<a $cssclass href=\"{$CFG->wwwroot}/blocks/lp_reportbuilder/reports/modules_summary.php?autosubmit=1&courseid={$courseid}\">{$course}</a>";
    }

    // convert a course name into a link to that course and shows
    // the course icon next to it
    function rb_display_link_course_icon($course, $row) {
        global $CFG;
        $courseid = $row->course_id;
        $courseicon = $row->course_icon;
        $cssclass = (isset($row->course_visible) && $row->course_visible == 0) ? ' class="dimmed"' : '';
        return "<a $cssclass href=\"{$CFG->wwwroot}/course/view.php?id={$courseid}\"><img class=\"course_icon\" src=\"{$CFG->wwwroot}/local/icon.php?icon=".urlencode($courseicon)."&amp;id=$courseid&amp;size=small&amp;type=course\" alt=\"$course\" />{$course}</a>";
    }

    // display an icon based on the course icon field
    function rb_display_course_icon($icon, $row) {
        global $CFG;
        $courseid = $row->course_id;
        $coursename = $row->course_name;
        return "<img class=\"course_icon\" src=\"{$CFG->wwwroot}/local/icon.php?icon=".urlencode($icon)."&amp;id=$courseid&amp;size=small&amp;type=course\" alt=\"$coursename\" />";
    }

    // display an icon for the course type
    function rb_display_course_type_icon($type) {
        global $CFG;

        switch ($type) {
        case null:
            return null;
            break;
        case 0:
            $image = 'elearning';
            break;
        case 1:
            $image = 'blended';
            break;
        case 2:
            $image = 'facetoface';
            break;
        }
        $alt = get_string($image, 'rb_source_dp_course');
        $icon = "<img title=\"{$alt}\" src=\"{$CFG->pixpath}/msgicons/{$image}" . '-regular.png' . "\"></img>";

        return $icon;
    }

    // convert a course category name into a link to that category's page
    function rb_display_link_course_category($category, $row) {
        global $CFG;
        $catid = $row->cat_id;
        if($catid == 0 || !$catid) {
            return '';
        }
        $cssclass = (isset($row->cat_visible) && $row->cat_visible == 0) ? ' class="dimmed"' : '';
        return "<a $cssclass href=\"{$CFG->wwwroot}/course/category.php?id={$catid}\">{$category}</a>";
    }

    // convert a course name into a link to that course and shows
    // the course icon next to it
    function rb_display_link_course_category_icon($category, $row) {
        global $CFG;
        $catid = $row->cat_id;
        $caticon = $row->cat_icon;
        if($catid == 0 || !$catid) {
            return '';
        }
        $cssclass = (isset($row->cat_visible) && $row->cat_visible == 0) ? ' class="dimmed"' : '';
        return "<a $cssclass href=\"{$CFG->wwwroot}/course/category.php?id={$catid}\"><img class=\"course_icon\" src=\"{$CFG->wwwroot}/local/icon.php?icon=".urlencode($caticon)."&amp;id=$catid&amp;size=small&amp;type=coursecategory\" alt=\"$category\" />{$category}</a>";
    }


    /**
     * Generate the plan title with a link to the plan
     * @global object $CFG
     * @param string $planname
     * @param object $row
     * @return string
     */
    public function rb_display_planlink($planname, $row){
        global $CFG;

        // no text
        if (strlen($planname) == 0) {
            return '';
        }

        // invalid id - show without a link
        if (empty($row->plan_id)) {
            return $planname;
        }

        return "<a href=\"{$CFG->wwwroot}/local/plan/view.php?id={$row->plan_id}\">$planname</a>";
    }


    /**
     * Display the plan's status (for use as a column displayfunc)
     *
     * @global object $CFG
     * @param int $status
     * @param object $row
     * @return string
     */
    public function rb_display_plan_status($status, $row){
        global $CFG;
        require_once($CFG->dirroot.'/local/plan/lib.php');

        switch ($status){
            case DP_PLAN_STATUS_UNAPPROVED:
                return get_string('unapproved', 'local_plan');
                break;
            case DP_PLAN_STATUS_APPROVED:
                return get_string('approved', 'local_plan');
                break;
            case DP_PLAN_STATUS_COMPLETE:
                return get_string('complete', 'local_plan');
                break;
        }
    }


    /**
     * Column displayfunc to convert a plan item's status to a
     * human-readable string
     *
     * @param int $status
     * @return string
     */
    public function rb_display_plan_item_status($status){
        global $CFG;
        require_once($CFG->dirroot . '/local/plan/lib.php');

        switch($status) {
        case DP_APPROVAL_DECLINED:
            return get_string('declined','local_plan');
        case DP_APPROVAL_UNAPPROVED:
            return get_string('unapproved', 'local_plan');
        case DP_APPROVAL_REQUESTED:
            return get_string('pendingapproval', 'local_plan');
        case DP_APPROVAL_APPROVED:
            return get_string('approved', 'local_plan');
        default:
            return '';
        }
    }


    function rb_display_yes_no($item, $row) {
        if ($item === null) {
            return '';
        } else if ($item) {
            return get_string('yes');
        } else {
            return get_string('no');
        }
    }

    // convert an integer number of minutes into a
    // formatted duration (e.g. 90 mins => 1h 30m)
    function rb_display_hours_minutes($mins, $row) {
        if($mins === null) {
            return '';
        } else {
            $minutes = abs((int) $mins);
            $hours = floor($minutes / 60);
            $decimalMinutes = $minutes - floor($minutes/60) * 60;
            return sprintf("%dh %02.0fm", $hours, $decimalMinutes);
        }
    }

    // convert a 2 digit country code into the country name
    function rb_display_country_code($code, $row) {
        $countries = get_string_manager()->get_list_of_countries();
        if (isset($countries[$code])) {
            return $countries[$code];
        }
        return $code;
    }
    
    function rb_display_maybe_null($item, $row) {
        if ($item === null) {
            return get_string('nullcolumnvalue', 'block_lp_reportbuilder');            
        }
        return $item;
    }
    
    function rb_display_time_in_mins($item, $row) {
        if ($item === null) {
            return get_string('nullcolumnvalue', 'block_lp_reportbuilder');  
        }
        //e.g.00:03:13.75
        
        $component = split(':', $item);
               
        $h = intval($component[0]);
        $m = intval($component[1]);
        $s = ceil(floatval($component[2]));
        
        return ($h * 60) + $m + (($s > 30 )? 1: 0);
    }

    //
    //
    // Generic select filter methods
    //
    //

    function rb_filter_yesno_list() {
        $yn = array();
        $yn['Yes'] = get_string('yes');
        $yn['No'] = get_string('no');
        return $yn;
    }

    function rb_filter_yesno_list_number() {
        $yn = array();
        $yn[0] = get_string('no');
        $yn[1] = get_string('yes');
        return $yn;
    }
    
    function rb_filter_modules_list() {
        global $CFG, $DB;
        $out = array();
        if($mods = $DB->get_records('modules', 'visible', 1, 'id', 'id,name')) {
            foreach($mods as $mod) {
                $modname = get_string('modulename', $mod->name);
                $icon = '/mod/' . $mod->name . '/icon.gif';
                if(file_exists($CFG->dirroot . $icon)) {
                    $out[$mod->name] = '<img src="'. $CFG->wwwroot .
                        '/mod/' . $mod->name . '/icon.gif" alt="'.
                        $modname . '" />&nbsp;' .
                        $modname;
                } else {
                    $out[$mod->name] = $modname;
                }
            }
        }
        return $out;
    }

    function rb_filter_organisations_list($contentmode, $contentoptions, $reportid) {
        global $CFG, $USER, $DB;
        require_once($CFG->dirroot.'/hierarchy/lib.php');
        require_once($CFG->dirroot.'/hierarchy/type/organisation/lib.php');

        // show all options if no content restrictions set
        if($contentmode == REPORT_BUILDER_CONTENT_MODE_NONE) {
            $hierarchy = new organisation();
            $hierarchy->make_hierarchy_list($orgs, null, true, true);
            return $orgs;
        }

        $baseorg = null; // default to top of tree

        $localset = false;
        $nonlocal = false;
        // are enabled content restrictions local or not?
        if(isset($contentoptions) && is_array($contentoptions)) {
            foreach($contentoptions as $option) {
                $name = $option->classname;
                $classname = 'rb_' . $name . '_content';
                if(class_exists($classname)) {
                    if($name == 'completed_org' || $name == 'current_org') {
                        if(reportbuilder::get_setting($reportid, $classname,
                            'enable')) {
                            $localset = true;
                        }
                    } else {
                        if(reportbuilder::get_setting($reportid, $classname,
                            'enable')) {
                        $nonlocal = true;
                        }
                    }
                }
            }
        }

        if($contentmode == REPORT_BUILDER_CONTENT_MODE_ANY) {
            if($localset && !$nonlocal) {
                // only restrict the org list if all content restrictions are local ones
                if($orgid = $DB->get_field('pos_assignment','organisationid','userid',$USER->id)) {
                    $baseorg = $orgid;
                }
            }
        } else if ($contentmode == REPORT_BUILDER_CONTENT_MODE_ALL) {
            if($localset) {
                // restrict the org list if any content restrictions are local ones
                if($orgid = $DB->get_field('pos_assignment','organisationid','userid',$USER->id)) {
                    $baseorg = $orgid;
                }
            }
        }

        $hierarchy = new organisation();
        $hierarchy->make_hierarchy_list($orgs, $baseorg, true, true);

        return $orgs;

    }

    function rb_filter_positions_list() {
        global $CFG;
        require_once($CFG->dirroot.'/hierarchy/lib.php');
        require_once($CFG->dirroot.'/hierarchy/type/position/lib.php');

        $hierarchy = new position();
        $hierarchy->make_hierarchy_list($positions, null, true, false);

        return $positions;

    }

    function rb_filter_course_categories_list() {
        global $CFG;
        require_once($CFG->dirroot.'/course/lib.php');
        make_categories_list($cats, $unused);

        return $cats;
    }

    function rb_filter_course_list() {
        global $DB;
        $resp = $DB->get_records_sql('select distinct co.fullname from {choice} ch join {course} co on ch.course = co.id order by fullname');
        $results = array();
        foreach($resp as $r){
            $results[$r->fullname] = $r->fullname;
        }
        return $results;
    }
    
    function rb_filter_choice_name_list() {
        global $DB;
        $resp = $DB->get_records_sql('select distinct name from {choice_answers} a join {choice} c on a.choiceid = c.id order by name');
        $results = array();
        foreach($resp as $r){
            $results[$r->name] = $r->name;
        }
        return $results;
    }
    
    function rb_filter_choice_answer_list() {
        global $DB;
        $resp = $DB->get_records_sql('select distinct text from {choice_answers} a join {choice_options} o on a.optionid = o.id order by text');
        $results = array();
        foreach($resp as $r){
            $results[$r->text] = $r->text;
        }
        return $results;
    }
    
    function rb_filter_hierarchy_list() {
    	global $CFG, $USER;
    	require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');
    	
        $excluded_hierarchy_list = '';
        $included_hierarchy_list = $this->get_accessible_hierarchy_list();
         
        if (isset($CFG->block_lp_hierarchy_excluded)) {
            $excluded_hierarchy_list = $CFG->block_lp_hierarchy_excluded;
        }
        
    	$hierarchy = Hierarchy::get_instance();
        
    	//$hierarchy->make_hierarchy_list_for_filter($orgs, null, true, false);
        // Work in progress!
        $hierarchy->make_hierarchy_list_for_filter($orgs, null, true, false, '', null, false, $excluded_hierarchy_list, $included_hierarchy_list);
        return $orgs;
    }
    
    function rb_filter_simple_hierarchy_select() {
    	global $CFG, $USER;
    	require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');
    	
        $excluded_hierarchy_list = '';
        $included_hierarchy_list = $this->get_accessible_hierarchy_list();
         
        if (isset($CFG->block_lp_hierarchy_excluded)) {
            $excluded_hierarchy_list = $CFG->block_lp_hierarchy_excluded;
        }
        
    	$hierarchy = Hierarchy::get_instance();        
        $orgs = $hierarchy->hierarchy_simple_select($excluded_hierarchy_list, $included_hierarchy_list);
        return $orgs;
    }
    
    function rb_filter_face_to_face_course_list(){
        global $DB;
        $resp = $DB->get_records_sql('select distinct fullname from {facetoface} f join {course} c on f.course = c.id order by fullname');
        $results = array();
        foreach($resp as $r){
            $results[$r->fullname] = $r->fullname;
        }
        return $results;
    }
    function rb_filter_face_to_face_with_sessions_course_list(){
        global $DB;
        $resp = $DB->get_records_sql('select distinct fullname from {facetoface} f join {course} c on f.course = c.id join {facetoface_sessions} s on f.id = s.facetoface where s.active = 1 order by fullname');
        $results = array();
        foreach($resp as $r){
            $results[$r->fullname] = $r->fullname;
        }
        return $results;
    }
    function rb_filter_face_to_face_name_list(){
        global $DB;
        $resp = $DB->get_records_sql('select distinct f.name from {facetoface} f  order by name');
        $results = array();
        foreach($resp as $r){
            $results[$r->name] = $r->name;
        }
        return $results;
    }
    function rb_filter_face_to_face_with_sessions_name_list(){
        global $DB;
        $resp = $DB->get_records_sql('select distinct f.name from {facetoface} f join {facetoface_sessions} s on f.id = s.facetoface where s.active = 1 order by name');
        $results = array();
        foreach($resp as $r){
            $results[$r->name] = $r->name;
        }
        return $results;
    }
    
    function rb_filter_completionstatus_list() {
        global $CFG;
        
        $list = array(
            0 => get_string('notattempted', 'block_lp_reportbuilder'),
            1 => get_string('incomplete', 'block_lp_reportbuilder'),
            2 => get_string('complete', 'block_lp_reportbuilder')
        );        
        
        return $list;
    }
    //
    //
    // Generic grouping methods for aggregation
    //
    //

    function rb_group_count($field) {
        return "COUNT($field)";
    }

    function rb_group_unique_count($field) {
        return "COUNT(DISTINCT $field)";
    }

    function rb_group_sum($field) {
        return "SUM($field)";
    }

    function rb_group_average($field) {
        return "AVG($field)";
    }

    function rb_group_max($field) {
        return "MAX($field)";
    }

    function rb_group_min($field) {
        return "MIN($field)";
    }

    function rb_group_stddev($field) {
        return "STDDEV($field)";
    }

    // can be used to 'fake' a percentage, if matching values return 1 and
    // all other values return 0 or null
    function rb_group_percent($field) {
    	global $DB;
    	return $DB->sql_cast_char2int("AVG($field)*100.0");
    }

    // return list as single field, separated by commas
    function rb_group_comma_list($field) {
        return sql_group_concat($field);
    }

    // return unique list items as single field, separated by commas
    function rb_group_comma_list_unique($field) {
        return sql_group_concat($field, ', ', true);
    }

    // return list as single field, one per line
    function rb_group_list($field) {
        return sql_group_concat($field, '<br />');
    }

    // return unique list items as single field, one per line
    function rb_group_list_unique($field) {
        return sql_group_concat($field, '<br />', true);
    }

    // return list as single field, separated by a line with - on (in HTML)
    function rb_group_list_dash($field) {
        return sql_group_concat($field, '<br />-<br />');
    }

    //
    //
    // Methods for adding commonly used data to source definitions
    //
    //

    //
    // Wrapper functions to add columns/fields/joins in one go
    //
    //


    /**
     * Adds the user table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'user id' field
     * @param string $field Name of user id field to join on
     * @return boolean True
     */
    protected function add_user_table_to_joinlist(&$joinlist, $join, $field) {
        global $CFG;

        // join uses 'auser' as name because 'user' is a reserved keyword
        $joinlist[] = new rb_join(
            'auser',
            'LEFT',
            $CFG->prefix . 'user',
            "auser.id = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );
    }

    protected function add_course_user_fields_to_columns(&$columnoptions,
        $join='course_user') {       

         $columnoptions[] = new rb_column_option(
            'course_user',
            'coursecompletiondate',
            get_string('coursecompletiondate', 'block_lp_reportbuilder'),
            "$join.timecompleted",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_date',
                'defaultheading' => get_string('coursecompletiondate', 'block_lp_reportbuilder')
            )
        );
         
        $columnoptions[] = new rb_column_option(
            'course_user',
            'completionstatus',
            get_string('completionstatus', 'block_lp_reportbuilder'),
           "$join.completion_status",
            array(
                'joins' => $join,
                'displayfunc' => 'course_status_as_text',
                'defaultheading' => get_string('completionstatus', 'block_lp_reportbuilder') 
                )
        );

        $columnoptions[] = new rb_column_option(
            'course_user',
            'completionstatuslink',
            get_string('completionstatuslink', 'block_lp_reportbuilder'),
            "$join.completion_status",
            array(
                'joins' => $join,
                'displayfunc' => 'link_course_status_as_text',
                'defaultheading' => get_string('completionstatus', 'block_lp_reportbuilder'), 
                )
        );

        $columnoptions[] = new rb_column_option(
            'course_user',
            'coursefirstaccessed',
            get_string('coursefirstaccessed', 'block_lp_reportbuilder'),
            "$join.timestarted",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_date',
                'defaultheading' => get_string('coursefirstaccessed', 'block_lp_reportbuilder')
            )
        );
        
        $columnoptions[] = new rb_column_option(
            'course_user',
            'courselastaccessed',
            get_string('courselastaccessed', 'block_lp_reportbuilder'),
            "$join.timeaccess",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_date',
                'defaultheading' => get_string('courselastaccessed', 'block_lp_reportbuilder')
            )
        );
                
        $columnoptions[] = new rb_column_option(
            'course_user',
            'coursecompleted',
            get_string('coursecompletionstatusastruefalse', 'block_lp_reportbuilder'),
            "CASE $join.completion_status 
		WHEN 2 THEN '" . get_string('completionstatustrue', 'block_lp_reportbuilder') . "' 
		ELSE '" . get_string('completionstatusfalse', 'block_lp_reportbuilder') . "' 
            END",
            array(
                'joins' => $join,
                'defaultheading' => get_string('coursecompletionstatusastruefalse', 'block_lp_reportbuilder'),
            )
        );
     }
     
     
    protected function add_course_user_fields_to_filters(&$filteroptions) {
        $filteroptions[] = new rb_filter_option(
            'course_user',
            'completionstatus',
            get_string('completionstatus', 'block_lp_reportbuilder'),
            'select',
            array(
                'selectfunc' => 'completionstatus_list',
                'selectoptions' => rb_filter_option::select_width_limiter(),
            )
        );
        return true;
    }
     
     /**
     * Adds some common user field to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $join Name of the join that provides the 'user' table
     *
     * @return True
     */
    protected function add_user_fields_to_columns(&$columnoptions,
        $join='auser') {
        global $DB;
        
        $columnoptions[] = new rb_column_option(
            'user',
            'fullname',
            get_string('userfullname', 'block_lp_reportbuilder'),
            $DB->sql_fullname("$join.firstname", "$join.lastname"),
            array('joins' => $join)
        );
        $columnoptions[] = new rb_column_option(
            'user',
            'namelink',
            get_string('usernamelink', 'block_lp_reportbuilder'),
            $DB->sql_fullname("$join.firstname", "$join.lastname"),
            array(
                'joins' => $join,
                'displayfunc' => 'link_user',
                'defaultheading' => get_string('userfullname', 'block_lp_reportbuilder'),
                'extrafields' => array('user_id' => "$join.id"),
            )
        );
        $columnoptions[] = new rb_column_option(
            'user',
            'namelinkicon',
            get_string('usernamelinkicon', 'block_lp_reportbuilder'),
            $DB->sql_fullname("$join.firstname", "$join.lastname"),
            array(
                'joins' => $join,
                'displayfunc' => 'link_user_icon',
                'defaultheading' => get_string('userfullname', 'block_lp_reportbuilder'),
                'extrafields' => array(
                    'user_id' => "$join.id",
                    'userpic_picture' => "$join.picture",
                    'userpic_firstname' => "$join.firstname",
                    'userpic_lastname' => "$join.lastname",
                    'userpic_imagealt' => "$join.imagealt"
                ),
                'style' => array('white-space' => 'nowrap'),
            )
        );
        $columnoptions[] = new rb_column_option(
            'user',
            'lastlogin',
            get_string('userlastlogin', 'block_lp_reportbuilder'),
            "$join.lastlogin",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_datetime',
            )
        );
        
        $columnoptions[] = new rb_column_option(
            'user',
            'firstaccess',
            get_string('userfirstaccess', 'block_lp_reportbuilder'),
            "$join.firstaccess",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_datetime',
            )
        );
        
        $columnoptions[] = new rb_column_option(
            'user',
            'lastaccess',
            get_string('userlastaccess', 'block_lp_reportbuilder'),
            "$join.lastaccess",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_datetime',
            )
        );

        $columnoptions[] = new rb_column_option(
            'user',
            'currentlogin',
            get_string('usercurrentlogin', 'block_lp_reportbuilder'),
            "$join.currentlogin",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_datetime',
            )
        );

        $columnoptions[] = new rb_column_option(
            'user',
            'timecreated',
            get_string('usertimecreated', 'block_lp_reportbuilder'),
            "$join.timecreated",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_datetime',
            )
        );
        
        $columnoptions[] = new rb_column_option(
            'user',
            'timemodified',
            get_string('usertimemodified', 'block_lp_reportbuilder'),
            "$join.timemodified",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_datetime',
            )
        );

        // auto-generate columns for user fields
        $fields = array(
            'firstname' => get_string('userfirstname', 'block_lp_reportbuilder'),
            'lastname' => get_string('userlastname', 'block_lp_reportbuilder'),
            'username' => get_string('username', 'block_lp_reportbuilder'),
            'idnumber' => get_string('useridnumber', 'block_lp_reportbuilder'),
            'city' => get_string('usercity', 'block_lp_reportbuilder'),
            'email' => get_string('useremail', 'block_lp_reportbuilder'),
        );
        
        foreach ($fields as $field => $name) {
            $columnoptions[] = new rb_column_option(
                'user',
                $field,
                $name,
                "$join.$field",
                array('joins' => $join)
            );
        }

        // add country option
        $columnoptions[] = new rb_column_option(
            'user',
            'country',
            get_string('usercountry', 'block_lp_reportbuilder'),
            "$join.country",
            array(
                'joins' => $join,
                'displayfunc' => 'country_code'
            )
        );

        // The option to include a 'deleted' column for users is defined by
        // the allowdeletedusers setting
        if (get_config('reportbuilder', 'allowdeletedusers') == 1) {
            $columnoptions[] = new rb_column_option(
                    'user',
                    'deleted',
                    get_string('userdeleted', 'block_lp_reportbuilder'),
                    "$join.deleted",
                    array(
                        'displayfunc' => 'yes_or_no'
                    )
            );
        }

        return true;
    }

    protected function add_user_hierarchy_fields_to_columns(&$columnoptions,
        $join='user_hierarchy') {
        global $CFG;
        
        require_once $CFG->dirroot . '/blocks/lp_hierarchy/lib.php';       
        
        $is_freetext = false;
        
        $hierarchy = Hierarchy::get_instance();
        
        $hierarchylabel = $hierarchy->get_hierarchy_field_label_text(true);
        
        $columnoptions[] = new rb_column_option(
                    'user_hierarchy',
                    'hierarchy',
                    $hierarchylabel,
                    "CONCAT_WS(' / ', level1, level2, level3, level4, level5)",
                    array('joins' => $join));

        // Check if this is a free-text DLE
        if (isset($CFG->block_lp_hierarchy_allow_freetext) && 
                $CFG->block_lp_hierarchy_allow_freetext == 1) {
            $is_freetext = true;
        }
        
        // Derive the level names from the hierarchy which has been set up
        $depths = $hierarchy->get_depths();
        $max_depth_level = $hierarchy->get_max_depth();
        
        foreach ($depths as $depth) {
            if ($is_freetext && $max_depth_level == $depth->level) {
                // When free-text is enabled the column is held differently
                $columnoptions[] = new rb_column_option('user_hierarchy', 
                    'freetexthierarchy', 
                    $depth->fullname, 
                    'freetext',
                    array('joins' => $join));
            }
            else {
                $columnoptions[] = new rb_column_option(
                    'user_hierarchy',
                    'level' . $depth->level,
                    $depth->fullname,
                    'level' . $depth->level,
                    array('joins' => $join));
            }
        }
        
        $columnoptions[] = new rb_column_option(
             'user_hierarchy',
             'hierarchyid',
             get_string('orgunitid', 'block_lp_reportbuilder'),
             'hierarchyid',
             array('joins' => $join));


        return $columnoptions;
    }
        
    /**
     * Adds some common user field to the $filteroptions array
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     */
    protected function add_user_fields_to_filters(&$filteroptions) {
        // auto-generate filters for user fields
        $fields = array(
            'fullname' => get_string('userfullname', 'block_lp_reportbuilder'),
            'firstname' => get_string('firstname'),
            'lastname' => get_string('lastname'),
            'username' => get_string('username'),
            'email' => get_string('email'),
            'idnumber' => get_string('useridnumber', 'block_lp_reportbuilder'),
            'city' => get_string('usercity', 'block_lp_reportbuilder'),            
        );
        
        foreach ($fields as $field => $name) {
            $filteroptions[] = new rb_filter_option(
                'user',
                $field,
                $name,
                'text'
            );
        }

        // pulldown with list of countries
        $select_width_options = rb_filter_option::select_width_limiter();
        $filteroptions[] = new rb_filter_option(
            'user',
            'country',
            get_string('usercountry', 'block_lp_reportbuilder'),
            'simpleselect',
            array(
                'selectchoices' => get_string_manager()->get_list_of_countries(),
                'selectoptions' => array_merge($select_width_options, array('datatype' => 'text')),
            )
        );
        
        // Only include the 'Show deleted' filter if it has been configured
        if (get_config('reportbuilder', 'allowdeletedusers') == 1) {
            $filteroptions[] = new rb_filter_option(
                'user',
                'deleted',
                get_string('showdeleted', 'block_lp_reportbuilder'),
                'simpleselect',
                array(
                    'selectchoices' => array(0 => get_string('no'), 1 => get_string('yes'))
                )
            );
        }
        
        $filteroptions[] = new rb_filter_option(
            'user',
            'lastlogin',
            get_string('userlastlogin', 'block_lp_reportbuilder'),
            'date');
        
        return true;
    }

    /**
     * Gets the configured hierarchy access for a user
     * 
     * @global object $CFG
     * @global type $USER
     * @return type 
     */
    protected function get_accessible_hierarchy_list() {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/blocks/lp_hierarchy/lib.php");
        
        $accessible_hierarchy_list = '';
        
        // Check for hierarchy restrictions
        // Bug #5965 - Nasty hack, but safer than a big rewrite DPMH 
        if (!empty($GLOBALS['scheduledreportuser'])) {
            $hierarchy = Hierarchy::get_instance();
            
            $accessible_hierarchy_list = $hierarchy->get_report_builder_hierarchy_access($GLOBALS['scheduledreportuser']);             
        } else if (has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))
                || has_capability('block/lp_reportbuilder:viewanyhierarchy', get_context_instance(CONTEXT_SYSTEM))) {
            // The site admin can use view details on any hierarchy
            $accessible_hierarchy_list = '';
        } else if (has_capability('block/lp_reportbuilder:viewreports', get_context_instance(CONTEXT_SYSTEM))) {
            // The user has permission to view reports
            $hierarchy = Hierarchy::get_instance();
            
            $accessible_hierarchy_list = $hierarchy->get_report_builder_hierarchy_access($USER->id); 
        }
         
        return $accessible_hierarchy_list;
    }
    
    /**
     * Adds any user profile fields to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated if
     *                         any user custom fields exist
     * @param string $join Name of join containing user id to join on
     * @param string $joinfield Name of user id field to join on
     * @return boolean True if user custom fields exist
     */
    protected function add_user_custom_fields_to_joinlist(&$joinlist,
        $join, $joinfield) {
        global $CFG, $DB;

        // add all user custom fields to join list
        if($custom_fields = $DB->get_records('user_info_field')) {
            foreach($custom_fields as $custom_field) {
                $id = $custom_field->id;
                $key = "user_$id";
                $joinlist[] = new rb_join(
                    $key,
                    'LEFT',
                    $CFG->prefix . 'user_info_data',
                    "$key.userid = $join.$joinfield AND $key.fieldid = $id",
                    REPORT_BUILDER_RELATION_MANY_TO_MANY, // added to ensure the count query remains valid
                    $join
                );
            }
            return true;
        }
        return false;
    }


    /**
     * Adds any user profile fields to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated if
     *                              any user custom fields exist
     * @return boolean True if user custom fields exist
     */
    protected function add_user_custom_fields_to_columns(&$columnoptions) {
        global $DB;
    	// auto-generate columns for each user custom field
        if($custom_fields =
            $DB->get_records('user_info_field')) {
            foreach($custom_fields as $custom_field) {
            	$shortname = $custom_field->shortname;
                $name = $custom_field->name;
                $key = "user_$custom_field->id";
                $type = $custom_field->datatype;
                
                 if (!in_array($type, array('hierarchy', 'hierarchytext'))) {
                    $columnoptions[] = new rb_column_option(
                        'user_profile',
                        $key,
                        $name,
                        "$key.data",
                        array('joins' => $key)
                    );	
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Adds any user profile fields to the $filteroptions array as text filters
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated if
     *                              any user custom fields exist
     * @return boolean True if user custom fields exist
     */
    protected function add_user_custom_fields_to_filters(&$filteroptions) {
        global $DB;

        if ($custom_fields = $DB->get_records('user_info_field', array(), '', 'id,shortname,name,datatype')) {
            foreach($custom_fields as $custom_field) {
                $shortname = $custom_field->shortname;
            	$name = $custom_field->name;
                $key = "user_$custom_field->id";
                $type = $custom_field->datatype;
                
                if (!in_array($type, array('hierarchy', 'hierarchytext'))) {
                    $filteroptions[] = new rb_filter_option(
	                    'user_profile',
	                    $key,
	                    $name,
	                    'text'); 
                }   
            }
            return true;
        }
        return false;
    }
    

    /**
     * Adds the course table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'course id' field
     * @param string $field Name of course id field to join on
     * @return boolean True
     */
    protected function add_course_table_to_joinlist(&$joinlist, $join, $field) {
        global $CFG;

        $joinlist[] = new rb_join(
            'course',
            'LEFT',
            $CFG->prefix . 'course',
            "course.id = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );
    }


    /**
     * Adds some common course info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $join Name of the join that provides the 'course' table
     *
     * @return True
     */
    protected function add_course_fields_to_columns(&$columnoptions, $join='course') {
        global $DB;
    	$columnoptions[] = new rb_column_option(
            'course',
            'fullname',
            get_string('coursename', 'block_lp_reportbuilder'),
            "$join.fullname",
            array('joins' => $join)
        );
        $columnoptions[] = new rb_column_option(
            'course',
            'courselink',
            get_string('coursenamelinked', 'block_lp_reportbuilder'),
            "$join.fullname",
            array(
                'joins' => $join,
                'displayfunc' => 'link_course',
                'defaultheading' => get_string('coursename', 'block_lp_reportbuilder'),
                'extrafields' => array('course_id' => "$join.id", 'course_visible' => "$join.visible")
            )
        );
        
        $columnoptions[] = new rb_column_option(
            'course',
            'courselinktomodule',
            get_string('coursenamelinkedtomodule', 'block_lp_reportbuilder'),
            "$join.fullname",
            array(
                'joins' => $join,
                'displayfunc' => 'link_course_to_module_report',
                'defaultheading' => get_string('coursename', 'block_lp_reportbuilder'),
                'extrafields' => array('course_id' => "$join.id", 'course_visible' => "$join.visible")
            )
        );
        /*
        $columnoptions[] = new rb_column_option(
            'course',
            'courselinkicon',
            get_string('coursenamelinkedicon', 'block_lp_reportbuilder'),
            "$join.fullname",
            array(
                'joins' => $join,
                'displayfunc' => 'link_course_icon',
                'defaultheading' => get_string('coursename', 'block_lp_reportbuilder'),
                'extrafields' => array(
                    'course_id' => "$join.id",
                    'course_icon' => "$join.icon",
                    'course_visible' => "$join.visible"
                )
            )
        );*/
        /*
        $columnoptions[] = new rb_column_option(
            'course',
            'icon',
            get_string('courseicon', 'block_lp_reportbuilder'),
            "$join.icon",
            array(
                'joins' => $join,
                'displayfunc' => 'course_icon',
                'defaultheading' => get_string('courseicon', 'block_lp_reportbuilder'),
                'extrafields' => array(
                    'course_name' => "$join.fullname",
                    'course_id' => "$join.id",
                )
            )
        );
        */
        $columnoptions[] = new rb_column_option(
            'course',
            'shortname',
            get_string('courseshortname', 'block_lp_reportbuilder'),
            "$join.shortname",
            array('joins' => $join)
        );
        $columnoptions[] = new rb_column_option(
            'course',
            'idnumber',
            get_string('courseidnumber', 'block_lp_reportbuilder'),
            "$join.idnumber",
            array('joins' => $join)
        );
        $columnoptions[] = new rb_column_option(
            'course',
            'id',
            get_string('courseid', 'block_lp_reportbuilder'),
            "$join.id",
            array('joins' => $join)
        );
        $columnoptions[] = new rb_column_option(
            'course',
            'startdate',
            get_string('coursestartdate', 'block_lp_reportbuilder'),
            "$join.startdate",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_date',
            )
        );
        $columnoptions[] = new rb_column_option(
            'course',
            'name_and_summary',
            get_string('coursenameandsummary', 'block_lp_reportbuilder'),
            // case used to merge even if one value is null
            "CASE WHEN $join.fullname IS NULL THEN $join.summary
                WHEN $join.summary IS NULL THEN $join.fullname
                ELSE " . $DB->sql_concat("$join.fullname", "'<br />'",
                    "$join.summary") . ' END',
            array(
                'joins' => $join,
            )
        );

        $columnoptions[] = new rb_column_option(
            'course',
            'visible',
            get_string('visible', 'block_lp_reportbuilder'),
            "$join.visible",
            array('joins' => $join,
                    'displayfunc' => 'yes_or_no')
        );
        
       /* $columnoptions[] = new rb_column_option(
            'course',
            'coursetypeicon',
            get_string('coursetypeicon', 'block_lp_reportbuilder'),
            "$join.coursetype",
            array(
                'joins' => $join,
                'displayfunc' => 'course_type_icon',
                'defaultheading' => get_string('coursetypeicon', 'block_lp_reportbuilder'),
            )
        );*/
        return true;
    }

    
    /**
     * Adds some common course filters to the $filteroptions array
     *
     * @param array &$columnoptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     */
    protected function add_course_fields_to_filters(&$filteroptions) {
        /*$filteroptions[] = new rb_filter_option(
            'course',
            'fullname',
            get_string('coursename', 'block_lp_reportbuilder'),
            'text'
        );*/
         $filteroptions[] = new rb_filter_option(
            'course',
            'fullname',
            get_string('coursename', 'block_lp_reportbuilder'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'course',
            'shortname',
            get_string('courseshortname', 'block_lp_reportbuilder'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'course',
            'idnumber',
            get_string('courseidnumber', 'block_lp_reportbuilder'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'course',
            'startdate',
            get_string('coursestartdate', 'block_lp_reportbuilder'),
            'date'
        );
        $filteroptions[] = new rb_filter_option(
            'course',
            'name_and_summary',
            get_string('coursenameandsummary', 'block_lp_reportbuilder'),
            'textarea'
        );
        return true;
    }


    /**
     * Adds any course custom fields to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated if
     *                         any course custom fields exist
     * @param string $join Name of join containing course id to join on
     * @param string $joinfield Name of course id field to join on
     * @return boolean True if course custom fields exist
     */
    protected function add_course_custom_fields_to_joinlist(&$joinlist,
        $join, $joinfield) {
        global $CFG, $DB;
/*
        // add all course custom fields to join list
        if($custom_fields = $DB->get_records('course_info_field')) {
            foreach($custom_fields as $custom_field) {
                $id = $custom_field->id;
                $key = "course_$custom_field->id";
                $joinlist[] = new rb_join(
                    $key,
                    'LEFT',
                    $CFG->prefix . 'course_info_data',
                    "$key.courseid = $join.$joinfield AND $key.fieldid = $id",
                    REPORT_BUILDER_RELATION_ONE_TO_ONE,
                    $join
                );
            }
            return true;
        }
        */
        return true;
        //return false;
    }


    /**
     * Adds any course custom fields to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated if
     *                              any course custom fields exist
     * @return boolean True if course custom fields exist
     */
    protected function add_course_custom_fields_to_columns(&$columnoptions) {
        global $DB;
        /*
    	// auto-generate columns for each course custom field
        if($custom_fields =
            $DB->get_records('course_info_field')) {
            foreach($custom_fields as $custom_field) {
                $name = $custom_field->fullname;
                $key = "course_$custom_field->id";
                $columnoptions[] = new rb_column_option(
                    'course_custom_fields',
                    $key,
                    $name,
                    "$key.data",
                    array('joins' => $key)
                );
            }
            return true;
        }
        return false;*/
        return true;
    }


    /**
     * Adds any course custom fields to the $filteroptions array as text filters
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated if
     *                              any course custom fields exist
     * @return boolean True if course custom fields exist
     */
    protected function add_course_custom_fields_to_filters(&$filteroptions) {
        global $DB;
        /*
    	if($custom_fields = $DB->get_records('course_info_field','','','','id,shortname,fullname')) {
            foreach($custom_fields as $custom_field) {
                $name = $custom_field->fullname;
                $key = "course_$custom_field->id";
                $filteroptions[] = new rb_filter_option(
                    'course_custom_fields',
                    $key,
                    $name,
                    'text'
                );
            }
            return true;
        }
        return false;
        */
        return true;
    }



    /**
     * Adds the course_category table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include course_category
     * @param string $join Name of the join that provides the 'course' table
     * @param string $field Name of category id field to join on
     * @return boolean True
     */
    protected function add_course_category_table_to_joinlist(&$joinlist,
        $join, $field) {

        global $CFG;

        $joinlist[] = new rb_join(
            'course_category',
            'LEFT',
            $CFG->prefix . 'course_categories',
            "course_category.id = $join.$field",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            $join
        );

        return true;
    }


    /**
     * Adds some common course category info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $catjoin Name of the join that provides the
     *                        'course_categories' table
     * @param string $coursejoin Name of the join that provides the
     *                           'course' table
     * @return True
     */
    protected function add_course_category_fields_to_columns(&$columnoptions,
        $catjoin='course_category', $coursejoin='course') {
        $columnoptions[] = new rb_column_option(
                'course_category',
                'name',
                get_string('coursecategory', 'block_lp_reportbuilder'),
                "$catjoin.name",
                array('joins' => $catjoin)
        );
        $columnoptions[] = new rb_column_option(
                'course_category',
                'namelink',
                get_string('coursecategorylinked', 'block_lp_reportbuilder'),
                "$catjoin.name",
                array(
                    'joins' => $catjoin,
                    'displayfunc' => 'link_course_category',
                    'defaultheading' => get_string('category', 'block_lp_reportbuilder'),
                    'extrafields' => array('cat_id' => "$catjoin.id", 'cat_visible' => "$catjoin.visible")
                )
        );
       /* 
        $columnoptions[] = new rb_column_option(
                'course_category',
                'namelinkicon',
                get_string('coursecategorylinkedicon', 'block_lp_reportbuilder'),
                "$catjoin.name",
                array(
                    'joins' => $catjoin,
                    'displayfunc' => 'link_course_category_icon',
                    'defaultheading' => get_string('category', 'block_lp_reportbuilder'),
                    'extrafields' => array(
                        'cat_id' => "$catjoin.id",
                        'cat_icon' => "$catjoin.icon",
                        'cat_visible' => "$catjoin.visible"
                    )
                )
        ); */
        $columnoptions[] = new rb_column_option(
                'course_category',
                'id',
                get_string('coursecategoryid', 'block_lp_reportbuilder'),
                "$coursejoin.category",
                array('joins' => $coursejoin)
        );
        
        $columnoptions[] = new rb_column_option(
            'course_category',
            'visible',
            get_string('coursecategoryvisible', 'block_lp_reportbuilder'),
            "$catjoin.visible",
            array('joins' => $catjoin,
                    'displayfunc' => 'yes_or_no')
        );
        
        return true;
    }


    /**
     * Adds some common course category filters to the $filteroptions array
     *
     * @param array &$columnoptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     */
    protected function add_course_category_fields_to_filters(&$filteroptions) {
        $filteroptions[] = new rb_filter_option(
            'course_category',
            'id',
            get_string('coursecategory', 'block_lp_reportbuilder'),
            'select',
            array(
                'selectfunc' => 'course_categories_list',
                'selectoptions' => rb_filter_option::select_width_limiter(),
            )
        );
        return true;
    }

    protected function add_hierarchy_fields_to_filters(&$filteroptions) {
        global $CFG;
        require_once $CFG->dirroot . '/blocks/lp_hierarchy/lib.php';
        
        $hierarchy = Hierarchy::get_instance();
        
        $hierarchylabel = $hierarchy->get_hierarchy_field_label_text(true);
        $filteroptions[] = new rb_filter_option(
            'user_hierarchy', 
            'hierarchyid', 
            $hierarchylabel, 
            'org', 
            array(                	       
                'selectfunc' => 'hierarchy_list'
            )
        );
        
        $filteroptions[] = new rb_filter_option(
            'user_hierarchy', 
            'hierarchyid', 
            $hierarchylabel, 
            'select', 
            array(                	       
                'selectfunc' => 'hierarchy_list'
            )
        );
        
        $filteroptions[] = new rb_filter_option(
            'user_hierarchy', 
            'hierarchyid', 
            //"Choose Filter Level",
            $hierarchylabel, 
            'simple_hierarchy_select', 
            array(                	       
               // 'selectfunc' => 'hierarchy_list'
            )
        );
        
        
        /*$filteroptions[] = new rb_filter_option(
            'user_hierarchy',
            'hierarchyid',
            'User\'s Hierarchy',
            'org'
        );*/
        return true;
    }

    /**
     * Adds the pos_assignment, pos and org tables to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the 'user' table
     * @param string $field Name of user id field to join on
     * @return boolean True
     */
    protected function add_position_tables_to_joinlist(&$joinlist,
        $join, $field) {

        global $CFG;

        // to get access to position type constants
       // require_once($CFG->dirroot . '/hierarchy/type/position/lib.php');
 /*
        $joinlist[] =new rb_join(
            'user_hierarchy',
            'LEFT',
            '{lp_user_hierarchy}',
            "user_hierarchy.userid = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );*/
/*
        $joinlist[] = new rb_join(
            'organisation',
            'LEFT',
            $CFG->prefix . 'org',
            'organisation.id = position_assignment.organisationid',
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'position_assignment'
        );

        $joinlist[] = new rb_join(
            'position',
            'LEFT',
            $CFG->prefix . 'pos',
            'position.id = position_assignment.positionid',
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'position_assignment'
        );
*/
        return true;
    }


    /**
     * Adds some common user position info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $posassign Name of the join that provides the
     *                          'pos_assignment' table.
     * @param string $org Name of the join that provides the 'org' table.
     * @param string $pos Name of the join that provides the 'pos' table.
     *
     * @return True
     */
    protected function add_position_fields_to_columns(&$columnoptions,
        $posassign='position_assignment',
        $org='organisation', $pos='position') {

        $columnoptions[] = new rb_column_option(
            'user',
            'organisationid',
            get_string('usersorgid', 'block_lp_reportbuilder'),
            "$posassign.organisationid",
            array('joins' => $posassign)
        );
        $columnoptions[] = new rb_column_option(
            'user',
            'organisationpath',
            get_string('usersorgpathids', 'block_lp_reportbuilder'),
            "$org.path",
            array('joins' => $org)
        );
        $columnoptions[] = new rb_column_option(
            'user',
            'organisation',
            get_string('usersorgname', 'block_lp_reportbuilder'),
            "$org.fullname",
            array('joins' => $org)
        );
        $columnoptions[] = new rb_column_option(
            'user',
            'positionid',
            get_string('usersposid', 'block_lp_reportbuilder'),
            "$posassign.positionid",
            array('joins' => $posassign)
        );
        $columnoptions[] = new rb_column_option(
            'user',
            'positionpath',
            get_string('userspospathids', 'block_lp_reportbuilder'),
            "$pos.path",
            array('joins' => $pos)
        );
        $columnoptions[] = new rb_column_option(
            'user',
            'position',
            get_string('userspos', 'block_lp_reportbuilder'),
            "$pos.fullname",
            array('joins' => $pos)
        );
        $columnoptions[] = new rb_column_option(
            'user',
            'title',
            get_string('usersjobtitle', 'block_lp_reportbuilder'),
            "$posassign.fullname",
            array('joins' => $posassign)
        );
        return true;
    }


    /**
     * Adds some common user position filters to the $filteroptions array
     *
     * @param array &$columnoptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     */
    protected function add_position_fields_to_filters(&$filteroptions) {
     /*
        $filteroptions[] = new rb_filter_option(
            'user',
            'organisationid',
            get_string('participantscurrentorgbasic', 'block_lp_reportbuilder'),
            'select',
            array(
                'selectfunc' => 'organisations_list',
                'selectoptions' => rb_filter_option::select_width_limiter(),
            )
        );
       */ 
        $filteroptions[] = new rb_filter_option(
            'lp_user_hierarchy',
            'hierarchyid',
           'Current Org unit',
            'text'
        );
        
        /*
        $filteroptions[] = new rb_filter_option(
            'user',
            'positionid',
            get_string('participantscurrentposbasic', 'block_lp_reportbuilder'),
            'select',
            array(
                'selectfunc' => 'positions_list',
                'selectoptions' => rb_filter_option::select_width_limiter(),
            )
        );
        $filteroptions[] = new rb_filter_option(
            'user',
            'positionpath',
            get_string('participantscurrentpos', 'block_lp_reportbuilder'),
            'pos'
        );
        */
        return true;
    }


    /**
     * Adds the manager_role_assignment and manager tables to the $joinlist
     * array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'position_assignment' table
     * @param string $field Name of reportstoid field to join on
     * @return boolean True
     */
    protected function add_manager_tables_to_joinlist(&$joinlist,
        $join, $field) {

        global $CFG, $DB;
/*
        // only include these joins if the manager role is defined
        if($managerroleid = $DB->get_field('role','id','shortname','manager')) {
            $joinlist[] = new rb_join(
                'manager_role_assignment',
                'LEFT',
                $CFG->prefix . 'role_assignments',
                "(manager_role_assignment.id = $join.$field" .
                    ' AND manager_role_assignment.roleid = ' .
                    $managerroleid . ')',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'position_assignment'
            );
            $joinlist[] = new rb_join(
                'manager',
                'LEFT',
                $CFG->prefix . 'user',
                'manager.id = manager_role_assignment.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'manager_role_assignment'
            );
        }
*/
        return true;
    }


    /**
     * Adds some common user manager info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $manager Name of the join that provides the
     *                          'manager' table.
     * @param string $org Name of the join that provides the 'org' table.
     * @param string $pos Name of the join that provides the 'pos' table.
     *
     * @return True
     */
    protected function add_manager_fields_to_columns(&$columnoptions,
        $manager='manager') {
/*
        $columnoptions[] = new rb_column_option(
            'user',
            'managername',
            get_string('usersmanagername', 'local_reportbuilder'),
            sql_fullname("$manager.firstname","$manager.lastname"),
            array('joins' => $manager)
        );
        */
        return true;
    }


    /**
     * Adds some common manager filters to the $filteroptions array
     *
     * @param array &$columnoptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     */
    protected function add_manager_fields_to_filters(&$filteroptions) {
        $filteroptions[] = new rb_filter_option(
            'user',
            'managername',
            get_string('managername', 'block_lp_reportbuilder'),
            'text'
        );
        return true;
    }


    /**
     * Adds the tags tables to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'course' table
     * @param string $field Name of course id field to join on
     * @return boolean True
     */
    protected function add_course_tags_tables_to_joinlist(&$joinlist,
        $join, $field) {

        global $CFG, $DB;

        $joinlist[] = new rb_join(
            'tagids',
            'LEFT',
            // subquery as table name
            "(SELECT crs.id AS cid, " .
                sql_group_concat(sql_cast2char('t.id'),'|') .
                " AS idlist FROM {$CFG->prefix}course crs
                LEFT JOIN {$CFG->prefix}tag_instance ti
                    ON crs.id=ti.itemid AND ti.itemtype='course'
                LEFT JOIN {$CFG->prefix}tag t
                    ON ti.tagid=t.id AND t.tagtype='official'
                GROUP BY crs.id)",
            "tagids.cid = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );
/*
        // create a join for each official tag
        if($tags = $DB->get_records('tag', array(), 'tagtype', 'official')) {
            foreach($tags as $tag) {
                $tagid = $tag->id;
                $name = "course_tag_$tagid";
                $joinlist[] = new rb_join(
                    $name,
                    'LEFT',
                    $CFG->prefix . 'tag_instance',
                    "($name.itemid = $join.$field AND $name.tagid = $tagid " .
                        "AND $name.itemtype='course')",
                    REPORT_BUILDER_RELATION_ONE_TO_ONE,
                    $join
                );
            }
        }
*/
        return true;
    }

    /**
     * Adds some common course tag info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $manager Name of the join that provides the
     *                          'tagids' table.
     *
     * @return True
     */
    protected function add_course_tag_fields_to_columns(&$columnoptions,
        $tagids='tagids') {
        global $DB;
        
        $columnoptions[] = new rb_column_option(
            'course',
            'tagids',
            get_string('coursetagids', 'block_lp_reportbuilder'),
            "$tagids.idlist",
            array('joins' => $tagids)
        );
/*
        // create a on/off field for every official tag
        if($tags = $DB->get_records('tag', 'tagtype', 'official')) {
            foreach($tags as $tag) {
                $tagid = $tag->id;
                $name = $tag->name;
                $join = "course_tag_$tagid";
                $columnoptions[] = new rb_column_option(
                    'tags',
                    $join,
                    get_string('taggedx', 'local_reportbuilder', $name),
                    "CASE WHEN $join.id IS NOT NULL THEN 1 ELSE 0 END",
                    array(
                        'joins' => $join,
                        'displayfunc' => 'yes_no',
                    )
                );
            }
        }
        */
        return true;
    }


    /**
     * Adds some common course tag filters to the $filteroptions array
     *
     * @param array &$columnoptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     */
    protected function add_course_tag_fields_to_filters(&$filteroptions) {
        global $DB;
        /*
        // create a filter for every official tag
        if($tags = $DB->get_records('tag', 'tagtype', 'official')) {
            foreach($tags as $tag) {
                $tagid = $tag->id;
                $name = $tag->name;
                $join = "course_tag_$tagid";
                $filteroptions[] = new rb_filter_option(
                    'tags',
                    $join,
                    get_string('taggedx', 'local_reportbuilder', $name),
                    'simpleselect',
                    array('selectchoices' => array(
                        1 => get_string('yes'), 0 => get_string('no'))
                    )
                );
            }
        }
        */
        return true;
    }


} // end of rb_base_source class
