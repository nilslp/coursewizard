<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

define('POSTCODELOOKUP_FIELD_PREFIX', 'id_profile_field_');
define('POSTCODELOOKUP_FUNC_GETADDRESS', 'getAddress');

function learningpool_postcode_get_config() {
    $cfg = get_config('learningpool/postcodelookup');
    
    // check that we have filled out our lookup details
    if (empty($cfg->account) || empty($cfg->password) || empty($cfg->serviceurl)) {
        return false;
    }
    
    // prepare the main url - strip trailing slashes
    $cfg->serviceurl = preg_replace('/\/$/', '', $cfg->serviceurl);
    
    return $cfg;
}

function learningpool_postcode_query($postcode) {
    global $DB, $CFG;

    $cfg = learningpool_postcode_get_config();
    $result = array('success' => false, 'msg' => '');

    if (!$cfg) {
        $result['msg'] = get_string('error:postcodelookupauthdetailsincomplete', 'local_learningpool');
        return $result;
    }

    // bully, we can do a lookup
    //$xml = simplexml_load_file(dirname(__FILE__) . '/testdata.xml');
    $url = $cfg->serviceurl . '/' . POSTCODELOOKUP_FUNC_GETADDRESS . '?account=' . $cfg->account . '&password=' . $cfg->password . '&postcode=' . $postcode;
    $xml = simplexml_load_file(str_replace(' ', '', $url)); // Removes unnecessary spaces

    if ($xml->ErrorNumber != 0) { // If an error has occured show message
        if ($CFG->debug == DEBUG_DEVELOPER || $xml->ErrorNumber == 3) { // show extra info when appropriate
            $result['msg'] = (string)$xml->ErrorMessage;
        } else {
            $result['msg'] = get_string('error:generic', 'local_learningpool');
        }
        return $result;
    } else {
        $result['options'] = array();
        $chunks = explode(";", $xml->PremiseData); // Splits up premise data
        if (!empty($chunks)) {
            foreach ($chunks as $v) {
                if (!empty($v)) {
                    list($organisation, $building, $number) = explode('|', $v); // Splits premises into organisation, building and number
                    $option = "";
                    if (!empty($organisation)) {
                        $option .= $organisation . ", ";
                    } else if (!empty($building)) {
                        $option .= str_replace("/", ", ", $building) . ", ";
                    } else if (!empty($number)) {
                        $option .= $number . " ";
                    }
                    $option .= $xml->Address1;
                    $result['options'] [] = $option;
                }
            }
        }

        $result['address2'] = (string)$xml->Address2;
        $result['address3'] = (string)$xml->Address3;
        $result['address4'] = (string)$xml->Address4;
        $result['town'] = (string)$xml->Town;
        $result['country'] = (string)$xml->Country;
        $result['postcode'] = (string)$xml->Postcode;
        $result['success'] = true;
    }

    return $result;
}

function learningpool_postcode_js() {
    global $DB, $CFG, $PAGE;

    $cfg = learningpool_postcode_get_config();
    if (!$cfg || !(int) $cfg->usepostcodelookup) {
        return;
    }
    
    $jsconfig = array(
        'name' => 'local_learningpool_postcodelookup',
        'fullpath' => '/local/learningpool/postcodelookup/lookup.js',
        'requires' => array(
            'node',
            'event',
            'selector-css3',
            'io',
            'json-encode',
            'json',
            'panel'
        )
    );
    
    $fields = array(
        'address1'  => POSTCODELOOKUP_FIELD_PREFIX . $cfg->field_address1,
        'address2'  => POSTCODELOOKUP_FIELD_PREFIX . $cfg->field_address2,
        'address3'  => POSTCODELOOKUP_FIELD_PREFIX . $cfg->field_address3,
        'address4'  => POSTCODELOOKUP_FIELD_PREFIX . $cfg->field_address4,
        'postcode'  => POSTCODELOOKUP_FIELD_PREFIX . $cfg->field_postcode,
        'town'      => POSTCODELOOKUP_FIELD_PREFIX . $cfg->field_town,
        'debug'     => ($CFG->debug === DEBUG_DEVELOPER),
        'lookupurl' => $CFG->wwwroot . '/local/learningpool/postcodelookup/lookup.php'
    );

    $PAGE->requires->strings_for_js(array('ok', 'cancel'), 'moodle');
    $PAGE->requires->strings_for_js(array('findaddress', 'chooseanaddress', 'error:mustprovidepostcode', 'error:generic'), 'local_learningpool');
    $PAGE->requires->js_init_call('M.local_learningpool_postcodelookup.init', array($fields), false, $jsconfig);
}