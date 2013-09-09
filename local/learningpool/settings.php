<?php // $Id$

/**
 * Add reportbuilder administration menu settings
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

if ($hassiteconfig) {
    $ADMIN->add('root',
        new admin_category('learningpool_audit_reports', get_string('audit','local_learningpool'))
    );

    // add links to report builder reports
    $ADMIN->add('learningpool_audit_reports',
        new admin_externalpage('auditreports',
            get_string('downloadcompletionreport','local_learningpool'),
            "$CFG->wwwroot/local/learningpool/audit/report.php?type=completion",
            'moodle/site:config'
        )
    );

    $ADMIN->add('root',
        new admin_category('learningpooladmin',
        get_string('learningpooladmin','local_learningpool'))
    );

    // add link to dle configuration page
    $ADMIN->add('learningpooladmin',
        new admin_externalpage('learningpooldleconfiguration',
            get_string('learningpooldleconfiguration','local_learningpool'),
            "$CFG->wwwroot/local/learningpool/configure.php",
            'moodle/site:config'
        )
    );
    
    // postcodelookupconfiguration
    $ADMIN->add('learningpooladmin',
        new admin_externalpage('postcodelookupconfiguration',
            get_string('postcodelookupconfiguration','local_learningpool'),
            "$CFG->wwwroot/local/learningpool/postcodelookup/configure.php",
            'moodle/site:config'
        )
    );
}

// Add link to settings block to change current theme settings
if (file_exists($CFG->dirroot.'/theme/'.$CFG->theme.'/basic-settings.php')) {
    $ADMIN->add('root',
        new admin_externalpage(
            'theme_'.$CFG->theme.'_config',
            get_string('theme_settings','theme_'.$CFG->theme),
            "$CFG->wwwroot/theme/$CFG->theme/basic-settings.php",
            'theme/'.$CFG->theme.':config'
        )
    );
}

$ADMIN->add('reports',
    new admin_category('block_lp_reportbuilder', get_string('reportbuilder','block_lp_reportbuilder'))
);

$ADMIN->add('root',
    new admin_category('workritecategory',
    get_string('workritecategory','local_learningpool'))
);

$ADMIN->add('workritecategory',
    new admin_externalpage('local_workwrite_admin',
        get_string('settings'),
        "$CFG->wwwroot/local/learningpool/workrite/settings.php",
        'moodle/site:config'
    )
);

// add links to report builder reports
$ADMIN->add('block_lp_reportbuilder',
    new admin_externalpage('myreports',
        get_string('myreports','block_lp_reportbuilder'),
        "$CFG->wwwroot/blocks/lp_reportbuilder/myreports.php",
        'block/lp_reportbuilder:viewreports'
    )
);

if (0 != get_config('reportbuilder','showcoursequizquick')) {
   // add links to report builder reports
    $ADMIN->add('block_lp_reportbuilder',
        new admin_externalpage('coursequizquick',
            get_string('coursequizquickheading','block_lp_reportbuilder'),
            "$CFG->wwwroot/blocks/lp_reportbuilder/coursequiz.php",
            'block/lp_reportbuilder:viewreports'
        )
    ); 
}

$ADMIN->add('block_lp_reportbuilder',
    new admin_externalpage('schedulereports',
        get_string('schedulereports','block_lp_reportbuilder'),
        "$CFG->wwwroot/blocks/lp_reportbuilder/schedulereports.php",
        'block/lp_reportbuilder:viewreports'
    )
);

$x = get_config('reportbuilder', 'enableskillschecker');

if (get_config('reportbuilder', 'enableskillschecker') == 1) {
    // Add a link to the skill checker report
    $ADMIN->add('block_lp_reportbuilder',
        new admin_externalpage('skillscheckerreport',
            get_string('skillscheckerreport','block_lp_reportbuilder'),
            "$CFG->wwwroot/blocks/lp_reportbuilder/skillscheckerreport.php",
            'block/lp_reportbuilder:viewreports'
        )
    );
}

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
        array('moodle/site:config')
    )
);

$ADMIN->add('block_lp_reportbuilder',
    new admin_externalpage('reportadminsettings',
        get_string('reportadminconfig', 'block_lp_reportbuilder'),
        "$CFG->wwwroot/blocks/lp_reportbuilder/reportadminsettings.php",
        array('block/lp_reportbuilder:configurepermissions')
    )
);

if (0 != get_config('reportbuilder','showcustomtrentdownload')) {
   // add links to report builder reports
    $ADMIN->add('block_lp_reportbuilder',
        new admin_externalpage('customtrentdownload',
            get_string('customtrentdownload','block_lp_reportbuilder'),
            "$CFG->wwwroot/blocks/lp_reportbuilder/customtrent.php",
            'block/lp_reportbuilder:viewreports'
        )
    ); 
}

$ADMIN->add('users', new admin_externalpage('managehierarchy',
            get_string('manage_hierarchy', 'block_lp_hierarchy'),
            new moodle_url('/blocks/lp_hierarchy/manage_hierarchy.php'),
            'block/lp_hierarchy:manage'));
?>
