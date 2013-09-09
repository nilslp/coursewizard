<?php

require_once($CFG->dirroot.'/lib/formslib.php');
require_once("lib.php");

class edit_product_form extends moodleform {

    // Define the form
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $product = $this->_customdata['product'];
        $new = $product->id == 0 ? true : false;
        $hascategory = $product->hascategory == 1 ? true : false;
        
        /// Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT); 
        
        $mform->addElement('hidden', 'courseidlist');
        $mform->setType('courseidlist', PARAM_SEQUENCE);
        
        $mform->addElement('hidden', 'categoryidlist');
        $mform->setType('categoryidlist', PARAM_SEQUENCE);

        $header = $new ? get_string('addproduct', 'block_shopping_basket') : get_string('editproduct', 'block_shopping_basket');
        
        $mform->addElement('header', 'editvoucher', $header);
        
        $mform->addElement('text', 'itemcode', get_string('itemcode', 'block_shopping_basket'), array('maxlength' => 100, 'size' => 20, 'style' => 'text-transform: uppercase'));
        $mform->addRule('itemcode', null, 'required', null, 'client');
        $mform->setType('itemcode', PARAM_MULTILANG);
        $mform->addHelpButton('itemcode', 'itemcode', 'block_shopping_basket');
        
        if ($new) {
            // Callback function to ensure that the itemcode is unique
            function validate_item_code($itemcode) {
                if (!empty($itemcode) && !is_itemcode_unique($itemcode)) {
                    return false;
                }
                else {
                    return true;
                }
            }

            $mform->addRule('itemcode', get_string('validationstringalreadyinuse', 'block_shopping_basket', get_string('itemcode', 'block_shopping_basket')), 'callback', 'validate_item_code');
        }
        
        $mform->addElement('text', 'fullname', get_string('productname', 'block_shopping_basket'), 'maxlength="1024" size="50"');
        $mform->addRule('fullname', null, 'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);

        $mform->addElement('htmleditor', 'description', get_string('description'));
        $mform->setType('description', PARAM_CLEAN);

        // Course or Category - Users will only see this when creating a new product
        if($new) {
            $radioarray = array();
            $radioarray[] =& $mform->createElement('radio', 'hascategory', '', get_string('yes'), 1);
            $radioarray[] =& $mform->createElement('radio', 'hascategory', '', get_string('no'), 0);
            $mform->addGroup($radioarray, 'hascategory_grp', get_string('categoryorcourse', 'block_shopping_basket'), array(' '), false);
            $mform->setDefault('hascategory', 0);
            $mform->setType('hascategory', PARAM_INT);
            $mform->addHelpButton('hascategory_grp', 'categoryorcourse', 'block_shopping_basket');    
        } else {
            $mform->addElement('hidden', 'hascategory', $product->hascategory);
            $mform->setType('hascategory', PARAM_INT);
            $mform->setType('id', PARAM_INT);
        }
        
        if($new || $hascategory) {
            // Category selection
            $categories = get_categories("none", "cc.name ASC", "cc.id, cc.name, cc.visible");
            $category_options = array("" => get_string('pleaseselectoption', 'block_shopping_basket'));

            foreach ($categories as $category) {
                $category_options[$category->id] = $category->name;
            }
             // Output associated categories
            if (isset($product->categories)) {
                $category_html = get_category_table_html($product->categories);
            } else {
                $category_html = get_string('nocategoryassociated', 'block_shopping_basket');
            }
            $categoryarray = array();
            $categoryarray[] =  $mform->createElement('select', 'categoryselect', '', $category_options, array('class' => 'multiSelect'));
            $categoryarray[] = $mform->createElement('static', 'addcategory', '', '&nbsp;' . html_writer::empty_tag('input', array('id' => 'add_category', 'name' => 'add_category', 'class' => 'add_button', 'type' => 'button', 'value' => 'Add')));
            $categoryarray[] = $mform->createElement('static', 'y', '', html_writer::tag('div', $category_html, array('id' => 'category_list')));
            $mform->addGroup($categoryarray, 'category_group', get_string('selectcategory', 'block_shopping_basket'), null, false);
            $mform->addHelpButton('category_group', 'selectcategory', 'block_shopping_basket');
            $mform->disabledIf('category_group', 'hascategory', 'eq', 0);
            $mform->disabledIf('add_category', 'hascategory', 'eq', 0);
        }
        
        if($new || !$hascategory) {
            //Course selection
            $courses = get_courses("all", "c.fullname ASC", "c.id, c.fullname, c.visible");      
            $course_options = array("" => get_string('pleaseselectoption', 'block_shopping_basket'));

            foreach ($courses as $course) {
                $course_options[$course->id] = $course->fullname;
            }
            $coursearray = array();
            $coursearray[] =  $mform->createElement('select', 'type', '', $course_options, array('class' => 'multiSelect'));
            $coursearray[] = $mform->createElement('static', 'addcourse', '', '&nbsp;' . html_writer::empty_tag('input', array('id' => 'add_course', 'name' => 'add_course', 'class' => 'add_button', 'type' => 'button', 'value' => 'Add')));
            
            // Output associated courses
            if (isset($product->courses)) {
                $course_html = get_course_table_html($product->courses);
            }
            else {
                $course_html = get_string('nocoursesassociated', 'block_shopping_basket');
            }
            $coursearray[] = $mform->createElement('static', 'x', '', html_writer::tag('div', $course_html, array('id' => 'course_list')));
            
            $mform->addGroup($coursearray, 'course_group', get_string('selectcourse', 'block_shopping_basket'), null, false);
            $mform->addHelpButton('course_group', 'selectcourse', 'block_shopping_basket');
            $mform->disabledIf('course_group', 'hascategory', 'eq', 1);
            $mform->disabledIf('add_course', 'hascategory', 'eq', 1);

        }
        
        $mform->addElement('text', 'cost', get_string('cost', 'block_shopping_basket'));
        $mform->addRule('cost', null, 'required', null, 'client');
        $mform->addHelpButton('cost', 'cost', 'block_shopping_basket');
        $mform->setType('cost', PARAM_FLOAT);
        
        $mform->addElement('text', 'tax', get_string('tax', 'block_shopping_basket'));
        $mform->addHelpButton('tax', 'tax', 'block_shopping_basket');
        $mform->setType('tax', PARAM_FLOAT);
        $mform->setDefault('tax', get_config('block_shopping_basket', 'taxrate'));
        
        $mform->addElement('duration', 'enrolperiod', get_string('duration', 'block_shopping_basket'), array('optional' => false, 'defaultunit' => 86400));
        $mform->addHelpButton('enrolperiod', 'duration', 'block_shopping_basket');
        $mform->addRule('enrolperiod', null, 'required', null, 'client');
        $grprules['number'][] = array(get_string('err_required','form'), 'required', null, 'client');
        $grprules['number'][] = array(get_string('err_numeric','form'), 'numeric', null, 'client');
        $mform->addGroupRule('enrolperiod', $grprules);
        
        $mform->addElement('advcheckbox', 'visible', get_string('visible', 'block_shopping_basket'));
        $mform->setDefault('visible', true);

        if (!$new) {
            $mform->freeze('itemcode');
        }

        $this->add_action_buttons();
    }
    
    function validation($data, $files) {
        $errors = array();
        $errors = parent::validation($data, $files);
        
        // Ensure enrol period has been entered, and is a non-negative number
        if(  $data['enrolperiod'] <= 0 || !is_numeric($data['enrolperiod']) ) {
            $errors['enrolperiod'] = get_string('greaterthanzero', 'block_shopping_basket');
        }
        
        return $errors;
    }
}