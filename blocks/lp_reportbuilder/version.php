<?php

$plugin->version  = 2013071800;   // The (date) version of this module
// This won't work yet as there is no local_modules table
// To activate cron set the config variable local_<mod>_cron with set_config()
// in local/<mod>/db/upgrade.php
$plugin->cron     = 300; // how many seconds between cron updates

