<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/lib.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/report_forms.php');
require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');

global $DB, $USER, $PAGE;

require_login();

$userid = required_param('userid', PARAM_INT);
$hierarchy_list = optional_param('hierids', '', PARAM_TEXT);
$hierarchy_text = '';

$hierarchy = Hierarchy::get_instance();

$csv = $hierarchy->get_report_builder_hierarchy_access_list_for_UI($userid);
$hierarchy_user_record = $hierarchy->get_user_hierarchy($userid);

if ($hierarchy_user_record) {
    $hierarchy_text = $hierarchy_user_record->hierarchy;
}

//Not the best method but existing functions check the !empty($hierarchy_list)
//and exit if it is so the clear code has to be a separate piece. 
if($_POST['clear_all']){
	// Begin the database transaction
    $transaction = $DB->start_delegated_transaction();
	
	// Delete the existing hierarchy information for the user
    if (!$DB->delete_records('report_builder_hierarchy', array('userid' => $userid))) {
    	$transaction->rollback(new Exception('Error deleting from report_builder_hierarchy for userid ' . $userid));

        redirect($returnurl, get_string('error:couldnotcreatenewreport','block_lp_reportbuilder'));
    	exit;
    }

	// All okay so commit changes
    $transaction->allow_commit();
    
    redirect($CFG->wwwroot . '/blocks/lp_reportbuilder/reportadminsettings.php');
		
}else if ($hierarchy_list != '') {
    // Permissions have been edited for this user
    if (update_permissions($userid, $hierarchy_list)) {
        $return_url = $CFG->wwwroot . '/blocks/lp_reportbuilder/reportadminsettings.php';
    
        redirect($return_url);
    }
}

$user = $DB->get_record('user', array('id' => $userid));

require_once($CFG->dirroot.'/local/learningpool/js/setup.php');
include_hierarchy_scripts(array('highlight'=>array('up'=>true,'down'=>true)));

// Define the page layout and header/breadcrumb
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('editreportpermissions', 'block_lp_reportbuilder'));
$PAGE->set_heading(get_string('editreportpermissions', 'block_lp_reportbuilder'));

$return_url = qualified_me();
$previousurl = $CFG->wwwroot.'/blocks/lp_reportbuilder/reportadminsettings.php';
$list_page_url = new moodle_url('/blocks/lp_reportbuilder/reportadminsettings.php');

$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_url(new moodle_url('/blocks/lp_report_builder/editpermissions.php'));
$PAGE->set_context($context);

// Rebuild the navigation bar
$PAGE->navbar->add(get_string('administrationsite'));
$PAGE->navbar->add(get_string('reports'));
$PAGE->navbar->add('Report builder');
$PAGE->navbar->add(get_string('reportadminconfig', 'block_lp_reportbuilder'), $list_page_url);
$PAGE->navbar->add(get_string('editreportpermissions', 'block_lp_reportbuilder'), $return_url);

$renderer = $PAGE->get_renderer('local_lp_enrolment_manager');

echo $renderer->header();
echo $renderer->heading(get_string('editreportpermissions', 'block_lp_reportbuilder'));
echo $renderer->js();

echo '<form id="edit_form" action="' . $return_url .'" method="post">';
echo '<div class="group yui3-u-1-3">';
echo get_string('editpermissionsblurb1', 'block_lp_reportbuilder', $user->firstname . ' ' . $user->lastname) . '<br />';

if ($hierarchy_text != '') {
    echo get_string('editpermissionsblurb2', 'block_lp_reportbuilder', $hierarchy_text) . '<br />';
}

echo '<br />' . get_string('editpermissionsblurb3', 'block_lp_reportbuilder', get_string('savechanges')) . '<br /><br />';
echo '<input type="hidden" id="userid" name="userid" value="' . $userid . '">';
echo '<input type="submit" id="id_submitbutton" name="submitbutton" value="' . get_string('savechanges') . '">';
echo "<input type=\"button\" id=\"id_cancelbutton\" name=\"cancelbutton\" value=\"" . get_string('cancel') . "\" onclick=\"window.location='" . $previousurl . "'\">";
echo '</div>';

echo '<div class="group yui3-u-2-3">';
echo html_writer::tag('style', '.ygtvitem .parent-selected{ background-color:#ff0; }', array('type'=>'text/css'));
echo html_writer::tag('div','', array('id'=>'hierarchy-tree'));
echo "<input type='submit' id='id_clear_all' name='clear_all' value='" . get_string('clear_all','block_lp_reportbuilder') . "' onclick=\"if(confirm('" . get_string('confirm_clear','block_lp_reportbuilder') . "')){return true;}else{return false;}\" >";
echo '</div>';
echo '</form>';

echo $renderer->footer();

/**
 * Saves the user's hierarchy to the report_builder_hierarchy table
 * @global type $DB
 * @param type $userid Unique identifier of the user
 * @param type $hierarchy_list Comma-separated list of hierarchy ID values
 * e.g. 1, 1_2, 1_2_3.  Depth are separated by underscores.
 * @return type true for success or false for an error
 */
function update_permissions($userid, $hierarchy_list) {
    global $CFG, $DB;

	class update_permission_access{
		public $id=-1,$showchildren=false,$access=false,$children=array();
		public function __construct($id,$access=false,$showc=false){
			$this->id = $id;
			$this->showchildren = $showc;
			$this->access = $access;
		}
		public function add_child($id,$showc){
			$this->children[$id] = new update_permission_access($id,$showc);
		}
		public function retrieve_inserts($uid ,&$records){
			if($this->showchildren && count($this->children)){
				$records[] = new ins_access_obj($uid,$this->id,($this->showchildren)?1:0);
			}else if($this->showchildren && empty($this->children) && $this->access){
				$records[] = new ins_access_obj($uid,$this->id,0);
			}else if(!empty($this->children)){
				foreach($this->children as $child){
					$child->retrieve_inserts($uid,$records);
				}
			}
			return true;
		}
	}
	
	class ins_access_obj{
		public $userid,$hierarchyid,$showchildren;
		public function __construct($uid =-1 ,$hid =-1,$sc = 0){
			$this->userid = $uid;
			$this->hierarchyid = $hid;
			$this->showchildren = $sc;
		}
	}

    // Check the required paramters
    if (!$userid || $hierarchy_list  == '') {
        return false;
    }
    
    $returnurl = $CFG->wwwroot."/blocks/lp_reportbuilder/editpermissions.php";
    
    $hierarchy_input_array = array();

    $hierarchy_input_array = explode(',', $hierarchy_list);

    $hierarchy_array = array();
    $hierarchy_array_pntr = null;
	
	// Changes by rwm
	foreach($hierarchy_input_array as $hierinp){
		$hierarchy_item = explode('_',$hierinp);
		$i = 1;
		$max = count($hierarchy_item);
		$hierarchy_array_pntr = &$hierarchy_array;
		foreach($hierarchy_item as $hi){
			if(!isset($hierarchy_array_pntr[$hi])){
				$hierarchy_array_pntr[$hi] = new update_permission_access($hi,($max === $i)?true:false,$max === $i);
			}
			
			$hierarchy_array_pntr = &$hierarchy_array_pntr[$hi]->children; 
			
			$i++;
		}
		
	}
	

    // Begin the database transaction
    $transaction = $DB->start_delegated_transaction();
    
    // Delete the existing hierarchy information for the user
    if (!$DB->delete_records('report_builder_hierarchy', array('userid' => $userid))) {
    	$transaction->rollback(new Exception('Error deleting from report_builder_hierarchy for userid ' . $userid));

        redirect($returnurl, get_string('error:couldnotcreatenewreport','block_lp_reportbuilder'));
    	return false;
    }
    
    // Calculate the maximum depth
    $max_depth = $CFG->block_lp_hierarchy_depth - $CFG->block_lp_hierarchy_allow_freetext;
    
	$records = array();
	foreach($hierarchy_array as $ha){
		$ha->retrieve_inserts($userid,$records);
	}
	
	foreach($records as $ins){
		if (!$DB->insert_record('report_builder_hierarchy', $ins)) {
            $transaction->rollback(new Exception('Could not record in report_builder_hierarchy'));

            redirect($returnurl, get_string('error:couldnotcreatenewreport','block_lp_reportbuilder'));
        }
	}
	
	// All okay so commit changes
    $transaction->allow_commit();
	
    return true;
}


?>
