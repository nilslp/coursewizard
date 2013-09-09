<?php

require_once($CFG->dirroot.'/local/learningpool/dialogs/dialog_content.class.php');

/**
 * Constants for defining JS to load
 */
define('LEARNINGPOOL_JS_DIALOG', 1);
define('LEARNINGPOOL_JS_TREEVIEW', 2);

/**
 * Load appropriate JS and CSS files for lightbox
 *
 * @param $options array Array of option constants
 */
function include_jquery($options = array()) {
    global $CFG, $PAGE;

    $PAGE->requires->js('/local/learningpool/js/lib/jquery-1.6.4.min.js');
    
     // If dialog
    if (in_array(LEARNINGPOOL_JS_DIALOG, $options)) {
        $PAGE->requires->js('/local/learningpool/js/lib/jquery-ui-1.8.17.custom.min.js');
        $PAGE->requires->js('/local/learningpool/js/lib/dialog.js.php');
        $PAGE->requires->js('/local/learningpool/js/lib/jquery.bgiframe.min.js');
    }

    // If treeview enabled
    if (in_array(LEARNINGPOOL_JS_TREEVIEW, $options)) {
        $PAGE->requires->js('/local/learningpool/js/lib/jquery.treeview.min.js');
    }
}

/**
 * TreeView control for the hierarchy. 
 * usage: 
 *  include this file, call the function below and create an empty node
 *  in your document that has the id "hierarchy-tree". The script will do the
 *  rest. A hidden input with the id "hierids" will be appended to your node
 *  - the value of this will reflect the hierarchy levels selected by the user.
 * @global type $CFG
 * @global type $PAGE
 * @param type $options - unused at present
 */
function include_hierarchy_scripts($options = array()) {
    global $CFG, $PAGE;
    $params = array();
    $userid = optional_param('userid', 0, PARAM_INT);
    
    if (!empty($options['direct'])){
        echo "<script type='text/javascript' src='/local/learningpool/js/lib/hierarchy.manip.php'></script>";
    } else {
        
        require_once(dirname(__FILE__) . '/../../../config.php');
        require_once(dirname(__FILE__) .'/../../../blocks/lp_hierarchy/lib.php'); 
            
        $hierarchy = Hierarchy::get_instance();    

        $accesslist = $hierarchy->get_report_builder_hierarchy_access_list_for_UI($userid);
        $accesslist = explode(',',$accesslist);    

        $hierarchy = $hierarchy->build_hierarchy_tree(explode(',',$CFG->block_lp_hierarchy_excluded));
        foreach($accesslist as $id){
            $id = strpos($id, '_') ? substr($id,strrpos($id, '_')+1) : $id;
            $node = &$hierarchy->find_parent($hierarchy->nodes->children, $id);
            $node->access = 1;
        }
        
        $params['hierarchy'] = $hierarchy->nodes;
		if(isset($options['highlight'])){
			$params['highlight'] = $options['highlight'];
		}
         
        $jsconfig = array(
	'name' => 'local_learningpool',
	'fullpath' => '/local/learningpool/js/lib/hierarchy.manip.js',
	'requires' => array(
                        'node',
                        'base', 
                        'json',
                        'io',
                        'event',
                        'yui2-treeview'
                    )
            );

        $PAGE->requires->js_init_call('M.local_learningpool.init', array($params), false, $jsconfig);
    }
}

/**
 * Load appropriate JS and CSS files for lightbox
 *
 * @param $options array Array of option constants
 */
/*
function local_js($options = array()) {
    global $CFG;

    // Include required javascript libraries
    require_js(array(
        $CFG->wwwroot.'/local/learningpool/js/lib/jquery-1.6.4.min.js',
    ));

    // If dialog
    if (in_array(LEARNINGPOOL_JS_DIALOG, $options)) {

        require_js(array(
            $CFG->wwwroot.'/local/learningpool/js/lib/jquery-ui-1.8.17.custom.min.js',
            $CFG->wwwroot.'/local/learningpool/js/lib/dialog.js.php',
            // fix for IE6 select z-index problem
            // TODO only include for IE6 - conditional comments?
            $CFG->wwwroot.'/local/learningpool/js/lib/jquery.bgiframe.min.js',
        ));
    }

    // If treeview enabled
    if (in_array(LEARNINGPOOL_JS_TREEVIEW, $options)) {

        require_js(array(
            $CFG->wwwroot.'/local/learningpool/js/lib/jquery.treeview.min.js',
        ));
    }
}
*/
/**
 * Return markup for 'Currently selected' info in a dialog
 * @param $label the label
 * @param $title the unique title of the dialog
 * @return  $html
 */
function dialog_display_currently_selected($label, $title='') {

    $html = ' <span id="treeview_currently_selected_span_'.$title.'" style="display: none;">';
    $html .= '(<label for="treeview_selected_text_'.$title.'">'.$label.'</label>:&nbsp;';
    $html .= '<em><span id="treeview_selected_text_'.$title.'"></span></em>'; 
    $html .= ')</span>';

    // Also add a hidden field that can hold the currently selected value
    $html .= '<input type="hidden" id="treeview_selected_val_'.$title.'" name="treeview_selected_val_'.$title.'" value="" />';

    return $html;
}
