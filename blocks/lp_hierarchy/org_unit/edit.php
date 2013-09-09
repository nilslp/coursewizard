<?php
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");
require_once("edit_form.php");

require_login();

// Get the query string
$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_context(build_context_path());

// Define the page layout and header/breadcrumb
$PAGE->set_url($CFG->wwwroot . '/blocks/lp_hierarchy/depth/edit.php', array('id' => $id));
// Define the page layout and header/breadcrumb
$PAGE->set_pagelayout('base');
if ($id == 0) {
    $PAGE->set_heading(get_string('addorgunittitle', 'block_lp_hierarchy'));
    $PAGE->set_title(get_string('addorgunittitle', 'block_lp_hierarchy'));
}
else {
    $PAGE->set_heading(get_string('editorgunittitle', 'block_lp_hierarchy'));
    $PAGE->set_title(get_string('editorgunittitle', 'block_lp_hierarchy'));
}

// Build the breadcrumb
$settings_url = new moodle_url('/admin/settings.php?section=blocksettinglp_hierarchy');
$manage_hierarchy = new moodle_url('/blocks/lp_hierarchy/manage_hierarchy.php');
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_lp_hierarchy'), $settings_url);
$PAGE->navbar->add(get_string('manage_hierarchy', 'block_lp_hierarchy'), $manage_hierarchy);

$hierarchy = Hierarchy::get_instance();
$context = get_context_instance(CONTEXT_SYSTEM);

if ($id == 0) {
    // Creating new org unit
    //require_capability('moodle/local:create'.$type.'depth', $context);
    $org = new object();
    $org->id = 0;
    $org->parentid = 0;
}
else {
    // Edit existing org unit
    if (!$org = $hierarchy->get_org_unit_by_id($id)) {
        print_error('e_depth_select', 'block_lp_hierarchy');
    }
}

// Create the form
$datatosend = array('item'=>$org);

$orgform  = new org_unit_edit_form(null, $datatosend);
$orgform->set_data($org);

if ($orgform->is_cancelled()){
	// User clicked 'Cancel'
    redirect("{$CFG->wwwroot}/blocks/lp_hierarchy/manage_hierarchy.php");
}
else if ($neworg = $orgform->get_data()) {
    
   $transaction = $DB->start_delegated_transaction();
   try {

        // Update data
        $neworg->timemodified = time();
        $neworg->modifierid = $USER->id;

        // Need to update depth and sort order if parent changed or new
        if ($neworg->id == 0 || $neworg->parentid != $org->parentid) {
            if ($neworg->parentid > 0) {
                if (!$parent = $DB->get_record('lp_hierarchy', array('id' => $neworg->parentid))) {
                    throw new Exception('e_org_unit_parent_incorrect');
                }

                // Get the depth of the parent
                $parent_depth = $hierarchy->get_depth_by_id($parent->depthid);

                // Work out the depth for this item
                $org_depth = $hierarchy->get_depth_by_level($parent_depth->level + 1);
            }
            else {
                // There is no parent, this is a first-level item
                $org_depth = $hierarchy->get_depth_by_level(1);
            }

            // Create path for finding ancestors
            $neworg->path = ($neworg->parentid == 0 ? '' : $parent->path) . '/' . ($neworg->id != 0 ? $neworg->id : '');

            // Set the depthid
            $neworg->depthid = $org_depth->id;

            // Get the sort order
            // Find highest sortorder of siblings
            if (isset($parent)) {
                $path = $parent->id ? $parent->path : '';
            }
            else {
                $path = '';
            }

            if ($neworg->id != 0) {
                // Create path for finding ancestors
                $neworg->path = ($neworg->parentid == 0 ? '' : $parent->path) . '/' . ($neworg->id != 0 ? $neworg->id : '');
            }

            $sortorder = $hierarchy->get_maximum_hierarchy_sort_order($path);
            $neworg->sortorder = $sortorder + 1;

            if ($neworg->parentid != $org->parentid  && $neworg->id != 0) {
                // The parent has changed -- all decendants must also be moved
                // Get any decendants
                $decendants = $hierarchy->get_item_descendants($neworg->id);
            }
        }

        if ($neworg->id == 0) {
            // This is a new org unit
            unset($neworg->id);

            $neworg->timecreated = time();

            // Find the next sortorder
            $neworg->sortorder = $sortorder + 1;

            // Increment the sortorder for all other items
            $DB->execute("UPDATE {$CFG->prefix}lp_hierarchy SET sortorder = sortorder + 1 WHERE sortorder > $sortorder");

            try { 
                $neworg->id = $DB->insert_record('lp_hierarchy', $neworg);
                if (!$neworg->id) {
                    throw new Exception('e_org_unit_insert');
                }
            } catch (Exception $e) {
                $transaction->rollback($e);
                print_error($e->getMessage(),'block_lp_hierarchy');
            }

            // Create path for finding ancestors
            $neworg->path = ($neworg->parentid == 0 ? '' : $parent->path) . '/' . ($neworg->id != 0 ? $neworg->id : '');

            $DB->set_field('lp_hierarchy', 'path', $neworg->path, array('id' => $neworg->id));
        }
        else {
            if ($neworg->visible == 0) {
                // Check that there are no active users before making this invisible
                $active_user_count = $hierarchy->get_active_user_count($neworg->id);

                if ($active_user_count > 0) {
                    // There are still active users
                    // Return to the landing page
                    $transaction->allow_commit();
                    redirect("{$CFG->wwwroot}/blocks/lp_hierarchy/org_unit/edit.php?id={$neworg->id}&c=$active_user_count");
                }
            }

            if (!property_exists($neworg, 'sortorder')) {
                $neworg->sortorder = $org->sortorder;
            }

            if ($neworg->sortorder != $org->sortorder) {
                // Changing the sort order involves a bit of work in to preserve
                // the ordering of the existing hierarchy
                // Get the parent's decendants
                // Specifically we are looking for the sort order of the last item, to know
                // where the item moved should go
                $parent_decendants = $hierarchy->get_item_descendants($neworg->parentid);

                if (count($parent_decendants) > 0) {
                    end($parent_decendants);
                    $last_sort_order = current($parent_decendants)->sortorder;
                }
                else {
                    // If there are no dependants, use the sort order of the parent itself
                    $parent = $DB->get_record('lp_hierarchy', array ('id' => $neworg->parentid));

                    $last_sort_order = $parent->sortorder;
                }

                $decendants = $hierarchy->get_item_descendants($org->id);
                $decendant_items = array();
                $decendant_items_string = '';

                if ($decendants) {
                    foreach ($decendants as $decendant) {
                        $decendant_items = $decendant->id;
                    }

                    end($decendants);

                    if (count($decendant_items) == 1) {
                        $decendant_items_string = $decendant_items;
                    }
                    else {
                        $decendant_items_string = explode(",", $decendant_items);
                    }

                    // Work out how many items are moving in total
                    $move_count = 1 + count($decendants);

                    end($decendants);
                    $last_decendant_sort_order = current($decendants)->sortorder;
                }

                if ($last_sort_order > $org->sortorder) {
                    // The item is moving down the list
                    $neworg->sortorder = $last_sort_order + 1;

                    // The sort order requires updating
                    if ($decendants) {
                        $newpath = str_replace("/{$org->parentid}/", "/{$neworg->parentid}/", $org->path);
                        $offset = 1 + count($decendants);
                        $endsortorder = $last_sort_order + count($decendants);
                        // Re-evaluate the 'path' for decendants
                        $sql = "UPDATE {$CFG->prefix}lp_hierarchy
                            SET path = REPLACE(path, '{$org->path}', '$newpath')
                            WHERE id IN ($decendant_items_string)";

                        $DB->execute($sql);
                    }
                    else {
                        // There are no decendants so only this item is moving
                        $offset = 1;
                        $endsortorder = $neworg->sortorder;
                    }

                    // Evaluate the sort order for the existing items
                    $sql2 = "UPDATE {$CFG->prefix}lp_hierarchy
                                SET sortorder = sortorder - $offset
                                WHERE (sortorder > $org->sortorder AND sortorder < $endsortorder) ";

                    if ($decendant_items_string != '') {
                        $sql2 .= " AND id NOT IN ($decendant_items_string)";
                    }

                    $DB->execute($sql2);

                    // Update the sort order for decendants
                    if ($decendants) {
                        $sql3 = "UPDATE {$CFG->prefix}lp_hierarchy
                                SET sortorder = sortorder + $offset
                                WHERE id IN ($decendant_items_string)";

                        $DB->execute($sql3);
                    }

                    $neworg->sortorder = $neworg->sortorder - $offset;
                }
                else if ($last_sort_order < $org->sortorder) {
                    // The item is moving up the list
                    // Set the sort order to one more than the last child of the parent
                    $neworg->sortorder = $last_sort_order + 1;

                    // The sort order requires updating
                    if ($decendants) {
                        $newpath = str_replace("/{$org->parentid}/", "/{$neworg->parentid}/", $org->path);
                        $offset = 1 + count($decendants);
                        $endsortorder = $neworg->sortorder + count($decendants);
                        // Re-evaluate the 'path' for decendants
                        $sql = "UPDATE {$CFG->prefix}lp_hierarchy
                                SET path = REPLACE(path, '{$org->path}', '$newpath')
                                WHERE id IN ($decendant_items_string)";

                        $DB->execute($sql);
                    }
                    else {
                        // There are no decendants so only this item is moving
                        $offset = 1;
                        $endsortorder = $org->sortorder;
                    }

                    // Evaluate the sort order for the existing items
                    $sql2 = "UPDATE {$CFG->prefix}lp_hierarchy
                                SET sortorder = sortorder + $offset
                                WHERE (sortorder >= $neworg->sortorder AND sortorder <= $endsortorder) ";

                    if ($decendant_items_string != '') {
                        $sql2 .= " AND id NOT IN ($decendant_items_string)";
                    }

                    $DB->execute($sql2);

                    // Update the sort order for decendants
                    if ($decendants) {
                        $sql3 = "UPDATE {$CFG->prefix}lp_hierarchy
                                SET sortorder = sortorder - $offset
                                WHERE id IN ($decendant_items_string)";

                        $DB->execute($sql3);
                    }
                }
            }

            $org_children = $hierarchy->get_all_children($neworg->id);

            if ($neworg->visible == 0  && $org_children != '') {
                // Hide decendants of this org unit
                $sql = "UPDATE {$CFG->prefix}lp_hierarchy
                        SET visible = 0
                        WHERE id IN ($org_children)";

                $DB->execute($sql);
            }

            // This is an existing org unit
            try { 
                $DB->update_record('lp_hierarchy', $neworg);
            } catch (Exception $e) {
                $transaction->rollback($e);
                print_error($e->getMessage(),'block_lp_hierarchy');
            }
        }

        // Commit the database transaction
        $transaction->allow_commit();

        // Reload from database
        $neworg = $DB->get_record('lp_hierarchy', array ('id' => $neworg->id));

        // Return to the landing page
        redirect("{$CFG->wwwroot}/blocks/lp_hierarchy/manage_hierarchy.php");
    } catch (Exception $e) {
        if (!$transaction->is_disposed()) {
            $transaction->rollback($e);
        }
        $msg = $e->getMessage();
        if ('Error writing to database' == $msg) {
            $msg = 'e_org_unit_edit_generic';
        }
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string($msg, 'block_lp_hierarchy'), 'notifyfailure');
        die();
    }
}

echo $OUTPUT->header();

$active_user_count = optional_param('c', 0, PARAM_INT);

if ($active_user_count > 0) {
    // This is a re-direct from a failed updated due to the number of active users
    echo $OUTPUT->notification(get_string('error:cannothideorgunit', 'block_lp_hierarchy', $active_user_count), 'notifyfailure');
}

/// Finally display the form
$orgform->display();

echo $OUTPUT->footer();
?>