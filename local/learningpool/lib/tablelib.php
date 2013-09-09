<?php
/*
*/

require_once($CFG->dirroot.'/lib/tablelib.php');

class lp_flexible_table extends flexible_table {

    /* */
    function start_html() {
        global $OUTPUT;
        // Do we need to print initial bars?
        $this->print_initials_bar();
        
        $url = preg_replace('/(\&?ssort=[^\&.]*)/','',$this->baseurl);
        if ($this->use_pages) {
            $pagingbar = new paging_bar($this->totalrows, $this->currpage, $this->pagesize, $url);
            $pagingbar->pagevar = $this->request[TABLE_VAR_PAGE];
            echo $OUTPUT->render($pagingbar);
        }

        if (in_array(TABLE_P_TOP, $this->showdownloadbuttonsat)) {
            echo $this->download_buttons();
        }

        $this->wrap_html_start();
        // Start of main data table

        echo html_writer::start_tag('div', array('class' => 'no-overflow'));
        echo html_writer::start_tag('table', $this->attributes);

    }
    
    /* */
    function print_headers() {
        global $CFG, $OUTPUT;

        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');
        foreach ($this->columns as $column => $index) {

            $icon_hide = '';
            if ($this->is_collapsible) {
                $icon_hide = $this->show_hide_link($column, $index);
            }

            $primary_sort_column = '';
            $primary_sort_order  = '';
            if (reset($this->sess->sortby)) {
                $primary_sort_column = key($this->sess->sortby);
                $primary_sort_order  = current($this->sess->sortby);
            }

            switch ($column) {

                case 'fullname':
                if ($this->is_sortable($column)) {
                    $firstnamesortlink = $this->sort_link(get_string('firstname'),
                            'firstname', $primary_sort_column === 'firstname', $primary_sort_order);

                    $lastnamesortlink = $this->sort_link(get_string('lastname'),
                            'lastname', $primary_sort_column === 'lastname', $primary_sort_order);

                    $override = new stdClass();
                    $override->firstname = 'firstname';
                    $override->lastname = 'lastname';
                    $fullnamelanguage = get_string('fullnamedisplay', '', $override);

                    if (($CFG->fullnamedisplay == 'firstname lastname') or
                        ($CFG->fullnamedisplay == 'firstname') or
                        ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
                        $this->headers[$index] = $firstnamesortlink . ' / ' . $lastnamesortlink;
                    } else {
                        $this->headers[$index] = $lastnamesortlink . ' / ' . $firstnamesortlink;
                    }
                }
                break;

                case 'userpic':
                    // do nothing, do not display sortable links
                break;

                default:
                if ($this->is_sortable($column)) {
                    $this->headers[$index] = $this->sort_link($this->headers[$index],
                            $column, $primary_sort_column == $column, $primary_sort_order);
                }
            }

            $attributes = array(
                'class' => 'header c' . $index . $this->column_class[$column],
                'scope' => 'col',
            );
            if ($this->headers[$index] === NULL) {
                $content = '&nbsp;';
            } else if (!empty($this->sess->collapse[$column])) {
                $content = $icon_hide;
            } else {
                if (is_array($this->column_style[$column])) {
                    $attributes['style'] = $this->make_styles_string($this->column_style[$column]);
                }
                $content = $this->headers[$index] . html_writer::tag('div',
                        $icon_hide, array('class' => 'commands'));
            }
            echo html_writer::tag('th', $content, $attributes);
        }

        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');
    }
    
    /* */
    function finish_html() {
        global $OUTPUT;
        if (!$this->started_output) {
            //no data has been added to the table.
            $this->print_nothing_to_display();

        } else {
            echo html_writer::end_tag('tbody');
            echo html_writer::end_tag('table');
            echo html_writer::end_tag('div');
            $this->wrap_html_finish();

            // Paging bar
            if(in_array(TABLE_P_BOTTOM, $this->showdownloadbuttonsat)) {
                echo $this->download_buttons();
            }

            $url = preg_replace('/(\&?ssort=[^\&.]*)/','',$this->baseurl);
            if($this->use_pages) {
                $pagingbar = new paging_bar($this->totalrows, $this->currpage, $this->pagesize, $url);
                $pagingbar->pagevar = $this->request[TABLE_VAR_PAGE];
                echo $OUTPUT->render($pagingbar);
            }
        }
    }


}