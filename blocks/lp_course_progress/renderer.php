<?php 

/*
 * Default renderer for lp_course_progress
 * @copyright Learning Pool
 * @author Brian Quinn
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lp_course_progress
 */
class block_lp_course_progress_renderer extends plugin_renderer_base {

	/**
     * A cache of strings
     * @var stdClass
    */
    protected $strings;

    /**
     * Override the constructor so that we can initialise the string cache
     *
     * @param moodle_page $page
     * @param string $target
     */
    public function __construct(moodle_page $page, $target) {
        $this->strings = new stdClass;
        parent::__construct($page, $target);
    }
    
    function block_lp_course_progress_ui() {
        global $PAGE, $USER;

        $display_categories = null;
        $display_courses = null;
        
        $output = '';
        
        if (isset($USER) && isset($USER->username)) {
            if ($USER->username != 'guest') {
                // If this is not the guest user
                $jsconfig = array(
                        'name' => 'block_lp_course_progress',
                        'fullpath' => '/blocks/lp_course_progress/javascript/full_course_progress.js',
                        'strings' => array(
                            array('blocktitle', 'block_lp_course_progress')
                        ),
                        'requires' => array(
                                        'node',
                                        'node-load',
                                        'event',
                                        'selector-css3',
                                        'io-base',
                                        'json-parse',
                                        'event-hover',
                                        'get',
                                        'anim',
                                        'panel'
                                    )
                            );

                $PAGE->requires->js_init_call('M.block_lp_course_progress.init', null, false, $jsconfig);
        
                // Use the next two lines for debugging
                //$output .= html_writer::start_tag('script', array('type' => 'text/javascript', 'src' => '/blocks/lp_course_progress/javascript/full_course_progress.js'));
                //$output .= html_writer::end_tag('script');
                
                // Get the categories and courses
                get_course_progress($USER->id, $display_categories, $display_courses);

                // Render the graphical indicator
                $output .= render_course_progress_meter($display_courses);
        
                if (has_capability('block/lp_course_progress:managenotifications', get_context_instance(CONTEXT_SYSTEM))){
                    $output .= $this->print_notification_link();
                }        
            }
        }
        
        return $output;
    }
    
    /**
     * Renders the course progress information
     * @global type $PAGE
     * @global type $OUTPUT
     * @global type $USER
     * @global type $CFG
     * @param type $show_meter true|false flag to indicate if the graphical progress indicator should be used
     * @param type $show_header true|false flag to indicate if the 'My Course Progress' header should be displayed
     * @return type HTML object
     */
    public function render_course_progress($show_meter = true, $show_header = true, $show_meter_links = true) {
        global $PAGE, $OUTPUT, $USER;
        
        $display_categories = null;
        $display_courses = null;            
                
        // Get progress data for categories and courses
        get_course_progress($USER->id, $display_categories, $display_courses);
                
        $output = ''; 
        
        $output .= html_writer::start_tag('div', array('id' => 'cp-detail'));
       
        // Generate the top filters and header (if applicable)
        $output .= print_course_progress_key($show_meter, $show_header, $display_courses, $show_meter_links);
        
        // Open the report area <div>
        $output .= html_writer::start_tag('div', array('class' => 'cp-report'));       
       
        // Render the user's course progress
        $output .= print_course_progress($USER->id, $display_categories, $display_courses);

        $output .= html_writer::end_tag('div');
        
        // Output the categories
        // Note that this these will be contained in a <select> as unfortunately the JavaScript
        // could not be injected into the calling page via the YUI AJAX call
            /*
        if ($display_categories) {
        
            $output .= html_writer::start_tag('select', array('id' => 'course_categories', 'style' => 'display:none'));
            
            foreach ($display_categories as $category) {
                $key = $category->id;

                $value = $category->name;
                $categories[$key] = $value;                
                
                $output .= html_writer::start_tag('option', array('value' => $key . ',' . $category->sortorder));
                $output .= $value;
                $output .= html_writer::end_tag('option');
            
            }
            $output .= html_writer::end_tag('select');
            // Trying a series of DLs
            $output .= html_writer::start_tag('div', array('class' => 'category-list'));
            
            foreach ($display_categories as $category) {
                $key = $category->id;

                $value = $category->name;
                $categories[$key] = $value;                
                
                $output .= html_writer::start_tag('dl', array('class'=>'category', 'data-id'=>$category->id, 'data-sortorder'=>$category->sortorder));
                $output .= html_writer::start_tag('dt');
                $output .= $value;
                $output .= html_writer::end_tag('dt');
                $output .= html_writer::empty_tag('dd');
                $output .= html_writer::end_tag('dl');
            
            }
            $output .= html_writer::end_tag('div');
        } 
            */

        $output .= html_writer::end_tag('div');
                
        return $output;
    }
    
    private function print_notification_link(){
        global $CFG;
        return html_writer::link(
            $CFG->wwwroot.'/blocks/lp_course_progress/email_notification/manage.php',
            get_string('manageemailnotifications','block_lp_course_progress')
        );
    }
    
    public function manage_notifications(){        
        $output = array();
        
        // heading
        $output []= html_writer::tag('h2', get_string('manageemailheading','block_lp_course_progress'));
        
        // content container
        $output []= html_writer::start_tag('div',array('class'=>'content'));
        $output []= html_writer::tag('div',get_string('manageemailinstruction','block_lp_course_progress'),array('class'=>'summary'));
        $output []= html_writer::tag('div',$this->print_notification_actions(),array('class'=>'buttons'));     
        $output []= html_writer::end_tag('div');
        
        return implode('',$output);
    }
    
    public function print_notification_actions($echo=false){
        global $CFG;
        $output = array();
        
        $output []= html_writer::link(
            $CFG->wwwroot.'/blocks/lp_course_progress/email_notification/edit.php?action=new',
            get_string('setupnewnotification','block_lp_course_progress')
        );
        
        if ($echo){
            echo implode('',$output);
        }
        return implode('',$output);
    }
    
}
?>
