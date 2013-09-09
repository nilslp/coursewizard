<?php

class block_lp_reportbuilder extends block_base {
    function init() {
        $this->title   = get_string('myreports', 'block_lp_reportbuilder');
        $this->version = 2012010601;
    }
    
    public function instance_allow_multiple() {
        return false;
    }    
    
    // Define the custom sort function
    function custom_sort($a, $b) {
        return $a['fullname'] > $b['fullname'];
    }
            
    /**
     *
     * @global type $CFG
     * @global moodle_database $DB
     * @global type $OUTPUT
     * @return type 
     */
    function get_content() {
        global $CFG, $DB, $OUTPUT;
        require_once("{$CFG->dirroot}/blocks/lp_reportbuilder/lib.php");
        
        $context = get_context_instance(CONTEXT_SYSTEM);
        
        $html = '';
        
        if (has_capability('block/lp_reportbuilder:viewreports', $context)) {
            $shortnames_to_display = array();

            // Get all the embedded reports
            $embedded_reports = reportbuilder_get_all_embedded_reports();

            // Get a list of all the embeddd reports as held in the database
            // which have the 'showinblock' flag set
            $embedded_reports_for_display = $DB->get_records('report_builder', array('showinblock' => 1, 'embedded' => 1));

            foreach ($embedded_reports_for_display as $embedded_report_for_display) {
                if (reportbuilder::is_capable($embedded_report_for_display->id)) {
                    $shortnames_to_display[] = $embedded_report_for_display->shortname;
                }
            }

            // Remove any embedded reports which shouldn't be displayed in this block
            $i = 0;
            foreach ($embedded_reports as $embedded_report) {
                // Include the wwwroot in case the site is hosted in a sub-directory
                $embedded_reports[$i]->url = $CFG->wwwroot . $embedded_reports[$i]->url;                
                if (!in_array($embedded_report->shortname, $shortnames_to_display)) {
                    unset($embedded_reports[$i]);                
                }                
                $i++;
            }

            $embedded_reports = array_values($embedded_reports);

            $user_reports = $DB->get_records('report_builder', array('showinblock' => 1, 'embedded' => 0));
            
            foreach ($user_reports as $user_report) {
                // Build up the URL
                $user_report->url = $CFG->wwwroot . '/blocks/lp_reportbuilder/report.php?id=' . $user_report->id;
            }
            
            $i = 0;
            foreach ($user_reports as $user_report) {
                if (reportbuilder::is_capable($user_report->id)) {
                    $user_reports_to_display[] = $user_report->id;
                }
                
                $i++;
            }
            
            foreach ($user_reports as $user_report) {
                if (!in_array($user_report->id, $user_reports_to_display)) {
                    unset($user_reports[$i]);
                }
            }
            
            $user_reports = array_values($user_reports);
            
            // Combine the embedded and user-generated reports
            $reports = array_merge($embedded_reports, $user_reports);
          
            // Sort the multidimensional array
            //usort($reports, "custom_sort");

            // Build up the HTML table to display this
            if (is_array($reports) && count($reports) > 0) { 
                $html .= html_writer::start_tag('ul', array('class'=>'report-list'));
                foreach($reports as $report) {
                    $html .= html_writer::tag('li', html_writer::link($report->url, $report->fullname));
                }
                $html .= html_writer::end_tag('ul');
            }
            else {
                // No reports were found
                $html .= $OUTPUT->heading(get_string('noreportsfound', 'block_lp_reportbuilder'),3, array('class'=>'no-reports'));
            }
            $html .= html_writer::link($CFG->wwwroot.'/blocks/lp_reportbuilder/myreports.php', get_string('viewallmyreports','block_lp_reportbuilder').'&hellip;', array('class'=>'btn view-'));
            
            if ($this->content !== NULL) {
                return $this->content;
            }
        }
        else {
            $html = ''; //get_string('nocapability', 'block_lp_reportbuilder');
        }
        
        $this->content =  new stdClass;

        $this->content->text = $html;
            
        return $this->content;
    }    

    function instance_allow_config() {
        return false;
    }
     
    
    /**
     * Standard cron function
     */
    public function cron() {
        global $CFG;
        
        require_once($CFG->dirroot.'/blocks/lp_reportbuilder/cron.php');
        
        $group = optional_param('group', 0, PARAM_INT);
        $this->trace('cron started at '. date('H:i:s'));
        try {
            reportbuilder_cron($group);
        } catch (Exception $e) {
            $this->trace('cron failed with an exception:');
            $this->trace($e->getMessage());
        }
        $this->trace('cron finished at ' . date('H:i:s'));
    }

    /**
     * helper function to print our messages consistently
     */
    function trace($msg) {
        mtrace('lp_reportbuilder: ' . $msg);    
    }

}