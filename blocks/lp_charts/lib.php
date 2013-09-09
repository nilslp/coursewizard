<?php

require_once($CFG->dirroot.'/lib/setuplib.php');
require_once($CFG->dirroot.'/blocks/lp_charts/classes/generic_chart.php');

define('GOOGLE_JS_API_URL', 'https://www.google.com/jsapi');
define('GOOGLE_JS_API_URL_UNSECURE', 'http://www.google.com/jsapi');
define('LP_CHART_DATE_FORMAT', 'j-M-y');
define('LP_CHART_WORKING_DIR', 'lp_charts');

function get_chart($id, &$chart, &$x_axis, &$y_axis) {
    global $DB;
    
    // Get the chart
    $chart = $DB->get_record('lp_chart', array('id' => $id));
    
    // Get the columns
    $x_axis = $DB->get_record('lp_chart_columns', array('chartid' => $id, 'axis' => 'x'));
    $y_axis = $DB->get_record('lp_chart_columns', array('chartid' => $id, 'axis' => 'y'));
    
}

function get_charts_html() {
    global $CFG, $DB;
    
    $output = '';
    $charts = $DB->get_records('lp_chart', array('hidden' => '0'));
    
    if ($charts) {
        $output .= html_writer::start_tag('table');
        
        foreach ($charts as $chart) {
            $output .= html_writer::start_tag('tr');
            $output .= html_writer::start_tag('td');
            $output .= html_writer::start_tag('a', array('href' => $CFG->wwwroot . '/blocks/lp_charts/view.php?id=' . $chart->id));
            $output .= html_writer::tag('img', '', array('src' => $CFG->wwwroot . '/blocks/lp_charts/images/reports.png'));
            $output .= html_writer::end_tag('a');
            $output .= html_writer::end_tag('td');
            $output .= html_writer::start_tag('td');
            $output .= html_writer::start_tag('a', array('href' => $CFG->wwwroot . '/blocks/lp_charts/view.php?id=' . $chart->id));
            $output .= $chart->fullname;
            $output .= html_writer::end_tag('a');
            $output .= html_writer::end_tag('td');
            $output .= html_writer::end_tag('tr');
        }
        
        $output .= html_writer::end_tag('table');
    }
    else {
        $output .= html_writer::start_tag('tr');
        $output .= html_writer::start_tag('td');
        $output .= get_string('nochartsfound', 'block_lp_charts');
        $output .= html_writer::end_tag('td');
        $output .= html_writer::end_tag('tr');
    }
    
    return $output;
}

function add_chart_schedule($id, $schedule_type, $interval = null) {
    global $DB, $USER;
    
    $schedule = $DB->get_record('lp_chart_schedule', 
                                    array('chartid' => $id, 
                                        'schedule_type' => $schedule_type, 
                                        'chart_interval' => $interval));
    
    if (!$schedule) {
        $new_schedule = new object();
        $new_schedule->chartid = $id;
        $new_schedule->schedule_type = $schedule_type;

        if ($interval == '') {
            $new_schedule->chart_interval = null;
        }
        else {
            $new_schedule->chart_interval = $interval;
        }

        $scheduleid = $DB->insert_record('lp_chart_schedule', $new_schedule); 
    }
    else {
        $scheduleid = $schedule->id;
    }
        
    // Check to see that the user has not already subscribed to this chart for this schedule/interval
    $subscription = $DB->get_record('lp_chart_schedule_recipient', array('chartscheduleid' => $scheduleid, 'userid' => $USER->id));
    
    if (!$subscription) {
        // Only add the subscription if it doesn't already exist
        $schedule_recipient = new object();
        $schedule_recipient->chartscheduleid = $scheduleid;
        $schedule_recipient->userid = $USER->id;

        $DB->insert_record('lp_chart_schedule_recipient', $schedule_recipient);
    }
}

/**
 * Unsubscribes a user from receiving a specific chart via email
 * @global moodle_database $DB
 * @param type $id Unique identifier for lp_chart_schedule_recipient
 */
function delete_chart_schedule_recipient($id) {
    global $DB;
    
    return $DB->delete_records('lp_chart_schedule_recipient', array('chartscheduleid' => $id));
}
