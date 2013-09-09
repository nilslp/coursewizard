<?php 
global $CFG;
  
require_once($CFG->dirroot.'/blocks/lp_charts/lib.php');
require_once($CFG->dirroot.'/lib/moodlelib.php');

class block_lp_charts extends block_base {
    function init() {
        $this->title = get_string('blocktitle', 'block_lp_charts');
    }

    function applicable_formats() {
        return array('site' => true, 'my' => true);
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content         = new stdClass;
        
        $context = get_context_instance(CONTEXT_SYSTEM);
        
        if (has_capability('block/lp_charts:view', $context)) {
            $this->content->text   = get_charts_html();
        }
        else {
            $this->content->text = ''; // get_string('nocapability', 'block_lp_charts');
        }
        //$this->content->footer = 'Footer here...';

        return $this->content;            
    }
    
    function instance_allow_multiple() {
        // Only one instance of this block can exist
        return false;
    }
    
    /**
    * Standard cron function
    */
    function cron() {
        $this->trace('block_lp_charts_cron() started at '. date('H:i:s'));

        try {
            $this->block_lp_charts_process();
        } 
        catch (Exception $e) {
            $this->trace('block_lp_charts_cron() failed with an exception:');
            $this->trace($e->getMessage());
        }

        $this->trace('block_lp_charts_cron() finished at ' . date('H:i:s'));
    }

    /**
    * This function does the cron process within the time range according to settings.
    */
    function block_lp_charts_process() {
        global $CFG;

        // Get the current day and time
        $current_minute = intval(date('i'));
        $current_day = date('N');
        $current_hour = date('G');
        $current_day_of_month = date('j');

        // Some work is required to work out the timezone
        if (isset($CFG->timezone)) {            
            if (preg_match('#^\d+(?:\.\d{1,2})?$#', $CFG->timezone)) {
                // This handles when the timezone is set to something like
                // UTC + 1
                $offset = intval($CFG->timezone);
                
                $current_hour = $current_hour + $offset;
            }
            else {
                // The timezone is set to something like Europe/London
                if (date('I') == 1) {
                    // It's Daylight Savings Time
                    $tz = get_timezone_record($CFG->timezone);
                    $current_hour = $current_hour + ($tz->dstoff / 60);
                }
                else {
                    $offset = get_timezone_offset($CFG->timezone);
                    
                    // Offset will be returned in seconds
                    if (isset($offset) && $offset != 0) {
                        // Add the offset to the current hour
                        $current_hour = $current_hour + ($offset / 3600);
                    }
                }
            }
            
        }
        else {
            $offset = 0;
        }
        
        // Get the schedule config settings
        $scheduled_day = get_config('block_lp_charts', 'deliveryday');
        $scheduled_hour = get_config('block_lp_charts', 'deliverytime');

        $schedule_type = array();

        $this->trace("Offset is $offset");
        $this->trace("Current date/time is " . date(DATE_RFC822) . ", cron should be run at $scheduled_hour:00");

        if ($current_hour == $scheduled_hour && $current_minute <= 5) {
        //if (true) {
            // The cron should be run
            $schedule_type[] = 'daily';

            // Check if the weekly charts should be sent
            if ($current_day == $scheduled_day) {
                $schedule_type[] = 'weekly';
            }

            if ($current_day_of_month == 1) {
                // It's the first of the month, so monthly reports should be sent
                $schedule_type[] = 'monthly';
            }

            $this->trace("Processing for " . join('/', $schedule_type) . " charts");

            $this->block_lp_charts_send_charts_to_recipients($schedule_type, true);
        }
        else {
            $this->trace("Nothing to do at this time: $scheduled_hour != $current_hour");
        }
    }

    function block_lp_charts_send_charts_to_recipients($schedule_type = array('daily'), $cron = false) {
        global $DB, $CFG;

        // Retrieve the strings
        $thisweek_string = strtolower(get_string('thisweek', 'block_lp_charts'));
        $lastmonth_string = strtolower(get_string('lastmonth', 'block_lp_charts'));
        $last3months_string = strtolower(get_string('last3months', 'block_lp_charts'));
        $last6months_string = strtolower(get_string('last6months', 'block_lp_charts'));
        $lastyear_string = strtolower(get_string('lastyear', 'block_lp_charts'));

        // Build the WHERE clause
        $where_clause = "WHERE cs.schedule_type IN ('" . str_replace(',', "','", join(',', $schedule_type)) . "') ";

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
                $where_clause ";

        $schedules = $DB->get_records_sql($sql);

        if (count($schedules) == 0) {
            // Nothing to do so exit
            return 'block_lp_charts_send_charts_to_recipients() - no schedules defined.';
        }

        // Initialise a temporary working directory to store all of the downloaded
        // chart images
        $directory = make_temp_directory(LP_CHART_WORKING_DIR) . '/';

        foreach ($schedules as $schedule) {
            $this->trace("Processing {$schedule->title}...");

            // Generate a chart according to the interval (if specified)
            get_chart($schedule->chartid, $chart_record, $xaxis, $yaxis);

            // Swap the interval into the SQL statement (if required)
            $sql = str_replace('$interval', $schedule->chart_interval, $chart_record->sql_query);

            $chart = new generic_chart($schedule->chartid, $chart_record->chart_type, '', $chart_record->fullname, $sql, $xaxis, $yaxis, $chart_record->is_date_based, $schedule->chart_interval);

            // Retrieve the URL
            $url = $chart->get_url_to_image();

            // Formulate the filename (save as .png)
            $filename = str_replace(" ", "_", $chart_record->fullname . ' ' . $schedule->chart_interval) . '.png';
            $filename_for_curl = $directory . $filename;

            // Fix the filename for attachment in case $CFG->dataroot contains
            // a trailing slash character
            if (substr($CFG->dataroot, -1) == '/') {
                $filename_for_attach = 'temp/' . LP_CHART_WORKING_DIR . '/' . $filename;
            }
            else {
                $filename_for_attach = '/temp/' . LP_CHART_WORKING_DIR . '/' . $filename;
            }

            //  Use CURL to download a copy of the image from the URL
            $ch = curl_init($url);
            $fp = fopen($filename_for_curl, "w");

            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);

            curl_exec($ch);
            curl_close($ch);
            fclose($fp);

            if (file_exists($filename_for_curl)) { 
                // Get all the recipients for this chart
                $sql = "SELECT u.*
                        FROM {lp_chart_schedule_recipient} csr
                        INNER JOIN {user} u ON u.id = csr.userid
                        WHERE csr.chartscheduleid = $schedule->id ";

                $recipients = $DB->get_records_sql($sql);

                if ($recipients) {
                    // Set the email subject, message text and attachment name
                    $subject_param = new stdClass();
                    $subject_param->schedule_type = get_string($schedule->schedule_type, 'block_lp_charts');
                    $subject_param->title = $schedule->title;

                    $subject = get_string('emailsubject', 'block_lp_charts', $subject_param);
                    $messagetext = get_string('emailbody', 'block_lp_charts', $schedule->title);
                    $attachname = $schedule->title . '.png';

                    foreach ($recipients as $recipient) {
                        // Send the chart to each recipient
                        if (email_to_user($recipient, '', $subject, $messagetext, '', $filename_for_attach, $attachname, false, '', '')) {
                            $this->trace("Email sent to {$recipient->email}");
                        }
                        else {
                            $this->trace("Unable to send email to {$recipient->email}");
                        }
                    }

                    $this->trace("All emails processed");
                }
                else {
                    $this->trace("No recipients found -- skipping");
                }

                // Tidy up and delete the temporary file
                unlink($filename_for_curl);
            }
            else {
                $this->trace("CURL failed to download to $filename_for_curl");
            }
        }
    }

    /**
    * Helper function for messages
    * @param type $msg The string to output
    */
    function trace($msg) {
        mtrace('lp_charts: ' . $msg);    
    }
    
    /**
     * Does the plugin have a settings page?
     * @return boolean
     */
    function has_config() {
        return true;
    }
}


