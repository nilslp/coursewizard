<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/lp_hierarchy/lib.php');
require_once($CFG->dirroot . "/lib/outputcomponents.php");

global $USER;

require_login();

if ($USER->username == 'guest') {
    // Prevent Guest users from seeing this page
    print_error('accessdenied', 'admin');
    die;
}

// Editing param
$edit = optional_param('edit', -1, PARAM_BOOL);

if ($edit === -1) {
    // If the user has navigated across pages
    // persist the editing
    if (isset($USER->hierarchyediting)) {
        if ($USER->hierarchyediting == 1) {
            $edit = 1;
        } else {
            $edit = 0;
        }
    } else {
        $edit = 0;
    }
}

$PAGE->set_context(build_context_path());
$PAGE->set_url($CFG->wwwroot . '/blocks/lp_hierarchy/manage_hierarchy.php');

$can_edit = has_capability('block/lp_hierarchy:manage', $PAGE->context);

// Define the page layout and header/breadcrumb
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('manage_hierarchy', 'block_lp_hierarchy'));
$PAGE->set_heading(get_string('manage_hierarchy', 'block_lp_hierarchy'));

$jsconfig = array(
    'name' => 'block_lp_hierarchy',
    'fullpath' => '/blocks/lp_hierarchy/javascript/behaviors.lp-tree.js',
    'requires' => array(
        'node',
        'event',
        'selector-css3',
        'event-hover'
    )
);

$PAGE->requires->js_init_call('M.block_lp_hierarchy.init', null, false, $jsconfig);

$settings_url = new moodle_url('/admin/settings.php?section=blocksettinglp_hierarchy');
$manage_hierarchy = new moodle_url('/blocks/lp_hierarchy/manage_hierarchy.php');
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_lp_hierarchy'), $settings_url);
$PAGE->navbar->add(get_string('manage_hierarchy', 'block_lp_hierarchy'), $manage_hierarchy);
echo $OUTPUT->header();

$hierarchy = Hierarchy::get_instance();

if ($can_edit) {
    echo '<div class="navbutton">';
    echo $hierarchy->get_editing_button($edit);
    echo '</div>';
}

// Retrieve the depths that have been defined
$depths = $hierarchy->get_depths();

if (!$depths) {
    // No depths have been set up yet
    // At least one depth must be setup before the hierarchy can be managed
    echo '<div>' . get_string('no_depth_defined', 'block_lp_hierarchy') . '</div>';

    if ($USER->hierarchyediting === 1 && $can_edit) {
        echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/lp_hierarchy/depth/edit.php', get_string('add_depth_button', 'block_lp_hierarchy'), 'GET');
    }

    echo $OUTPUT->footer();
    exit();
}

echo '<h2 class="main">Manage Hierarchy</h2>';
echo '<div class="group yui3-u-1-3">';
echo '<h2>' . get_string('depth_summary_title', 'block_lp_hierarchy') . '</h2>';

// Start displaying the org depths
echo '<ul>';
foreach ($depths as $depth) {
    echo '<li>';
    if (isset($USER->hierarchyediting) && $USER->hierarchyediting === 1) {
        // Add a link to edit
        echo sprintf("<a href=\"{$CFG->wwwroot}/blocks/lp_hierarchy/depth/edit.php?id=%s\">%s</a>", $depth->id, htmlentities($depth->fullname));
    } else {
        echo htmlentities($depth->fullname);
    }

    if ($CFG->block_lp_hierarchy_allow_freetext && $depth->level == $CFG->block_lp_hierarchy_depth) {
        echo "*";
    }

    echo '</li>';
}
// End displaying the org depths (close the <ul> tag)
echo '</ul>';

if ($CFG->block_lp_hierarchy_allow_freetext) {
    echo '<div>*&nbsp;' . get_string('freetext_enabled', 'block_lp_hierarchy') . '</div>';
}

if ($CFG->block_lp_hierarchy_depth
        && ($CFG->block_lp_hierarchy_depth >= $hierarchy->get_next_depth_level())
        && (isset($USER->hierarchyediting) && $USER->hierarchyediting === 1)
        && $can_edit) {
    echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/lp_hierarchy/depth/edit.php', get_string('add_depth_button', 'block_lp_hierarchy'), 'GET', $options);
}

echo "</div>";

echo '<div class="group yui3-u-2-3">';

// Retrieve the organisation hierarchy
$organisations = $hierarchy->get_hierarchy();

// Initialise the level to zero
$level = 0;

if ($depths) {
    echo '<h2>' . get_string('hierarchy_title', 'block_lp_hierarchy') . '</h2>';

    if (!$organisations) {
        echo '<p>' . get_string('no_org_units', 'block_lp_hierarchy') . '</p>';
    }

    if ($edit === 1 && $can_edit) {
        echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/lp_hierarchy/org_unit/edit.php', get_string('add_org_unit_button', 'block_lp_hierarchy'), 'GET');
        echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/lp_hierarchy/manage_hierarchy_users.php', get_string('manage_users_button', 'block_lp_hierarchy'), 'GET');
    }
}

// Build up the unordered list structure used to display the hierarchy
if ($organisations) {
    echo "\n<ul class=\"lp-tree\">";
}

$edit_string = get_string('edittooltip', 'block_lp_hierarchy');
$delete_string = get_string('deletetooltip', 'block_lp_hierarchy');

// Iterate over the data and build up the list
foreach ($organisations as $organisation) {
    if ($organisation->level > $level && $level != 0) {
        echo "\n<ul class=\"sub1\">";
    } else if ($organisation->level == $level) {
        echo "\n</li>";
    } else {
        if ($level != 0) {
            $count = (int) $level - (int) $organisation->level;

            // Close the list item tag
            echo "\n</li>";

            // Generate the closing list tags
            for ($i = 1; $i <= $count; $i++) {
                echo "\n</ul>";
            }
        }
    }

    echo "\n<li>";

    if (isset($USER->hierarchyediting) && $USER->hierarchyediting === 1) {
        // Add a link to edit

        if ($organisation->visible) {
            echo sprintf("<a href=\"{$CFG->wwwroot}/blocks/lp_hierarchy/org_unit/edit.php?id=%s\">%s</a>", $organisation->id, $organisation->fullname);
        } else {
            echo sprintf("<a class=\"dimmed_text\" href=\"{$CFG->wwwroot}/blocks/lp_hierarchy/org_unit/edit.php?id=%s\">%s</a>", $organisation->id, $organisation->fullname);
        }

        if ($CFG->block_lp_hierarchy_allow_freetext == 0 && $organisation->level == $CFG->block_lp_hierarchy_depth ||
                ($CFG->block_lp_hierarchy_allow_freetext == 1 && $CFG->block_lp_hierarchy_allow_freetext == ($organisation->level - 1))) {
            echo sprintf("&nbsp;(%d, %d)", $organisation->count, $organisation->deleted_count);
        }

        echo sprintf("&nbsp;<a href=\"{$CFG->wwwroot}/blocks/lp_hierarchy/org_unit/edit.php?id=%s\">", $organisation->id);
        echo "<img class=\"iconsmall\" title=\"$edit_string\" alt=\"$edit_string\" src=\"$CFG->wwwroot/theme/image.php?theme=standard&image=t%2Fedit&rev=189\">";
        echo "</a>";

        echo sprintf("&nbsp;<a href=\"{$CFG->wwwroot}/blocks/lp_hierarchy/org_unit/delete.php?id=%s\">", $organisation->id);
        echo "<img class=\"iconsmall\" title=\"$delete_string\" alt=\"$delete_string\" src=\"$CFG->wwwroot/theme/image.php?theme=standard&image=t%2Fdelete&rev=189\">";
        echo "</a>";
    } else {
        if ($organisation->visible == 0) {
            echo '<span class="dimmed_text">' . $organisation->fullname . '</span>';
        } else {
            echo $organisation->fullname;
        }

        if ($CFG->block_lp_hierarchy_allow_freetext == 0 && $organisation->level == $CFG->block_lp_hierarchy_depth ||
                ($CFG->block_lp_hierarchy_allow_freetext == 1 && $CFG->block_lp_hierarchy_allow_freetext == ($organisation->level - 1))) {
            echo sprintf("&nbsp;(%d, %d)", $organisation->count, $organisation->deleted_count);
        }
    }

    // Maintain the current depth level
    $level = $organisation->level;
}

if ($organisations) {
    echo "\n</li>";
    echo "\n</ul>";

    // Tidy up any closing <ul> tags
    for ($i = 1; $i <= $level; $i++) {
        echo "\n</ul>";
    }
}

echo "</div>";
echo $OUTPUT->footer();
