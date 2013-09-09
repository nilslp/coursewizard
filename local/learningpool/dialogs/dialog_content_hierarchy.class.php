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
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @package lp
 * @subpackage dialogs 
 */

/**
 * Hierarchy dialog generator
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/learningpool/dialogs/dialog_content.class.php');
require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');
#require_once($CFG->dirroot.'/hierarchy/lib.php');

/**
 * Class for generating single select hierarchy dialog markup
 *
 * @access  public
 */
class lp_dialog_content_hierarchy extends lp_dialog_content {

    /**
     * Hierarchy object instance
     *
     * @access  public
     * @var     object
     */
    public $hierarchy;


    /**
     * Flag to disable framework picker
     *
     * @access  public
     * @var     boolean
     */
    public $disable_picker = false;


    /**
     * If you are making access checks seperately, you can disable
     * the internal checks by setting this to true
     *
     * @access  public
     * @var     boolean
     */
    public $skip_access_checks = false;


    /**
     * Only display hierarchy templates, rather than items
     *
     * @access  public
     * @var     boolean
     */
    public $templates_only = false;


    /**
     * Enable search tab content
     *
     * @access  public
     * @var     bool
     */
    public $search_code = true;


    /**
     * Switching frameworks
     *
     * @access  public
     * @var     boolean
     */
    public $switch_frameworks = false;


    /**
     * Load hierarchy specific information and make some
     * capability checks (which can be disabled)
     *
     * @see     lp_dialog_hierarchy::skip_access_checks
     *
     * @access  public
     * @param   $type           string  Hierarchy type
     * @param   $frameworkid    int     Framework id (optional)
     */
    public function __construct($type, $frameworkid = 0) {

        // Make some capability checks
        if (!$this->skip_access_checks) {
            require_login();
            #require_capability("moodle/local:view{$type}", get_system_context());
        }

        // Load hierarchy instance
        $this->hierarchy = Hierarchy::get_instance();

        // Set lang file
        $this->lang_file = $type;

        // Load framework
        #$this->set_framework($frameworkid);

        // Check if switching frameworks
        #$this->switch_frameworks = optional_param('switchframework', false, PARAM_BOOL);
    }


    /**
     * Set framework hierarchy
     *
     * @access  public
     * @param   $frameworkid    int
     */
    public function set_framework($frameworkid) {

        if (!$framework = $this->hierarchy->get_framework($frameworkid, true)) {
            print_error('frameworkdoesntexist', 'hierarchy', $frameworkid);
        }

        $this->framework = $framework;
    }

    /**
     * Cleans out the hierarchy list and leaves only those items a user has access to
     * @global object $USER
     * @global object $CFG
     * @param array $hieritems  - full hierarchy list
     * @return array  - the cleaned list 
     */
    function remove_excluded( $hieritems ){
        global $USER,$CFG;
        
        $keep = array();
        $allow = $this->hierarchy->get_report_builder_hierarchy_access($USER->id);
        if ($allow != -1 && !empty($allow)){
            if (strpos($allow,',') > 0){
                $keep = array_merge($keep,explode(',',$allow));
            } else {
                $keep [] = $allow;
            }
        } else {
            $keep = array_keys($hieritems);
        }

        $remove = array();
        if (!empty($CFG->block_lp_hierarchy_excluded)){
            if (count($CFG->block_lp_hierarchy_excluded) > 1){
                $remove = array_merge($remove,explode(',', $CFG->block_lp_hierarchy_excluded));
            } else {
                $remove []= $CFG->block_lp_hierarchy_excluded;
            }
        }
        
        $cleaned = array();
        foreach ($hieritems as $key => $val){
            if (!in_array($key,$remove) && in_array($key,$keep)){   // remove is absolute
                $cleaned[$key] = $val;
            }
        }
        
        return $cleaned;
    }

    /**
     * Load hierarchy items to display
     *
     * @access  public
     * @param   $parentid   int
     */
    public function load_items($parentid) {
        $this->items = $this->remove_excluded($this->hierarchy->get_items_by_parent($parentid));

        // If we are loading non-root nodes, tell the dialog_content class not to
        // return markup for the whole dialog
        if ($parentid > 0) {
            $this->show_treeview_only = true;
        }

        // Also fill parents array
        $this->parent_items = $this->remove_excluded($this->hierarchy->get_all_parent_items());
    }


    /**
     * Prepend custom markup before treeview
     *
     * @access  protected
     * @return  string
     */
    protected function _prepend_markup() {
        return '';
    }


    /**
     * Should we show the treeview root?
     *
     * @access  protected
     * @return  boolean
     */
    protected function _show_treeview_root() {
        return !$this->show_treeview_only || $this->switch_frameworks;
    }

    /**
     * Generate search interface for hierarchy search
     *
     * @access  public
     * @return  string
     */
    public function generate_search_interface() {
        global $CFG;

        // Grab search page markup
        ob_start();
        require_once $CFG->dirroot.'/local/learningpool/hierarchy/item/search.php';
        return ob_get_clean();
    }
}


/**
 * Class for generating multi select hierarchy dialog markup
 *
 * @access  public
 */
class lp_dialog_content_hierarchy_multi extends lp_dialog_content_hierarchy {

    /**
     * Load hierarchy specific information and make some
     * capability checks (which can be disabled)
     *
     * @see     lp_dialog_hierarchy::skip_access_checks
     *
     * @access  public
     * @param   $type               string  Hierarchy type
     * @param   $frameworkid        int     Framework id (optional)
     * @param   $skipaccesschecks   boolean Indicate whether access checks should be performed
     */
    public function __construct($type, $frameworkid = 0, $skipaccesschecks=false) {

        $this->skip_access_checks = $skipaccesschecks;

        // Run parent constructor
        parent::__construct($type, $frameworkid);

        // Set to type multi
        $this->type = self::TYPE_CHOICE_MULTI;

        // Set titles
        $this->select_title = 'locate'.$type;
    }
}

