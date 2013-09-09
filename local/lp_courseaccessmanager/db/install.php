<?php

function xmldb_local_lp_courseaccessmanager_install() {
    // Default configs
    set_config('defaultview', 'show', 'local/lp_courseaccessmanager');
    return true;
}
