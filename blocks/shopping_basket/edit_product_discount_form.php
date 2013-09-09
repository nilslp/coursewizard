<?php

require_once($CFG->dirroot.'/lib/formslib.php');
require_once("lib.php");

class edit_product_discount_form extends moodleform {

    // Define the form
    function definition() {
        
        // Default number
        $max_tiers = 1;
        
        $mform =& $this->_form;

        $product = $this->_customdata['product'];
        $product_discounts = get_product_discounts($product->id);
        
        if($product_discounts) {
            $max_tiers = count($product_discounts);
        }
        
        $mform->addElement('html', '<div id="product_discount_form">');
        
        /// Add some extra hidden fields
        $mform->addElement('hidden', 'pid', $product->id);
        $mform->setType('pid', PARAM_INT);
        
        $mform->addElement('html', '<br />' . get_string('tierdescription', 'block_shopping_basket'));
        
        $tier_amount_options = array(
            TIER_CRITERIA_PERCENTAGE => '%',
            TIER_CRITERIA_FIXED_AMOUNT => get_config('block_shopping_basket','currency'));
        
        $fields = array();
        $fields[] = &$mform->createElement('hidden', 'id', 0);
        $fields[] = &$mform->createElement('hidden', 'productid', $product->id);
        
        // When qty is between
        $when = get_string('tiertypequantity', 'block_shopping_basket') . '&nbsp;' . get_string('tierbetween', 'block_shopping_basket') . '&nbsp;';
        $fields[] =& $mform->createElement('static', 'qty', null, $when);

        // Min
        $fields[] =& $mform->createElement('text', 'min', '', array('class'=>'min_field', 'maxlength'=>'5', 'size'=>'5'));

        // Max
        $fields[] =& $mform->createElement('static', 'maxlabel', null, 'and');
        $fields[] =& $mform->createElement('text', 'max', '', array('class'=>'max_field', 'maxlength'=>'5', 'size'=>'5'));

        // Rate
        $fields[] =& $mform->createElement('static', '', '', '&nbsp;'.get_string('discountperitem','block_shopping_basket').'&nbsp;is:');
        $fields[] =& $mform->createElement('text', 'rate', '', array('class'=>'rate_field', 'maxlength'=>'5', 'size'=>'5'));

        // Criteria
        $fields[] =& $mform->createElement('select', 'criteria', '', $tier_amount_options, array('class'=>'criteria_field'));

        // Delete
        $fields[] =& $mform->createElement('static', '', '', get_string('tierremove', 'block_shopping_basket'));
        $fields[] =& $mform->createElement('advcheckbox', 'delete', '', '', null, 1);
        
        $fieldgroup =& $mform->createElement('group', 'product_discount_group', '', $fields);
        // Number of discount tiers to repeat
        $add_rows = 1;
        $discountaddtext = ($add_rows == 1) ? 'tieraddsingle' : 'tieraddmulti';
        $this->repeat_elements(array($fieldgroup), $max_tiers, array(), 'num_discounts', 'add_text', $add_rows, get_string($discountaddtext, 'block_shopping_basket'));
        
        $this->add_action_buttons();
        
        $mform->addElement('html', '</div>');
    }
    
    function validation($data, $files) {
        $errors = array();
        $errors = parent::validation($data, $files);
        
        $product = $this->_customdata['product'];
        $number_greater_than_zero = array('rate' => 'Discount');
        $int_greater_than_zero = array('min' => 'Minimum', 'max' => 'Maximum');
        $bands = array();
        foreach ($data['product_discount_group'] as $i => $d) {
            
            if( $d['delete'] == 1 ) {
                // ignore deletions
                continue;
            }
            
            $band = new stdClass();
            $band->start = $d['min'];
            $band->end = $d['max'];
            
            if( $this->overlap( $band, $bands ) ) {
                $errors['product_discount_group['.$i.']'] = get_string('tieroverlaperror', 'block_shopping_basket');
            }
            
            $bands[$i] = $band;
            
            foreach( $int_greater_than_zero as $fid => $flabel ) {
                if( $d[$fid] == '' || $d[$fid] <= 0 || !is_numeric($d[$fid]) || strstr($d[$fid],'.') != false ) {
                    $errors['product_discount_group['.$i.']'] = $flabel.' '.get_string('tierintzeroerror', 'block_shopping_basket');
                }
            }
            
            foreach( $number_greater_than_zero as $fid => $flabel ) {
                if( $d[$fid] == '' || $d[$fid] <= 0 || !is_numeric($d[$fid]) ) {
                    $errors['product_discount_group['.$i.']'] = $flabel.' '.get_string('tierzeroerror', 'block_shopping_basket');
                }
            }
            
            if( $d['min'] > $d['max'] ) {
                $errors['product_discount_group['.$i.']'] = get_string('tierminerror', 'block_shopping_basket');
            }
            
            if( $d['criteria'] == TIER_CRITERIA_PERCENTAGE && $d['rate'] > 100 ) {
                $errors['product_discount_group['.$i.']'] = get_string('tierpercenterror', 'block_shopping_basket');
            }
            
            if( $d['criteria'] == TIER_CRITERIA_FIXED_AMOUNT && $d['rate'] > $product->cost ) {
                $errors['product_discount_group['.$i.']'] =  get_string('tieramounterror', 'block_shopping_basket') . ' (' . $product->cost . ')';
            }
        }
        return $errors;
    }
    
    /**
     * Check for overlapping bands
     * @param type $band
     * @param type $bands
     * @return boolean
     */
    private function overlap($band, $bands) {
        foreach ( $bands as $id => $data ) {
            if ($data->end >= $band->start && $band->end >= $data->start) {
                return true;
            }
        }
        return false;
    }
    
}
