<?php
/*
 * Settings page for block_lp_login
 * @copyright Learning Pool 2012
 * @author Dennis Heaney
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package block_lp_login
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {         
    $settings->add(new admin_setting_configcheckbox('block_lp_login/showforgottenpasswordlink', get_string('setting:showforgottenpasswordlink_caption', 'block_lp_login'), get_string('setting:showforgottenpasswordlink', 'block_lp_login'), 0));
}


