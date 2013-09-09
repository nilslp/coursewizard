<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Adds this plugin to the admin menu.
 *
 * @package    local
 * @subpackage lp_courseaccessmanager
 * @copyright  2012 Learning Pool
 * @author     Dennis Heaney
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $PAGE;

$ADMIN->add('courses',
    new admin_category('local_lp_courseaccessmanager',
    get_string('categoryname','local_lp_courseaccessmanager'))
);

$ADMIN->add('local_lp_courseaccessmanager', new admin_externalpage('lp_courseaccessmanager',
        get_string('courseaccessmanager', 'local_lp_courseaccessmanager'),
        new moodle_url('/local/lp_courseaccessmanager/index.php'),
        array('local/lp_courseaccessmanager:manageaccess')));

$ADMIN->add('local_lp_courseaccessmanager',
    new admin_externalpage('globalcourseaccessmanagersettings',
        get_string('globalsettings','local_lp_courseaccessmanager'),
        "$CFG->wwwroot/local/lp_courseaccessmanager/globalsettings.php",
        array('local/lp_courseaccessmanager:manageaccess')
    )
);


if ($ADMIN->fulltree) {
    $settings = new admin_settingpage('local_lp_courseaccessmanager_settings', 'Course Access Manager Settings');
    $options = array(
        'hide'=>get_string('owncourses', 'local_lp_courseaccessmanager'),
        'show'=>get_string('allcourses', 'local_lp_courseaccessmanager')
    );

    $settings->add(
            new admin_setting_configselect(
                    'local_lp_courseaccessmanager/defaultview',
                    get_string('defaultview', 'local_lp_courseaccessmanager'),
                    get_string('configdefaultview', 'local_lp_courseaccessmanager'),
                    'show',
                    $options
            )
    );
}
