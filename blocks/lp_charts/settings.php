<?php
/*
 * Settings page for lp_charts
 * @copyright Learning Pool 2012
 * @author Brian Quinn
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lp_charts
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // Set the choices
    $time_array = array(
        0 =>'00:00',
        1 =>'01:00',
        2 =>'02:00',
        3 =>'03:00',
        4 =>'04:00',
        5 =>'05:00',
        6 =>'06:00',
        7 =>'07:00',
        8 =>'08:00',
        9 =>'09:00',
        10 =>'10:00',
        11 =>'11:00',
        12 =>'12:00',
        13 =>'13:00',
        14 =>'14:00',
        15 =>'15:00',
        16 =>'16:00',
        17 =>'17:00',
        18 =>'18:00',
        19 =>'19:00',
        20 =>'20:00',
        21 =>'21:00',
        22 =>'22:00',
        23 =>'23:00',
    );
    
    // ISO-8601 numeric representation of the day of the week 
    $days_array = array(
        1 => get_string('monday', 'calendar'),
        2 => get_string('tuesday', 'calendar'),
        3 => get_string('wednesday', 'calendar'),
        4 => get_string('thursday', 'calendar'),
        5 => get_string('friday', 'calendar'),
        6 => get_string('saturday', 'calendar'),
        7 => get_string('sunday', 'calendar')
    );
        
    // A setting to hold the default date format for charts
    // Valid values should be as defined at http://php.net/manual/en/function.date.php
    $settings->add(new admin_setting_configtext('block_lp_charts/dateformat', get_string('dateformat', 'block_lp_charts'), get_string('dateformat_def', 'block_lp_charts'), 'j-M-y', PARAM_TEXT, 10));

    // Time for daily reports
    $settings->add(new admin_setting_configselect('block_lp_charts/deliverytime', get_string('deliverytime', 'block_lp_charts'), get_string('deliverytimedescription', 'block_lp_charts'), 7, $time_array));
    
    // Day of the week for weekly reports
    $settings->add(new admin_setting_configselect('block_lp_charts/deliveryday', get_string('deliveryday', 'block_lp_charts'), get_string('deliverydaydescription', 'block_lp_charts'), 1, $days_array));

    $settings->add(new admin_setting_configcheckbox('block_lp_charts/nohttps', get_string('nohttps', 'block_lp_charts'), get_string('nohttpsdescription', 'block_lp_charts'), 0));
}


