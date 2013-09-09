<?php

/*
 * Library functions for audit reports
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(__FILE__)).'/utils.php');

/**
 * Simple filter for appending params to a base link 
 */
class append_filter extends utils_report_filter {
    
    /**
     * The base url
     * @var string
     */
    public $base;
    
    /**
     * constructor
     * @param string $base - base url used in filter
     */
    function __construct($base) {
        $this->base = $base;
    }
    
    /**
     * Implementation of utils_report_filter::filter
     * 
     * @param mixed $args
     * @return mixed 
     */
    public function filter($args) {
        return $this->base.$args;
    }
    
}

