<?php
/**
 * This file is called by the validation on the hierarchytext field
 * when buyways is switched on. 
 */
require_once(dirname(__FILE__) .  '/../../config.php');
require_once($CFG->dirroot.'/blocks/lp_hierarchy/lib.php');

$hier = optional_param('hier', '0', PARAM_ALPHANUM); // The selected hierarchy level
$sid  = optional_param('sid', '0', PARAM_ALPHANUM);   // The entered school id

$result = array('message'=>'','ret'=>'');

$hierarchy= Hierarchy::get_instance();

if(intval($hier,10)<1){
    $result['message']  =get_string('buyways_checking_error_nolvl', 'block_lp_hierarchy', $hierarchy->get_hierarchy_field_label_text(false));
    $result['ret']      =false;
}else if(preg_match('/(^[0-9]{4}$)/', $sid) !== 1){
    $result['message']  =get_string('buyways_checking_error_nosid', 'block_lp_hierarchy');
    $result['ret']      =false;    
}else{

    $shier=$DB->get_field('lp_hierarchy','oldid',array('id'=>$hier));
    if($shier !== false){
        $check_lvl = $DB->get_field('lp_hierarchy','id',array('oldid'=>$shier . $sid));
        if($check_lvl === false){
            $result['message'] =get_string('buyways_checking_error_nomch', 'block_lp_hierarchy', $hierarchy->get_hierarchy_field_label_text(false));
            $result['ret']=false;
        }else{
            $result['message'] ='';
            $result['ret']=true;
        }
        
    }else{
        $result['message'] =get_string('buyways_checking_error_prob', 'block_lp_hierarchy');
        $result['ret']=false;
    }
}

echo json_encode($result);
flush();

?>
