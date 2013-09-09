<?php
require_once('../../../config.php');
require_once($CFG->dirroot.'/blocks/lp_reportbuilder/lib.php');
require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');


/**
 * This class generates the UI for embedded reports in a standardised form
 *
 * @author Brian Quinn
 */
class base_report_embedded {
    private $url;
    private $shortname;
    private $format = '';
    private $extraheadingtext = '';
    private $querystring = '';
    
    /**
     * Constructor
     * @param type $short_name The unique short name of the embedded report
     * @param type $report_url The path/relative URL to the report script, e.g. /blocks/lp_reportbuilder/reports/cours_search.php 
     */
    public function __construct($short_name, $report_url, $extra_heading_text = '') {
        global $USER, $PAGE, $CFG;
        
        if ($short_name == '' || $report_url == '') {
            die('$short_name and $report_url parameters are required');
        }
    
        // Permissions check
        if (!has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM)) 
                && !has_capability('block/lp_reportbuilder:viewreports', get_context_instance(CONTEXT_SYSTEM))) {
            die('Access denied -- block/lp_reportbuilder:viewreports capability is required to view reports');
        }
        
        // Set the properties
        $this->shortname = $short_name;
        $this->url = $report_url;
        $this->format = optional_param('format', '', PARAM_TEXT); // export format
        $this->extraheadingtext = $extra_heading_text;
        $this->querystring = (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');
        
        $referrer = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
        
        $PAGE->set_url($CFG->wwwroot . $this->url);
        $PAGE->set_pagelayout('report');
        
        $filter = optional_param('addfilter', '', PARAM_TEXT);
        $document_request = isset($_REQUEST['format']);
        if($filter == '' && !$document_request )
        {
            //remove filters
            if(array_key_exists('filtering_'.$this->shortname, $_SESSION['SESSION'])){
                $filtername = 'filtering_'.$this->shortname;
                unset($_SESSION['SESSION']->$filtername);
            }
        }
        if (!strpos($referrer, $report_url)) {
            // Clear any custom session data here
            switch($short_name) {
                case 'course_search':
                    if (key_exists('hierarchy_where_clause', $_SESSION)) {
                        unset($_SESSION['hierarchy_where_clause']);
                    }
                    
                    if (key_exists('course_search_user-deleted', $_SESSION)) {
                        unset($_SESSION['course_search_user-deleted']);
                    }
                    break;
            }
        }
    }
    
    /**
     * Generates the UI for the report
     * @global type $PAGE
     * @global type $OUTPUT 
     */
    public function run() {        
        global $CFG, $PAGE, $OUTPUT, $SITE, $SESSION;
        
        require_login();
		
        $PAGE->set_context(build_context_path());
		
        if (!$report = reportbuilder_get_embedded_report($this->shortname)) {
            print_error('error:couldnotgenerateembeddedreport', 'block_lp_reportbuilder');
        }

        if ($this->format != '') {
            // Log the export
            add_to_log(SITEID, 'reportbuilder', 'export report', 'report.php?id='. $report->_id,
                $report->fullname);

            // Export the report in the specified format
            $tempfilename = md5(time());
            $tempfilepathname = $CFG->dataroot . '/' . $tempfilename;
            $report->export_data($this->format);
            die;
        }

        // Log the report viewing
        add_to_log(SITEID, 'reportbuilder', 'view report', 'report.php?id='. $report->_id,
            $report->fullname);
        
        $report->include_js();
        $report->include_css();
        
        // Define the page layout and header/breadcrumb
        $PAGE->set_url($CFG->wwwroot . $this->url);
        $PAGE->set_pagelayout('report');
        $PAGE->navbar->add(get_string('myreports','block_lp_reportbuilder'), "$CFG->wwwroot/blocks/lp_reportbuilder/myreports.php");
        $PAGE->navbar->add($report->fullname);
        
        $filter_session_name = $report->_filtering->_sessionname;
        
         if (!empty($SESSION->$filter_session_name)) {
            $filtering = true; 
         }
         else {
            $filtering = false; 
         }
         
        $PAGE->set_heading($SITE->fullname);
        $PAGE->set_title($report->fullname);
        
//        $PAGE->requires->yui2_lib('connection');
//        $PAGE->requires->yui2_lib('datatable');
//        $PAGE->requires->yui2_lib('datasource');
                
        $ajaxconfig = array(
            'name' => 'block_lp_reportbuilder_ajax',
            'fullpath' => '/blocks/lp_reportbuilder/js/reports.ajax.js',       
            'requires' => array(
                'node',
                'event',
                'selector-css3',
                'yui2-datatable', 
                'yui2-datasource',
                'yui2-paginator',
                'io',
                'json-encode',
                'json',
                'panel',
                'yui2-connection'
            ),
            'strings'=> array(
                array('noresultsfound', 'block_lp_reportbuilder'),
                array('loading', 'block_lp_reportbuilder'),
                array('totalrecords', 'block_lp_reportbuilder'),
                array('xrecord', 'block_lp_reportbuilder'),
                array('xrecords', 'block_lp_reportbuilder'),
                array('generatingreport', 'block_lp_reportbuilder')
            )
        );
     
        $params = json_encode($report->_params);
        
        $PAGE->requires->js_init_call('M.block_lp_reportbuilder_ajax.init', 
                array('rid' => $report->_id, 
                    'columns' => $report->columns, 
                    'pageSize' => intval($report->recordsperpage), 
          //          'totalRecords' => $report->get_filtered_count(),
                    'defaultSortColumn' => $report->defaultsortcolumn,
                    'params' => $this->querystring,
                    'filtering' => $filtering,
                    'wwwroot' => $CFG->wwwroot
                ), false, $ajaxconfig);

        echo $OUTPUT->header();  

        // Output the report header        
        if ($this->extraheadingtext != '') {
            echo $OUTPUT->heading($this->extraheadingtext . ' - ' . $report->fullname);
        }
        else {
            echo $OUTPUT->heading($report->fullname);
        }
        
        // Output the description
        print $report->print_description();
 
        // Display the filters
        $report->display_search();
        
        echo '<div>';
        $content = html_writer::tag('img','',array('src' => $CFG->wwwroot . '/pix/i/filter.png'));
        echo '<div id="export" class="icons-container">';
        print html_writer::tag('a', $content, array('id' => 'show-hide-search-filter', 'class' => 'button', 'title' => get_string('showhidefilters', 'block_lp_reportbuilder')));
	echo '</div>';	

        print $report->edit_button();
        echo $report->generate_export_options();
        
        // Print saved search buttons if appropriate
        echo $OUTPUT->container_start('saved-search');
        print $report->save_button();
        print $report->view_saved_menu();
        echo $OUTPUT->container_end();
        echo '</div>';
        
        echo '<br /><br />';
        echo html_writer::tag('h4', '', array('id' => 'totalCountLabel'));
                
        $report->display_table();

        echo $OUTPUT->footer();
    }
}
?>