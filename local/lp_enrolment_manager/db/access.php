<?php

/***
* The capabilities are loaded into the database table when the module is
* installed or updated. Whenever the capability definitions are updated,
* the module version number should be bumped up.
*
* The system has four possible values for a capability:
* CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
*
*
* CAPABILITY NAMING CONVENTION
*
* It is important that capability names are unique. The naming convention
* for capabilities that are specific to modules and blocks is as follows:
*   [mod/block]/<component_name>:<capabilityname>
*
* component_name should be the same as the directory name of the mod or block.
*
* For local modules the naming convention is:
*   local/<component_name>:<capabilityname>
*
* Core moodle capabilities are defined thus:
*    moodle/<capabilityclass>:<capabilityname>
*
* Examples: mod/forum:viewpost
*           block/recent_activity:view
*           moodle/site:deleteuser
*
* The variable name for the capability definitions array follows the format
*   $<componenttype>_<component_name>_capabilities
*
* For the core capabilities, the variable is $moodle_capabilities.
*
* For local modules, the variable is $capabilities in 2.0 and
* $local_<component_name>_capabilities in 1.9.
*/

$capabilities = array(

// can manage access settings
'local/lp_enrolment_manager:enrolusers' => array(
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array('manager' => CAP_ALLOW),
        'legacy' => array('admin' => CAP_ALLOW)
    )
);

?>
