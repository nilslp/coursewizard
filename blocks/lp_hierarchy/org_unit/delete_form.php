<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class org_unit_delete_form extends moodleform {

    // Define the form
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $item = $this->_customdata['item'];
        $parents = array();
        $hierarchy = Hierarchy::get_instance();
        
        $items  = $hierarchy->get_parent_items();
        $depths = $hierarchy->get_depths();
        //$contact_data = $hierarchy->get_org_unit_contacts();

        // Get maximum depth level
        $max_depth_id = end($depths)->id;

        // Get this item's current depth level
        $depthlevel = 0;
        if ($item->id) {
            $depthlevel = $depths[$item->depthid]->level;
        }
        
        // Add top as an option if adding a new item, or current parent is Top
        if (!$item->id || $item->parentid == 0) {
            $parents[0] = get_string('top', 'block_lp_hierarchy');
        }
        
        $max_decendant_level = 0;
        $offset = 0;
        
        if ($item->id > 0) {
            // Check if this item has any decendants
            // This is important in working out which other parents should be displayed
            $decendants = $hierarchy->get_item_descendants($item->id);

            if ($decendants) {
                // Calculate the deepest level of the decendants
                foreach ($decendants as $decendant) {
                    if ($decendant->level > $max_decendant_level) {
                        $max_decendant_level = $decendant->level;
                    }
                }               
            }
            
            if ($max_decendant_level > 0) {
                // Use this to work out an offset from its this item
                $offset = $max_decendant_level - $depths[$item->depthid]->level;
            }
        }
        
        if ($items) {
            // Cache breadcrumbs
            $breadcrumbs = array();

            foreach ($items as $parent) {
                // Do not show items at the deepest depth
                if ($parent->depthid == $max_depth_id) {
                    continue;
                }

                // Do not show the current item as a parent
                if ($parent->id == $item->id) {
                    continue;
                }
                                
                if ($max_decendant_level != 0 && $offset > 0) {
                    // This item has decendants
                    // Ensure that the possible parent can accomodate this item and decendants
                    if ((1 + $depths[$parent->depthid]->level + $offset) > $CFG->block_lp_hierarchy_depth) {
                        // Moving this item and decendants into this parent would exceed
                        // the maximum depth allowed
                        continue;
                    }
                }
                
                // Convert 'path' to text
                // Remove the initial forward-slash (/)
                $path = preg_replace('/^\//', '', $parent->path);
                $indexes = explode('/', $path);
                
                foreach($indexes as $i) {
                    $breadcrumbs[] = $items[$i]->fullname;
                }
                
                // Make display text
                $display = implode(' / ', $breadcrumbs);
                $parents[$parent->id] = $display;
                unset($breadcrumbs);
            }
        }
        
        $contacts = array();
        
//        if ($contact_data) {
//            $contacts[0] = '';
//            
//             foreach ($contact_data as $contact) {
//                $display_name = $contact->firstname . " " . $contact->lastname;
//                $contacts[$contact->id] = $display_name; 
//             }
//        }
        /// Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
    
        $mform->addElement('select', 'parentid', get_string('parentorgunit', 'block_lp_hierarchy'), $parents);
        // If we only have a "Top" placeholder parentid, lock it
        if (count($parents) <= 1) {
            $mform->setDefault('parentid', 0);            
        }

        $mform->hardFreeze('parentid');
        
        $mform->addElement('text', 'fullname', get_string('fullnameorg', 'block_lp_hierarchy'), 'maxlength="1024" size="50"');
        $mform->addRule('fullname', get_string('missingfullnameorg', 'block_lp_hierarchy'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);
        $mform->hardFreeze('fullname');
        
        $mform->addElement('text', 'shortname', get_string('shortnameorg', 'block_lp_hierarchy'), 'maxlength="100" size="20"');
        $mform->addRule('shortname', get_string('missingshortnameorg', 'block_lp_hierarchy'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_MULTILANG);
        $mform->hardFreeze('shortname');
        
        $mform->addElement('htmleditor', 'description', get_string('description'));
        $mform->setType('description', PARAM_CLEAN);
        $mform->freeze('description');
                
        //$mform->addElement('select', 'contactid', get_string('leadcontact', 'block_lp_hierarchy'), $contacts);
        //$mform->hardFreeze('contactid');
        
        // Get the number of user's associated with this org unit
        $user_count = $hierarchy->get_org_unit_user_count($item->id);        
        $decendants = $hierarchy->get_item_descendants($item->id);
        $decendant_count = count($decendants);
        
        if ($user_count == 0 && $decendant_count == 0) {
            // If no users are associated it is ok to delete
            $this->add_action_buttons(true, get_string('confirmdelete', 'block_lp_hierarchy'));
        }        
        else {
            if ($user_count > 0) {
                if ($user_count > 1) {
                    echo sprintf(get_string('deleteorguserswarningplural', 'block_lp_hierarchy'), $user_count);
                }
                else {
                    echo get_string('deleteorguserswarning', 'block_lp_hierarchy');
                }
            }
            else {
                if ($decendant_count > 0) {
                    if ($decendant_count > 1) {
                        echo sprintf(get_string('deleteorgwarningplural', 'block_lp_hierarchy'), $decendant_count);
                    }
                    else {
                         echo get_string('deleteorgwarning', 'block_lp_hierarchy');	
                    }
                }
            }

            // Otherwise just take the user back to their homepage
            $mform->addElement('button','cancel','Cancel', array('onclick' => "document.location.href=\"{$CFG->wwwroot}/blocks/lp_hierarchy/manage_hierarchy.php\""));       	
        }
    }
}