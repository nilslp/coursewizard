<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/local/learningpool/php_browser_detection.php');
       

/**
 * Description of generic_chart
 *
 * @author brian
 */
class generic_chart {
    private $width = 700;
    private $height = 300;
    private $title = '';
    private $subtitle = '';
    private $is_date_based = false;
    private $columns = array();
    private $resultset = array();
    private $sql = '';
    private $chart_type ='ColumnChart';
    private $url = '';
    private $date_format = '';
    private $max_value = 0;
    private $min_value = 0;
    private $id = 0;
        
    private function show_second_x_axis() {
        $records = $this->resultset;
        $count = 0;
        
        if ($records) {
            $count = count($records);
        }
        
        return ($count > 7 )? true : false;
    }
    
    private function get_data_string($for_url = false, $axis = 'y') {
        // Build the data string
        $data_string = '';
        
        $records = $this->resultset;
        
        if ($records) {
            if ($for_url) {
                // When the report is being rendered via a URL (e.g. for IE 6
                // support or for scheduled jobs) it must be rendered differently
                if ($this->is_date_based) {
                    $count = count($records);                   
                }
                
                // These variables will hold the number of days in each month
                $current_month = '';
                $month = '';
                $month_array = array();
                
                foreach ($records as $record) {                    
                    if ($axis == 'y') {
                        // Set the y-axis
                        $data_string .= "$record->y,";
                    }
                    else {
                        // Set the x-axis
                        if ($this->is_date_based) {
                            switch ($count) {
                                case 7:
                                    $data_string .= "$record->x|";
                                    break;
                                case 31:
                                    $val = explode('-', $record->x);

                                    $month = $val[1];
                                    $data_string .= "$val[0]|";
                                    break;
                                default:
                                    $val = explode('-', $record->x);
//                                    
//                                    $day = $val[0];
//                                    if (in_array($day, array(1, 15))) {
//                                        $data_string .= "$day|";
//                                    }
//                                    else {
                                        $data_string .= "|";
//                                    }
                                    
                                    $month = $val[1];
                                    break;
                            } // end switch
                            
                            // Build up an array of the months and the number
                            // of days in each month
                            if ($current_month != $month) {
                                // Reset the day to the first of the month
                                $day_count = 1;
                                
                                // Store off the month name and number of days
                                $this_month = new stdClass;
                                $this_month->name = $month;
                                $this_month->days = $day_count;
                                
                                $month_array[$month] =  $this_month;
                                
                                $current_month = $month;
                            }
                            else {
                                // Store off the number of days
                                $month_array[$month] = $day_count;
                            }   
                            
                            // Increment the number of days
                            $day_count++;
                        }
                        else {
                            // If this is not a date-based month it's easy
                            // Set the x-axis
                            $data_string .= "$record->x|";
                        }
                    } 
                } // end foreach
                
                if ($axis == 'y') {
                    // Remove trailing comma
                    $data_string = rtrim($data_string, ',');
                }
                else {
                    // Remove trailing pipe
                    $data_string = '|' . rtrim($data_string, '|');
                    
                    if (count($month_array) > 0) {
                        // Build up a string to store the months
                        $data_string_months = '2:|';
                        
                        // Iterate over each month array an
                        while ($day_count = current($month_array)) {
                            // Calculate the mid point so the month name is centred
                            $mid_point = ceil(round($day_count / 2, PHP_ROUND_HALF_UP));
                            
                            for ($i = 1; $i < $day_count; $i++) {
                                if ($i == $mid_point) {
                                    // Output the month name in the middle
                                    $data_string_months .= key($month_array) . "|";
                                }
                                else {
                                    // Output a blank separator
                                    $data_string_months .= '|';
                                }
                            }

                            if (!($mid_point % 2)) {
                                // Add a trailing pipe and move to the next month
                                $data_string_months .= '|';
                            }
                            next($month_array);
                        }
                        
                        // Append the months
                        $data_string .= '|' . $data_string_months; 
                    }
                }
            }
            else {
                // JavaScript will be used to render the chart
                $data_string = "\ndata.addRows([";

                $data_array = array();
                foreach ($records as $record) {
                    $data_array[] = "['{$record->x}', {$record->y}]";
                    //$data_string .= "['{$record->x}',\t\t{$record->y}],\n";
                }

                // Render the data array
                $data_string .= join(",\n", $data_array);
                
                $data_string .= "\n]);";
            }
        }
        
        return $data_string;
    }
    
    /**
     * Builds up a string representation of the data columns
     * @return string A string containing the columns to be used in the chart
     */
    private function get_column_string() {
        $column_string = '';

        // Build the column string
        foreach ($this->columns as $column) {
            $column_string .= "data.addColumn('$column->type', '$column->label');\n";
        }
        
        return $column_string;
    }
    
    /**
     * Returns the title of the chart (with subtitle if applicable)
     * @return string 
     */
    private function get_title_string() {
        $title = ($this->subtitle != ''? $this->title . ' - ' . $this->subtitle : $this->title);
        
        return $title;
    }
    
    /**
     * Constructor method for creating a new generic_chart class
     * @global type $DB
     * @param type $id Unique identifier of the chart
     * @param type $chart_type ColumnChart|PieChart
     * @param type $url URL to the page
     * @param type $title Title of the chart
     * @param type $sql SQL statement to return the results
     * @param type $xaxis stdClass containing {name, label, type}
     * @param type $yaxis stdClass containing {name, label, type}
     * @param type $is_date_based (optional) Flag to indicate if the chart is date-based along the x-axis
     * @param type $date_interval (optional) Text date interval, e.g. 7 DAY, 3 MONTH, etc.
     * @param type $width (optional) Width of the chart
     * @param type $height (optional) Height of the chart
     */
    public function __construct($id, $chart_type, $url, $title, $sql, $xaxis, $yaxis, $is_date_based = false, $date_interval = null, $width = null, $height = null) {
        global $DB;
        
        // Initialisation
        $this->id = $id;
        $this->chart_type = $chart_type;
        $this->url = $url;
        $this->title = $title;
        
        if (isset($height)) {
            $this->height = $height;
        }
        
        if (isset($width)) {            
            $this->width = $width;
        }
        else {
            if ($this->chart_type == 'ColumnChart' && $is_date_based && $date_interval == '12 MONTH') {
                $this->width = $this->width + 400;
            }
        }
        
        $this->sql = $sql;
        $this->is_date_based = $is_date_based;
        
        $this->columns[] = $xaxis;
        $this->columns[] = $yaxis;
        
         // Get/set the date format
        $this->date_format = get_config('block_lp_charts', 'dateformat');

        if ($this->date_format == '') {
            $this->date_format = LP_CHART_DATE_FORMAT;
        }
        
        // Retrieve the records from the database
        $results = $DB->get_records_sql($sql);
        $resultset = array();
        
        if ($results) {
            $x_name = $xaxis->name;
            $y_name = $yaxis->name;
            // Build up the data array
            foreach ($results as $record) {
                $data = new stdClass;
                $data->x = addslashes($record->$x_name);
                $data->y = addslashes($record->$y_name);
                
                // Set the maximum value
                if ($this->max_value < $data->y) {
                    $this->max_value = $data->y;
                }
                
                $resultset[$data->x] = $data;
            }
        }
        
        if ($is_date_based && isset($date_interval)) {
            // The x-axis contains dates.  We must augment the resultset to include
            // missing dates for Google Charts to be able to render this without
            // skewing the data
            $date_interval_array = explode(' ', $date_interval);
            $current_date = new DateTime();
            $days =  0;
            
            // Set the chart subtitle and extract the number of days for the interval
            switch ($date_interval_array[1]) {
                case 'DAY':
                    $days = intval($date_interval_array[0]);
                    
                    if ($days == 7) {
                        $this->subtitle = get_string('thisweek', 'block_lp_charts');
                    }
                    else {
                        $this->subtitle = get_string('lastxdays', 'block_lp_charts', $days);                        
                    }
                    break;
                
                case 'MONTH':
                    $month_count = intval($date_interval_array[0]);
                    $days = 30 * $month_count;
                    
                    if ($month_count == 1) {
                        $this->subtitle = get_string('lastmonth', 'block_lp_charts');
                    }
                    else {
                        $this->subtitle = get_string('lastxmonths', 'block_lp_charts', $month_count);
                    }
                    break;                
            }
            
            $temp_resultset = array();
            
            // Get the date format
            $date_format = get_config('block_lp_charts', 'dateformat');
            
            if ($date_format == '') {
                $date_format = LP_CHART_DATE_FORMAT;
            }
            
            // Build up the date range
            for ($i = $days; $i >= 0; $i--) {
                // This is required since ->sub changes the value of the
                // DateTime variable being acted upon
                $current_date = new DateTime();
                
                $point = new stdClass;
                $interval_string = 'P' . $i . 'D';
                $int = new DateInterval($interval_string);
                $temp_date = $current_date->sub($int);
                $point->x = date_format($temp_date, 'Y-m-d');
                $key = date_format($temp_date, 'Y-m-d');
                $point->x = date_format($temp_date, $date_format);
                
                if (isset($resultset[$key])) {
                    $point->y = $resultset[$key]->y;
                }
                else {
                    $point->y = 0;
                }
                
                $temp_resultset[$key] = $point;
            }
            
            // Return the array
            $resultset = $temp_resultset;
        }
        
        $this->resultset = $resultset;
    }
 
    /**
     * Gets the JavaScript which is necessary to render this chart
     * @return type string JavaScript string
     */
    public function render_javascript() {
        $column_string = $this->get_column_string();
        $data_string = $this->get_data_string();
        $title_string = $this->get_title_string();

        $legend_position = ($this->chart_type == 'ColumnChart' ? 'none' : 'right');
        $output =  <<<SCRIPT
  google.load("visualization", "1", {packages:["corechart"]});
  google.setOnLoadCallback(drawChart);
  function drawChart() {
    var data = new google.visualization.DataTable();
    $column_string
    $data_string

    var options = {
      width: {$this->width}, height: {$this->height},
      title: '{$title_string}',

      backgroundColor: { fill:'transparent' },
      legend: {position: '$legend_position'},
      chartArea: {width:"80%"},
      vAxis: {minValue: 0}
    };

    var chart = new google.visualization.{$this->chart_type}(document.getElementById('chart_div'));
    chart.draw(data, options);
  }
SCRIPT;
        
        return $output;
    }
    
    /**
     * Renders the chart to the page
     * @global type $CFG
     * @global type $PAGE
     * @global type $OUTPUT 
     */
    public function run($notification = '') {       
        global $CFG, $PAGE, $OUTPUT, $SITE;
        
        $PAGE->set_url(new moodle_url($this->url));
        $PAGE->navbar->add(get_string('blocktitle', 'block_lp_charts'), "$CFG->wwwroot/blocks/lp_charts/index.php");
        $PAGE->navbar->add($this->title);
        $PAGE->set_heading($SITE->fullname);
        $PAGE->set_title($SITE->fullname);

        echo $OUTPUT->header();

        if ($notification != '') {
            echo $OUTPUT->notification($notification, 'notifysuccess');
        }
        
        echo html_writer::tag('h2', $this->title);
        
        if ($this->is_date_based) {
            // If the chart is date-based it should include an interval filter
            $this->add_interval_select();
        }
        
        // Detect the current browser
        $browser = browser_detection('full');
        
        // Check if AJAX/JavaScript is enabled
        $ajax_enabled = (isset($CFG->enableajax) ? $CFG->enableajax : false);
        //if (true) {
        if (!$ajax_enabled || ($browser[0] == 'ie' && $browser[1] <= 6)) {
            // If AJAX/JavaScript is disabled and the browser is IE 6 or below,
            // the user gets a static image
            // (The html_writer can't be used as the '&' needs to be preserved as is)
            echo '<img src="' . $this->get_url_to_image() . '" />';
        }
        else {
            // We can use the slick version
            // Include the JavaScript that does the Google Charts magic
            if (get_config('block_lp_charts', 'nohttps')) {
                $src = GOOGLE_JS_API_URL_UNSECURE;
            }
            else {
                $src = GOOGLE_JS_API_URL;
            }
            
            echo html_writer::tag('script', '', array('type' => 'text/javascript', 'src'=> $src));
            echo html_writer::tag('script', $this->render_javascript(), array('type' => 'text/javascript'));

            // A <div> tag with an id of 'chart_div' is required for Google Charts
            echo html_writer::tag('div', '', array('id' => 'chart_div'));
        }
        
        // Display the subzription section
        $this->add_subscription_table();

        $this->add_subscribe_form();
        
        echo $OUTPUT->footer();
    }
    
    /**
     * Adds the interval filter to the UI
     * @global type $CFG 
     */
    function add_interval_select() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/lp_charts/interval_filter_form.php');
        
        $filter = new chart_interval_filter_form();
        $filter->display();
    }
    
    /**
     * Adds the subscription form so that the user can indicate to receive
     * the chart via scheduled email
     * @global type $CFG 
     */
    function add_subscribe_form() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/lp_charts/subscribe_form.php');
        
        $subscribe_form = new chart_subscribe_form();
        $subscribe_form->display();
    }
    
    /**
     * Adds a table displaying the user's current subscription options
     * @global type $DB
     * @global type $USER 
     */
    function add_subscription_table() {
        global $CFG, $DB, $USER;

        $string_7days = get_string('thisweek', 'block_lp_charts');
        $string_1month = get_string('lastmonth', 'block_lp_charts');
        $string_3months = get_string('last3months', 'block_lp_charts');
        $string_6months = get_string('last6months', 'block_lp_charts');
        $string_12months = get_string('lastyear', 'block_lp_charts');
        
        $sql = "SELECT s.id,
                    s.chartid, 
                    s.schedule_type, 
                    s.chart_interval, 
                    CASE(s.chart_interval) 
                        WHEN '7 DAY' THEN '$string_7days'
                        WHEN '1 MONTH' THEN '$string_1month'
                        WHEN '3 MONTH' THEN '$string_3months'
                        WHEN '6 MONTH' THEN '$string_6months' 
                        WHEN '12 MONTH' THEN '$string_12months'
                    END AS period, 
                    c.shortname, 
                    c.fullname, 
                    c.sql_query, 
                    c.chart_type
                FROM {lp_chart_schedule} s
                INNER JOIN {lp_chart_schedule_recipient} r ON s.id = r.chartscheduleid
                LEFT JOIN {lp_chart} c ON c.id = s.chartid
                WHERE c.id = $this->id AND r.userid = {$USER->id}";

        $subscriptions = $DB->get_records_sql($sql);
        
        if ($subscriptions) {
            // Generate the list display table
            $tableheader = array(get_string('schedule', 'block_lp_charts'),
                         get_string('period', 'block_lp_charts'),
                         '');
            
           foreach ($subscriptions as $subscription) {
                $cells = array();
                
                $cells[] = new html_table_cell(ucwords($subscription->schedule_type));           
                $cells[] = new html_table_cell($subscription->period);              
                $cells[] = new html_table_cell("<a href='" . $CFG->wwwroot . 
                        '/blocks/lp_charts/view.php?id=' . $this->id . '&d=1&sid=' . $subscription->id . "' " .
                        'title="' . get_string('unsubscribe', 'block_lp_charts') . '">' .
                        '<img src="'.$CFG->wwwroot.'/pix/t/delete.gif" alt="' .
                        get_string('unsubscribe', 'block_lp_charts') . '" /></a>');

                $row = new html_table_row($cells);          

                $data[] = $row;
            }

            // Output the user generated report table
            $reportstable = new html_table();
            $reportstable->width = "250px";
            $reportstable->summary = '';
            $reportstable->head = $tableheader;
            $reportstable->data = $data;

            echo html_writer::table($reportstable);
        }
    }
    /**
     * Generates a URL representation of this report
     * @return type URL to the reports on Google.com
     */
    function get_url_to_image() {
        $url = 'http://chart.apis.google.com/chart';
        
        $interval = 1;
        
        // Set the interval on the y-axis
        if ($this->max_value == 0) {
            $this->max_value = 10;
            $interval = 1;
        }
        else if ($this->max_value <= 10) {
            $interval = 1;
        }
        else if ($this->max_value <= 20) {
            $interval = 2;
        }
        else if ($this->max_value <= 50) {
            $interval = 5;
        }
        else {
            $interval = 10;
        }

        $chart_type = '';
        
        switch ($this->chart_type) {
            case 'ColumnChart':
                $chart_type = 'bvg';
                break;
            
            case 'PieChart':
                $chart_type = 'p';
                break;
        }
        
        $query_data = array('chxt' => 'y',
                            'chbh' => 'a',
                            'chs' => $this->width . 'x' . $this->height,                        // Size
                            //'chco' => '0D9FE0',                                                 // Colour
                            'cht' => $chart_type,
                            'chxr' => '1,'. $this->min_value . ',' . $this->max_value . ',' . $interval,   // Axis interval, etc.
                            'chds' => 'a',                                                      // Scaling (a = auto)
                            'chd' => 't:' . $this->get_data_string(true, 'y'),                  // Data
                            'chxt'=> 'x,y' . ($this->show_second_x_axis()? ',x' : ''),          // Axis mapping for data
                            'chxl' => '0:' . $this->get_data_string(true, 'x'),                 // X-axis
                            'chtt' => $this->get_title_string());                               // Title
        
        $url .= '?' . http_build_query($query_data, null, '&');
        
        // Replace comma, pipe and colon characters which have been escaped
        $url = str_replace('%2C', ',', $url);
        $url = str_replace('%7C', '|', $url);
        $url = str_replace('%3A', ':', $url);
        
        return $url;
    }
}

?>
