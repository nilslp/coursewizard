<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/lp_charts/lib.php');
require_once($CFG->dirroot.'/blocks/lp_charts/classes/generic_chart.php');

global $CFG, $PAGE, $OUTPUT, $SITE;
    
require_login(NULL, false);

$id = required_param('id', PARAM_INT);
$interval = optional_param('interval', '7 DAY', PARAM_TEXT);
$subscribe = optional_param('subscribe', '', PARAM_TEXT);
$d = optional_param('d', 0, PARAM_INT);
$confirm = optional_param('confirm', false, PARAM_BOOL);
$subscriptionid = optional_param('sid', 0, PARAM_INT);

$notification = '';

$PAGE->set_context(get_context_instance_by_id(1, IGNORE_MISSING));

get_chart($id, $chart_record, $xaxis, $yaxis);

if ($d && $confirm) {
    if (!confirm_sesskey()) {
        $PAGE->set_url(new moodle_url('/blocks/lp_charts/view.php', array('id' => $id, 'd' => '1', 'sid' => $subscriptionid)));
        $PAGE->navbar->add(get_string('blocktitle', 'block_lp_charts'), "$CFG->wwwroot/blocks/lp_charts/index.php");
        $PAGE->navbar->add($chart_record->fullname);
        $PAGE->set_heading($SITE->fullname);
        $PAGE->set_title($SITE->fullname);

        echo $OUTPUT->header();      
    
        echo $OUTPUT->heading(get_string('confirmunsubscribeheader','block_lp_charts'));
        echo $OUTPUT->notification(get_string('error:unknownbuttonclicked', 'block_lp_reportbuilder'), 'notifyfailure');
    
        echo $OUTPUT->footer();
    }
    if (delete_chart_schedule_recipient($subscriptionid)) {
        add_to_log(SITEID, 'lp_charts', 'delete subscription', 'view.php', 'Subscription ID = ' . $subscriptionid);
        
        $notification = "You will no longer receive this email";
    } 
} 
else if ($d) {   
        
    $PAGE->set_url(new moodle_url('/blocks/lp_charts/view.php', array('id' => $id, 'd' => '1', 'sid' => $subscriptionid)));
    $PAGE->navbar->add(get_string('blocktitle', 'block_lp_charts'), "$CFG->wwwroot/blocks/lp_charts/index.php");
    $PAGE->navbar->add($chart_record->fullname);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_title($SITE->fullname);

    echo $OUTPUT->header();      
    
    echo $OUTPUT->heading(get_string('confirmunsubscribeheader','block_lp_charts'));

    $return_url = new moodle_url('/blocks/lp_charts/view.php', array('id' => $id));
    $confirm_url = new moodle_url('/blocks/lp_charts/view.php', array('id' => $id, 'sesskey' => $USER->sesskey, 'd' => '1', 'sid' => $subscriptionid, 'confirm' => 1));
    
    // Get the details of the subscription
    // Retrieve the strings
    $thisweek_string = strtolower(get_string('thisweek', 'block_lp_charts'));
    $lastmonth_string = strtolower(get_string('lastmonth', 'block_lp_charts'));
    $last3months_string = strtolower(get_string('last3months', 'block_lp_charts'));
    $last6months_string = strtolower(get_string('last6months', 'block_lp_charts'));
    $lastyear_string = strtolower(get_string('lastyear', 'block_lp_charts'));
    
    $sql = "SELECT cs.id, 
                cs.chartid, 
                cs.schedule_type, 
                cs.chart_interval, 
                c.fullname,
                CASE chart_interval
                    WHEN '7 DAY' THEN CONCAT(c.fullname, ' ($thisweek_string)')
                    WHEN '1 MONTH' THEN CONCAT(c.fullname, ' ($lastmonth_string)')
                    WHEN '3 MONTH' THEN CONCAT(c.fullname, ' ($last3months_string)')
                    WHEN '6 MONTH' THEN CONCAT(c.fullname, ' ($last6months_string)')
                    WHEN '12 MONTH' THEN CONCAT(c.fullname, ' ($lastyear_string)')
                    ELSE IFNULL(cs.chart_interval, c.fullname)
                END AS 'title'
            FROM {lp_chart_schedule} cs
            INNER JOIN {lp_chart} c ON c.id = cs.chartid 
            WHERE cs.id = $subscriptionid";
    
    $result = $DB->get_record_sql($sql);
    
    echo $OUTPUT->confirm(get_string('confirmunsubscribemessage','block_lp_charts', $result), $confirm_url, $return_url);
    
    echo $OUTPUT->footer();

    die;
}
    
if ($subscribe != '') {
    // The user has opted to receive the chart in an email
    $schedule_type = required_param('emailschedule', PARAM_TEXT);
    
    add_chart_schedule($id, $schedule_type, $interval);    
   
    $notification = get_string('subcriptionsaved', 'block_lp_charts');
}

$sql = str_replace('$interval', $interval, $chart_record->sql_query);

// Create the chart object
$chart = new generic_chart($id, $chart_record->chart_type, '/blocks/lp_charts/view.php?id='.$id, $chart_record->fullname, $sql, $xaxis, $yaxis, $chart_record->is_date_based, $interval);

// Display the chart
$chart->run($notification);


