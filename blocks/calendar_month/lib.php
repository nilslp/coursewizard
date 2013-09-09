<?php

global $CFG;

require_once $CFG->dirroot . '/calendar/lib.php';

/**
 * Generates the HTML for a miniature calendar
 *
 * @global core_renderer $OUTPUT
 * @param array $courses
 * @param array $groups
 * @param array $users
 * @param int $cal_month
 * @param int $cal_year
 * @return string
 */
function custom_calendar_get_mini($courses, $groups, $users, $cal_month = false, $cal_year = false) {
    global $CFG, $USER, $OUTPUT;

    $display = new stdClass;
    $display->minwday = get_user_preferences('calendar_startwday', calendar_get_starting_weekday());
    $display->maxwday = $display->minwday + 6;

    $content = '';

    if(!empty($cal_month) && !empty($cal_year)) {
        $thisdate = usergetdate(time()); // Date and time the user sees at his location
        if($cal_month == $thisdate['mon'] && $cal_year == $thisdate['year']) {
            // Navigated to this month
            $date = $thisdate;
            $display->thismonth = true;
        } else {
            // Navigated to other month, let's do a nice trick and save us a lot of work...
            if(!checkdate($cal_month, 1, $cal_year)) {
                $date = array('mday' => 1, 'mon' => $thisdate['mon'], 'year' => $thisdate['year']);
                $display->thismonth = true;
            } else {
                $date = array('mday' => 1, 'mon' => $cal_month, 'year' => $cal_year);
                $date['month'] = date("F", mktime(0, 0, 0, $cal_month, 1));
                $display->thismonth = false;
            }
        }
    } else {
        $date = usergetdate(time()); // Date and time the user sees at his location
        $display->thismonth = true;
    }

    // Fill in the variables we 're going to use, nice and tidy
    list($d, $m, $y) = array($date['mday'], $date['mon'], $date['year']); // This is what we want to display
    $display->maxdays = calendar_days_in_month($m, $y);

    if (get_user_timezone_offset() < 99) {
        // We 'll keep these values as GMT here, and offset them when the time comes to query the db
        $display->tstart = gmmktime(0, 0, 0, $m, 1, $y); // This is GMT
        $display->tend = gmmktime(23, 59, 59, $m, $display->maxdays, $y); // GMT
    } else {
        // no timezone info specified
        $display->tstart = mktime(0, 0, 0, $m, 1, $y);
        $display->tend = mktime(23, 59, 59, $m, $display->maxdays, $y);
    }

    $startwday = dayofweek(1, $m, $y);

    // Align the starting weekday to fall in our display range
    // This is simple, not foolproof.
    if($startwday < $display->minwday) {
        $startwday += 7;
    }

    // TODO: THIS IS TEMPORARY CODE!
    // [pj] I was just reading through this and realized that I when writing this code I was probably
    // asking for trouble, as all these time manipulations seem to be unnecessary and a simple
    // make_timestamp would accomplish the same thing. So here goes a test:
    //$test_start = make_timestamp($y, $m, 1);
    //$test_end   = make_timestamp($y, $m, $display->maxdays, 23, 59, 59);
    //if($test_start != usertime($display->tstart) - dst_offset_on($display->tstart)) {
        //notify('Failed assertion in calendar/lib.php line 126; display->tstart = '.$display->tstart.', dst_offset = '.dst_offset_on($display->tstart).', usertime = '.usertime($display->tstart).', make_t = '.$test_start);
    //}
    //if($test_end != usertime($display->tend) - dst_offset_on($display->tend)) {
        //notify('Failed assertion in calendar/lib.php line 130; display->tend = '.$display->tend.', dst_offset = '.dst_offset_on($display->tend).', usertime = '.usertime($display->tend).', make_t = '.$test_end);
    //}


    // Get the events matching our criteria. Don't forget to offset the timestamps for the user's TZ!
    $events = calendar_get_events(
        usertime($display->tstart) - dst_offset_on($display->tstart),
        usertime($display->tend) - dst_offset_on($display->tend),
        $users, $groups, $courses);

    // Set event course class for course events
    if (!empty($events)) {
        foreach ($events as $eventid => $event) {
            if (!empty($event->modulename)) {
                $cm = get_coursemodule_from_instance($event->modulename, $event->instance);
                if (!groups_course_module_visible($cm)) {
                    unset($events[$eventid]);
                }
            }
        }
    }

    // This is either a genius idea or an idiot idea: in order to not complicate things, we use this rule: if, after
    // possibly removing SITEID from $courses, there is only one course left, then clicking on a day in the month
    // will also set the $SESSION->cal_courses_shown variable to that one course. Otherwise, we 'd need to add extra
    // arguments to this function.

    $hrefparams = array();
    if(!empty($courses)) {
        $courses = array_diff($courses, array(SITEID));
        if(count($courses) == 1) {
            $hrefparams['course'] = reset($courses);
        }
    }

    // We want to have easy access by day, since the display is on a per-day basis.
    // Arguments passed by reference.
    //calendar_events_by_day($events, $display->tstart, $eventsbyday, $durationbyday, $typesbyday);
    calendar_events_by_day($events, $m, $y, $eventsbyday, $durationbyday, $typesbyday, $courses);

    //Accessibility: added summary and <abbr> elements.
    $days_title = calendar_get_days();

    $summary = get_string('calendarheading', 'calendar', userdate(make_timestamp($y, $m), get_string('strftimemonthyear')));
    $content .= '<table class="minicalendar calendartable" summary="'.$summary.'">'; // Begin table
    $content .= '<tr class="weekdays">'; // Header row: day names

    // Print out the names of the weekdays
    $days = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
    for($i = $display->minwday; $i <= $display->maxwday; ++$i) {
        // This uses the % operator to get the correct weekday no matter what shift we have
        // applied to the $display->minwday : $display->maxwday range from the default 0 : 6
        $content .= '<th scope="col"><abbr title="'. get_string($days_title[$i % 7], 'calendar') .'">'.
            get_string($days[$i % 7], 'calendar') ."</abbr></th>\n";
    }

    $content .= '</tr><tr>'; // End of day names; prepare for day numbers

    // For the table display. $week is the row; $dayweek is the column.
    $dayweek = $startwday;

    // Paddding (the first week may have blank days in the beginning)
    for($i = $display->minwday; $i < $startwday; ++$i) {
        $content .= '<td class="dayblank">&nbsp;</td>'."\n";
    }

    $weekend = CALENDAR_DEFAULT_WEEKEND;
    if (isset($CFG->calendar_weekend)) {
        $weekend = intval($CFG->calendar_weekend);
    }

    // Now display all the calendar
    for($day = 1; $day <= $display->maxdays; ++$day, ++$dayweek) {
        if($dayweek > $display->maxwday) {
            // We need to change week (table row)
            $content .= '</tr><tr>';
            $dayweek = $display->minwday;
        }

        // Reset vars
        $cell = '';
        if ($weekend & (1 << ($dayweek % 7))) {
            // Weekend. This is true no matter what the exact range is.
            $class = 'weekend day';
        } else {
            // Normal working day.
            $class = 'day';
        }

        // Special visual fx if an event is defined
        if(isset($eventsbyday[$day])) {
            $class .= ' hasevent';
            $hrefparams['view'] = 'day';
            $dayhref = calendar_get_link_href(new moodle_url(CALENDAR_URL.'view.php', $hrefparams), $day, $m, $y);

            $popupcontent = '';
            foreach($eventsbyday[$day] as $eventid) {
                if (!isset($events[$eventid])) {
                    continue;
                }
                $event = $events[$eventid];
                $popupalt  = '';
                $component = 'moodle';
                if(!empty($event->modulename)) {
                    $popupicon = 'icon';
                    $popupalt  = $event->modulename;
                    $component = $event->modulename;
                } else if ($event->courseid == SITEID) {                                // Site event
                    $popupicon = 'c/site';
                } else if ($event->courseid != 0 && $event->courseid != SITEID && $event->groupid == 0) {      // Course event
                    $popupicon = 'c/course';
                } else if ($event->groupid) {                                      // Group event
                    $popupicon = 'c/group';
                } else if ($event->userid) {                                       // User event
                    $popupicon = 'c/user';
                }

                $dayhref->set_anchor('event_'.$event->id);

                $popupcontent .= html_writer::start_tag('li');
                $popupcontent .= $OUTPUT->pix_icon($popupicon, $popupalt, $component);
                $popupcontent .= html_writer::link($dayhref, format_string($event->name, true));
                $popupcontent .= html_writer::end_tag('li');
            }
            
            if (!empty($popupcontent)) {
                $popupcontent = html_writer::tag('ul', $popupcontent);
            }
            
            // Class and cell content
            if(isset($typesbyday[$day]['startglobal'])) {
                $class .= ' calendar_event_global';
            } else if(isset($typesbyday[$day]['startcourse'])) {
                $class .= ' calendar_event_course';
            } else if(isset($typesbyday[$day]['startgroup'])) {
                $class .= ' calendar_event_group';
            } else if(isset($typesbyday[$day]['startuser'])) {
                $class .= ' calendar_event_user';
            }

            $cell = '<div class="tooltip"><a href="'.(string)$dayhref.'" >'.$day.'</a><span class="events"><div class="arrow_box"><h2 class="eventtitle">' . sprintf("%s, %d %s", ucfirst($days_title[$dayweek]), $day, $date['month']) . '</h2>' . $popupcontent . '</div></span></div>';
            } else {
            $cell = $day;
        }

        $durationclass = false;
        if (isset($typesbyday[$day]['durationglobal'])) {
            $durationclass = ' duration_global';
        } else if(isset($typesbyday[$day]['durationcourse'])) {
            $durationclass = ' duration_course';
        } else if(isset($typesbyday[$day]['durationgroup'])) {
            $durationclass = ' duration_group';
        } else if(isset($typesbyday[$day]['durationuser'])) {
            $durationclass = ' duration_user';
        }
        if ($durationclass) {
            $class .= ' duration '.$durationclass;
        }

        // If event has a class set then add it to the table day <td> tag
        // Note: only one colour for minicalendar
        if(isset($eventsbyday[$day])) {
            foreach($eventsbyday[$day] as $eventid) {
                if (!isset($events[$eventid])) {
                    continue;
                }
                $event = $events[$eventid];
                if (!empty($event->class)) {
                    $class .= ' '.$event->class;
                }
                break;
            }
        }

        // Special visual fx for today
        //Accessibility: hidden text for today, and popup.
        if($display->thismonth && $day == $d) {
            $class .= ' today';
            $today = get_string('today', 'calendar').' '.userdate(time(), get_string('strftimedayshort'));

            if(! isset($eventsbyday[$day])) {
                $class .= ' eventnone';
            }
            $cell = get_accesshide($today.' ').$cell;
        }

        // Just display it
        if(!empty($class)) {
            $class = ' class="'.$class.'"';
        }
        $content .= '<td'.$class.'>'.$cell."</td>\n";
    }

    // Paddding (the last week may have blank days at the end)
    for($i = $dayweek; $i <= $display->maxwday; ++$i) {
        $content .= '<td class="dayblank">&nbsp;</td>';
    }
    $content .= '</tr>'; // Last row ends

    $content .= '</table>'; // Tabular display of days ends

    return $content;
}

function custom_calendar_top_controls($type, $data) {
    global $CFG;
    $content = '';
    if(!isset($data['d'])) {
        $data['d'] = 1;
    }

    // Ensure course id passed if relevant
    // Required due to changes in view/lib.php mainly (calendar_session_vars())
    $courseid = '';
    if (!empty($data['id'])) {
        $courseid = '&amp;course='.$data['id'];
    }

    if(!checkdate($data['m'], $data['d'], $data['y'])) {
        $time = time();
    }
    else {
        $time = make_timestamp($data['y'], $data['m'], $data['d']);
    }
    $date = usergetdate($time);

    $data['m'] = $date['mon'];
    $data['y'] = $date['year'];

    //Accessibility: calendar block controls, replaced <table> with <div>.
    //$nexttext = link_arrow_right(get_string('monthnext', 'access'), $url='', $accesshide=true);
    //$prevtext = link_arrow_left(get_string('monthprev', 'access'), $url='', $accesshide=true);

    switch($type) {
        case 'frontpage':
            list($prevmonth, $prevyear) = calendar_sub_month($data['m'], $data['y']);
            list($nextmonth, $nextyear) = calendar_add_month($data['m'], $data['y']);
            $nextlink = calendar_get_link_next(get_string('monthnext', 'access'), 'index.php?', 0, $nextmonth, $nextyear, $accesshide=true);
            $prevlink = calendar_get_link_previous(get_string('monthprev', 'access'), 'index.php?', 0, $prevmonth, $prevyear, true);

            $calendarlink = calendar_get_link_href(new moodle_url(CALENDAR_URL.'view.php', array('view'=>'month')), 1, $data['m'], $data['y']);
            if (!empty($data['id'])) {
                $calendarlink->param('course', $data['id']);
            }

            if (right_to_left()) {
                $left = $nextlink;
                $right = $prevlink;
            } else {
                $left = $prevlink;
                $right = $nextlink;
            }

            $content .= html_writer::start_tag('div', array('class'=>'calendar-controls'));
            $content .= $left.'<span class="hide"> | </span>';
            //$content .= html_writer::tag('span', html_writer::link($calendarlink, userdate($time, get_string('strftimemonthyear')), array('title'=>get_string('monththis','calendar'))), array('class'=>'current'));

            $content .= html_writer::tag('span', html_writer::link($calendarlink, userdate($time, get_string('strftimemonthyear')), array('title'=>userdate($time, get_string('strftimemonthyear')))), array('class'=>'current'));

            $content .= '<span class="hide"> | </span>'. $right;
            $content .= "<span class=\"clearer\"><!-- --></span>\n";
            $content .= html_writer::end_tag('div');

            break;
        case 'course':
            list($prevmonth, $prevyear) = calendar_sub_month($data['m'], $data['y']);
            list($nextmonth, $nextyear) = calendar_add_month($data['m'], $data['y']);
            $nextlink = calendar_get_link_next(get_string('monthnext', 'access'), 'view.php?id='.$data['id'].'&amp;', 0, $nextmonth, $nextyear, $accesshide=true);
            $prevlink = calendar_get_link_previous(get_string('monthprev', 'access'), 'view.php?id='.$data['id'].'&amp;', 0, $prevmonth, $prevyear, true);

            $calendarlink = calendar_get_link_href(new moodle_url(CALENDAR_URL.'view.php', array('view'=>'month')), 1, $data['m'], $data['y']);
            if (!empty($data['id'])) {
                $calendarlink->param('course', $data['id']);
            }

            if (right_to_left()) {
                $left = $nextlink;
                $right = $prevlink;
            } else {
                $left = $prevlink;
                $right = $nextlink;
            }

            $content .= html_writer::start_tag('div', array('class'=>'calendar-controls'));
            $content .= $left.'<span class="hide"> | </span>';
            $content .= html_writer::tag('span', html_writer::link($calendarlink, userdate($time, get_string('strftimemonthyear')), array('title'=>get_string('monththis','calendar'))), array('class'=>'current'));
            $content .= '<span class="hide"> | </span>'. $right;
            $content .= "<span class=\"clearer\"><!-- --></span>";
            $content .= html_writer::end_tag('div');
            break;
        case 'upcoming':
            $calendarlink = calendar_get_link_href(new moodle_url(CALENDAR_URL.'view.php', array('view'=>'upcoming')), 1, $data['m'], $data['y']);
            if (!empty($data['id'])) {
                $calendarlink->param('course', $data['id']);
            }
            $calendarlink = html_writer::link($calendarlink, userdate($time, get_string('strftimemonthyear')));
            $content .= html_writer::tag('div', $calendarlink, array('class'=>'centered'));
            break;
        case 'display':
            $calendarlink = calendar_get_link_href(new moodle_url(CALENDAR_URL.'view.php', array('view'=>'month')), 1, $data['m'], $data['y']);
            if (!empty($data['id'])) {
                $calendarlink->param('course', $data['id']);
            }
            $calendarlink = html_writer::link($calendarlink, userdate($time, get_string('strftimemonthyear')));
            $content .= html_writer::tag('h3', $calendarlink);
            break;
        case 'month':
            list($prevmonth, $prevyear) = calendar_sub_month($data['m'], $data['y']);
            list($nextmonth, $nextyear) = calendar_add_month($data['m'], $data['y']);
            $prevdate = make_timestamp($prevyear, $prevmonth, 1);
            $nextdate = make_timestamp($nextyear, $nextmonth, 1);
            $prevlink = calendar_get_link_previous(userdate($prevdate, get_string('strftimemonthyear')), 'view.php?view=month'.$courseid.'&amp;', 1, $prevmonth, $prevyear);
            $nextlink = calendar_get_link_next(userdate($nextdate, get_string('strftimemonthyear')), 'view.php?view=month'.$courseid.'&amp;', 1, $nextmonth, $nextyear);

            if (right_to_left()) {
                $left = $nextlink;
                $right = $prevlink;
            } else {
                $left = $prevlink;
                $right = $nextlink;
            }

            $content .= html_writer::start_tag('div', array('class'=>'calendar-controls'));
            $content .= $left . '<span class="hide"> | </span><h1 class="current">'.userdate($time, get_string('strftimemonthyear'))."</h1>";
            $content .= '<span class="hide"> | </span>' . $right;
            $content .= '<span class="clearer"><!-- --></span>';
            $content .= html_writer::end_tag('div')."\n";
            break;
        case 'day':
            $days = calendar_get_days();
            $data['d'] = $date['mday']; // Just for convenience
            $prevdate = usergetdate(make_timestamp($data['y'], $data['m'], $data['d'] - 1));
            $nextdate = usergetdate(make_timestamp($data['y'], $data['m'], $data['d'] + 1));
            $prevname = calendar_wday_name($days[$prevdate['wday']]);
            $nextname = calendar_wday_name($days[$nextdate['wday']]);
            $prevlink = calendar_get_link_previous($prevname, 'view.php?view=day'.$courseid.'&amp;', $prevdate['mday'], $prevdate['mon'], $prevdate['year']);
            $nextlink = calendar_get_link_next($nextname, 'view.php?view=day'.$courseid.'&amp;', $nextdate['mday'], $nextdate['mon'], $nextdate['year']);

            if (right_to_left()) {
                $left = $nextlink;
                $right = $prevlink;
            } else {
                $left = $prevlink;
                $right = $nextlink;
            }

            $content .= html_writer::start_tag('div', array('class'=>'calendar-controls'));
            $content .= $left;
            $content .= '<span class="hide"> | </span><span class="current">'.userdate($time, get_string('strftimedaydate')).'</span>';
            $content .= '<span class="hide"> | </span>'. $right;
            $content .= "<span class=\"clearer\"><!-- --></span>";
            $content .= html_writer::end_tag('div')."\n";

            break;
    }
    return $content;
}

/**
 * Get the controls filter for calendar.
 *
 * Filter is used to hide calendar info from the display page
 *
 * @param moodle_url $returnurl return-url for filter controls
 * @return string $content return filter controls in html
 */
function custom_calendar_filter_controls($params) {
    global $CFG, $USER, $OUTPUT;

    $groupevents = true;
    $id = optional_param( 'id',0,PARAM_INT );
    
    $params['sesskey'] = sesskey();
    $seturl = new moodle_url('/calendar_month/index.php', $params);
    $content = html_writer::start_tag('ul');

    $seturl->param('var', 'showglobal');
    $content .= calendar_filter_controls_element($seturl, CALENDAR_EVENT_GLOBAL);

    $seturl->param('var', 'showcourses');
    $content .= calendar_filter_controls_element($seturl, CALENDAR_EVENT_COURSE);

    if (isloggedin() && !isguestuser()) {
        if ($groupevents) {
            // This course MIGHT have group events defined, so show the filter
            $seturl->param('var', 'showgroups');
            $content .= calendar_filter_controls_element($seturl, CALENDAR_EVENT_GROUP);
        } else {
            // This course CANNOT have group events, so lose the filter
        }
        $seturl->param('var', 'showuser');
        $content .= calendar_filter_controls_element($seturl, CALENDAR_EVENT_USER);
    }
    $content .= html_writer::end_tag('ul');

    return $content;
}