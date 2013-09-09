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

global $SESSION,$USER,$PAGE,$SITE,$CFG,$OUTPUT;
$title = get_string('coursequizquickheading', 'block_lp_reportbuilder');

$PAGE->set_context(build_context_path());
$PAGE->set_url($CFG->wwwroot.'/blocks/lp_reportbuilder/coursequiz.php');

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
            ),
    'strings' => array(
            array('generatingreport', 'block_lp_reportbuilder')
        )
    );

$PAGE->requires->js_init_call('M.block_lp_reportbuilder.init', null, false, $jsconfig);

// Define the page layout and header/breadcrumb
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($SITE->fullname);
$home_url = $CFG->wwwroot;
echo $OUTPUT->header();

$context = get_context_instance(CONTEXT_SYSTEM);
$can_view = has_capability('block/lp_reportbuilder:viewreports', $context);

echo '<div class="quick" id="quick-quiz-course"><div class="group">';    
echo '<h2>' . get_string('coursequizquickheading', 'block_lp_reportbuilder') . '</h2>';
print_course_quiz_report_form(get_config('reportbuilder','showcoursequizquick'));
echo '</div></div>';    

echo $OUTPUT->footer();

function print_course_quiz_report_form( $format ){
    global $CFG, $OUTPUT, $DB;
    
    $output = array();
    
    $output []= html_writer::start_tag('form',array('id'=>'run_course_quiz','name'=>'run_course_quiz','method'=>'post','action'=> $CFG->wwwroot .'/blocks/lp_reportbuilder/reports/coursequizreport.php'));
    
    $output []= html_writer::start_tag('div',array('id'=>'run_report', 'class'=>'actions'));
//    $output [] = html_writer::empty_tag(
//                    'img',
//                    array(
//                        'src'=>$OUTPUT->pix_url('icon_run','block_lp_reportbuilder'),
//                        'alt'=>get_string('runreport','block_lp_reportbuilder'),
//                        'title'=>get_string('runreporttitle','block_lp_reportbuilder')
//                    )
//                    );
    
//            array(
//                'onclick' => "javascript:validateCourseQuiz();"
//            )
    $output []= html_writer::link(
           '',// 'javascript:void(0);',
            html_writer::empty_tag(
                    'img',
                    array(
                        'src'=>$OUTPUT->pix_url('icon_run','block_lp_reportbuilder'),
                        'alt'=>get_string('runreport','block_lp_reportbuilder'),
                        'title'=>get_string('runreporttitle','block_lp_reportbuilder')
                    )
                    ).get_string('runreport','block_lp_reportbuilder')
//            array(
//                'onclick' => "javascript:validateCourseQuiz();"
//            )
        );
    $output []= html_writer::end_tag('div');
    
    $output []= html_writer::start_tag('p');
    
    $output []= get_string('choosedaterange','block_lp_reportbuilder');
    $output []= html_writer::label(get_string('datefromlabel','block_lp_reportbuilder'), 'datefrom');
    $output []= html_writer::empty_tag('input',array('id'=>'datefrom','name'=>'datefrom','class'=>'date-picker','readonly'=>'readonly'));
    
    $output []= html_writer::label(get_string('datetolabel','block_lp_reportbuilder'), 'dateto');
    $output []= html_writer::empty_tag('input',array('id'=>'dateto','name'=>'dateto','class'=>'date-picker','readonly'=>'readonly'));   
    $output []= html_writer::empty_tag('input',array('id'=>'cleardate','type'=>'button','value'=>'Clear Dates'));     
    $output []= html_writer::end_tag('p');
    
    $output []= get_string('quizcourseinst','block_lp_reportbuilder');
    
    
    
    // get course records
    $records = $DB->get_records('course',null,'fullname ASC','id,fullname');
    $courses = array();
    foreach ($records as $rec){
        $courses[$rec->id] = $rec->fullname;
    }
    
    $output []= html_writer::start_tag('fieldset');
    $output []= html_writer::tag('h3',get_string('selectquizcourseheading','block_lp_reportbuilder'));
    $output []= html_writer::select($courses,'course_select','',false,array('id'=>'course_from','size'=>'15','multiple'=>'multiple','class'=>'moveselect filter-list'));
    $output []= html_writer::start_tag('div',array('class'=>'controls'));
    $output []= html_writer::empty_tag('input',array('type'=>'button','value'=>get_string('add').' >>','class'=>'addcourse'));
    $output []= html_writer::empty_tag('br');  
    $output []= html_writer::empty_tag('input',array('type'=>'button','value'=>get_string('remove').' <<','class'=>'removecourse'));     
    $output []= html_writer::end_tag('div');     
    $output []= html_writer::select(array(),'course_to','',false,array('id'=>'course_to','size'=>'15','multiple'=>'multiple','class'=>'moveselect filter-list')); 
    $output []= html_writer::end_tag('fieldset');
    
    $output []= html_writer::start_tag('fieldset');
    $output []= html_writer::empty_tag('input',array('type'=>'hidden','id'=>'courses','name'=>'courses','value'=>''));
    $output []= html_writer::empty_tag('input',array('type'=>'hidden','id'=>'fields','name'=>'fields','value'=>get_config('reportbuilder','extrareportfields')));
    $output []= html_writer::empty_tag('input',array('type'=>'hidden','id'=>'complete','name'=>'complete','value'=>$format));
    $output []= html_writer::empty_tag('input',array('type'=>'hidden','id'=>'tab','name'=>'tab','value'=>intval(get_config('reportbuilder','coursequizquickformat'))));
    $output []= html_writer::end_tag('fieldset');
    $output []= html_writer::tag('div',html_writer::tag('div','',array('id'=>'cal')),array('id'=>'calendar'));
    
    
    $output []= html_writer::end_tag('form');
    
    echo implode('',$output);
}

