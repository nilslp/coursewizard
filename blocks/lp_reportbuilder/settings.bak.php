<?php // $Id$

/**
 * Add reportbuilder administration menu settings
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports',
    new admin_category('block_lp_reportbuilder', get_string('reportbuilder','block_lp_reportbuilder'))
);

// add links to report builder reports
$ADMIN->add('block_lp_reportbuilder',
    new admin_externalpage('myreports',
        get_string('myreports','block_lp_reportbuilder'),
        "$CFG->wwwroot/blocks/lp_reportbuilder/myreports.php",
        'block/lp_reportbuilder:viewreports'
    )
);

// add links to report builder reports
$ADMIN->add('block_lp_reportbuilder',
    new admin_externalpage('managelearningpoolreports',
        get_string('managereports','block_lp_reportbuilder'),
        "$CFG->wwwroot/blocks/lp_reportbuilder/index.php",
        array('block/lp_reportbuilder:managereports')
    )
);

$ADMIN->add('block_lp_reportbuilder',
    new admin_externalpage('globalreportsettings',
        get_string('globalsettings','block_lp_reportbuilder'),
        "$CFG->wwwroot/blocks/lp_reportbuilder/globalsettings.php",
        array('block/lp_reportbuilder:managereports')
    )
);

$ADMIN->add('block_lp_reportbuilder',
    new admin_externalpage('reportadminsettings',
        get_string('reportadminconfig', 'block_lp_reportbuilder'),
        "$CFG->wwwroot/blocks/lp_reportbuilder/reportadminsettings.php",
        array('block/lp_reportbuilder:managereports')
    )
);

?>
