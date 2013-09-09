<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . "/blocks/lp_reportbuilder/lib.php");
require_once($CFG->dirroot . "/local/learningpool/lib/tablelib.php");
    
require_login();

define('DEFAULT_PAGE_SIZE', 20);
define('SHOW_ALL_PAGE_SIZE', 5000);

global $SESSION,$USER;
$strheading = get_string('myreports', 'block_lp_reportbuilder');

$PAGE->set_context(build_context_path());
$PAGE->set_url($CFG->wwwroot.'/blocks/lp_reportbuilder/myreports.php');

// js 
$jsconfig = array(
'name' => 'block_lp_reportbuilder',
'fullpath' => '/blocks/lp_reportbuilder/js/reports.behaviours.js',
'requires' => array(
                'node',
                'event',
                'selector-css3',
                'event-hover',
                'datatable',
                'panel',
                'dd-plugin',
                'yui2-calendar'
            )
    );

$PAGE->requires->js_init_call('M.block_lp_reportbuilder.init', null, false, $jsconfig);

// Define the page layout and header/breadcrumb
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('myreports','block_lp_reportbuilder'));
$PAGE->set_heading($SITE->fullname);
$home_url = $CFG->wwwroot;
echo $OUTPUT->header();

add_to_log(SITEID, 'my', 'reports', 'myreports.php');

$context = get_context_instance(CONTEXT_SYSTEM);

$can_view = has_capability('block/lp_reportbuilder:viewreports', $context);


// @TODO refactor this stuff to user a renderer instance DPMH

echo '<div class="group berocca">';
// echo '<h2>' . get_string('myreports','block_lp_reportbuilder') . ' ' . $OUTPUT->help_icon('help_myreports', 'block_lp_reportbuilder', true) . '</h2>';
echo '<h2>' . get_string('myreports','block_lp_reportbuilder') . '</h2>';

if ($can_view) {
    print_report_manager();
}
else {
    echo html_writer::tag('div', get_string('nocapability', 'block_lp_reportbuilder'));
}
echo '</div>';

// Scheduled reports are moving
//if ($can_view && reportbuilder_get_reports()){
//    echo '<div class="scheduled"><div class="group">';
//    echo '<h2>' . get_string('scheduledreports', 'block_lp_reportbuilder') . '</h2>';
//    print_scheduled_reports();
//    echo '</div></div>';
//}


echo $OUTPUT->footer();

function print_report_manager($return=false) {
    global $CFG, $DB, $USER, $OUTPUT;
    require_once($CFG->dirroot.'/blocks/lp_reportbuilder/lib.php');
    $reports = $DB->get_records('report_builder', null, 'fullname');
    
    if (!is_array($reports)){
        $reports = array();
    }
    $context = get_context_instance(CONTEXT_SYSTEM);

    $rows = array();
    $counter = 0;

    foreach ($reports as $report) {
        // show reports user has permission to view, that are not hidden
        if (reportbuilder::is_capable($report->id) && !$report->hidden) {
            $viewurl = reportbuilder_get_report_url($report);
            $class = ($counter % 2) ? 'r0' : 'r1';
            $counter++;
            $row = '
            <tr class="'.$class.'">
           
                <td class="text" align="left">
                    <span><a href="'.$viewurl.'">'.$report->fullname.'</a>
                ';

/* TODO
            // if admin with edit mode on show settings button too
            if(has_capability('moodle/local:admin',$context) && isset($USER->editing) && $USER->editing) {
                $row .= '<a href="'.$CFG->wwwroot.'/local/reportbuilder/general.php?id='.$report->id.'">'.
                    '<img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('settings','local').'"></a>';
            }
 */
            $row .= '</span>
                </td>
                
				<td class="icon lastcol" align="left">			
                    <a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/general.php?id='.$report->id.'" title="'.$report->fullname.'">
                    <img src="'.$OUTPUT->pix_url('t/edit', 'core').'" /></a>
                </td>
            </tr>
            ';
            $rows[] = $row;
        }
    }

    // if there are any rows print them
    $returnstr = '';
    if (count($rows) > 0) {
        $returnstr = '<table class="reportmanager generaltable berocca"><thead><tr><th>Name</th><th class="lastcol">Edit</th></tr></thead>';
        $returnstr .= '<tbody>';
        $returnstr .= implode("\n",$rows);
        $returnstr .= '</tbody>';
        $returnstr .= '</table>';
    } 
    else {
        $returnstr = get_string('nouserreports', 'block_lp_reportbuilder');
    }

    if ($return) {
        return $returnstr;
    }
    
    echo $returnstr;
}

function print_scheduled_reports($return=false) {
    global $CFG, $DB, $USER, $REPORT_BUILDER_EXPORT_OPTIONS, $REPORT_BUILDER_SCHEDULE_OPTIONS, $CALENDARDAYS,$OUTPUT;
    $REPORT_BUILDER_SCHEDULE_CODES = array_flip($REPORT_BUILDER_SCHEDULE_OPTIONS);

    require_once($CFG->dirroot.'/blocks/lp_reportbuilder/lib.php');
    require_once($CFG->dirroot.'/calendar/lib.php');
    require_once($CFG->dirroot.'/blocks/lp_reportbuilder/scheduled_forms.php');
	
    $CALENDARDAYS = calendar_get_days();
	//echo html_writer::link($CFG->wwwroot.'/blocks/lp_reportbuilder/scheduled.php','+ Add Scheduled report',array('style'=>'text-align:right;'));
	
    $mform = new scheduled_reports_add_form($CFG->wwwroot . '/blocks/lp_reportbuilder/scheduled.php', array());
    $mform->display();

    $sql = "SELECT rbs.*, rb.fullname
            FROM {$CFG->prefix}report_builder_schedule rbs
            JOIN {$CFG->prefix}report_builder rb
            ON rbs.reportid = rb.id
            WHERE rbs.userid = {$USER->id}";

    if ($scheduledreports = $DB->get_records_sql($sql)){
        $columns[] = 'reportname';
        $headers[] = get_string('reportname', 'block_lp_reportbuilder');
        $columns[] = 'data';
        $headers[] = get_string('savedsearch', 'block_lp_reportbuilder');
        $columns[] = 'format';
        $headers[] = get_string('format', 'block_lp_reportbuilder');
        $columns[] = 'schedule';
        $headers[] = get_string('schedule', 'block_lp_reportbuilder');

        $columns[] = 'options';
        $headers[] = 'Options';

        $shortname = 'scheduled_reports';
        $table = new lp_flexible_table($shortname);
        $table->define_columns($columns);
        $table->define_headers($headers);
        $table->set_attribute('class', 'scheduled-reports generalbox generaltable reportmanager berocca');
        $table->column_class('options', 'options');
        $table->baseurl = "$CFG->wwwroot/blocks/lp_reportbuilder/myreports.php";
        $table->setup();
        $dateformat = ($USER->lang == 'en_utf8') ? 'jS' : 'j';

        foreach ($scheduledreports as $sched) {
            if (isset($sched->frequency) && isset($sched->schedule)){
                $schedule = '';

                switch ($REPORT_BUILDER_SCHEDULE_CODES[$sched->frequency]){
                case 'daily':
                    $schedule .= get_string('daily', 'block_lp_reportbuilder') . ' ' .  get_string('at', 'block_lp_reportbuilder') . ' ';
                    $schedule .= strftime('%l:%M%P' ,mktime($sched->schedule,0,0));
                    break;
                case 'weekly':
                    $schedule .= get_string('weekly', 'block_lp_reportbuilder') . ' ' . get_string('on', 'block_lp_reportbuilder') . ' ';                    
                    $schedule .= get_string($CALENDARDAYS[$sched->schedule], 'calendar');
                    break;
                case 'monthly':
                    $schedule .= get_string('monthly', 'block_lp_reportbuilder') . ' ' . get_string('onthe', 'block_lp_reportbuilder') . ' ';
                    $schedule .= date($dateformat ,mktime(0,0,0,0,$sched->schedule));
                    break;
                }
            }
            else {
                $schedule = get_string('schedulenotset', 'block_lp_reportbuilder');
            }

            foreach ($REPORT_BUILDER_EXPORT_OPTIONS as $option => $code) {
                // bitwise operator to see if option bit is set
                if ($sched->format == $code) {
                    $format = get_string($option . 'format','block_lp_reportbuilder');
                }
            }

            $data = '';
            if ($sched->savedsearchid != 0) {
                $data .= $DB->get_field('report_builder_saved', 'name', array('id' => $sched->savedsearchid));
            }
            else {
                $data .= get_string('alldata', 'block_lp_reportbuilder');
            }

            $tablerow = array();
            $tablerow[] = $sched->fullname;
            $tablerow[] = $data;
            $tablerow[] = $format;
            $tablerow[] = $schedule;

            /*
            $tablerow[] = '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/scheduled.php?id='.$sched->id .'" title="'.get_string('edit').
                '"><img src="'.$OUTPUT->pix_url('t/edit', 'core').'" class="" alt="'.get_string('edit').'" width="" height=""/></a>'. ' ' .
                '<a href="'.$CFG->wwwroot.'/blocks/lp_reportbuilder/deletescheduled.php?id='.$sched->id.'" title="'.get_string('delete').
                '"><img src="'.$OUTPUT->pix_url('t/delete', 'core').'" class="" alt="'.get_string('delete').'" width="" height="" /></a>';
            */
            
            $sched_opts = '';
            $icon_edit = html_writer::tag('img','',array('src'=>$OUTPUT->pix_url('t/edit', 'core'), 'alt'=>get_string('edit')));
            $sched_opts.= html_writer::link($CFG->wwwroot.'/blocks/lp_reportbuilder/scheduled.php?id='.$sched->id,$icon_edit,array('title'=>get_string('edit')));
            $icon_delete = html_writer::tag('img','',array('src'=>$OUTPUT->pix_url('t/delete', 'core'), 'alt'=>get_string('delete')));
            $sched_opts.= html_writer::link($CFG->wwwroot.'/blocks/lp_reportbuilder/deletescheduled.php?id='.$sched->id,$icon_delete,array('title'=>get_string('delete')));
            $tablerow[] = $sched_opts;
            
            $table->add_data($tablerow);
        }

        $table->print_html();
    }
    else {
        echo get_string('noscheduledreports', 'block_lp_reportbuilder') . '<br /><br />';
	}

}
