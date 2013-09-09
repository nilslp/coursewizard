<?php

$handlers = array (
    'user_created' => array (
        'handlerfile'      => '/local/moderngovernor/lib.php',
        'handlerfunction'  => 'populate_user_info',
        'schedule'         => 'instant'
    ),
    'user_updated' => array (
        'handlerfile'      => '/local/moderngovernor/lib.php',
        'handlerfunction'  => 'populate_user_info',
        'schedule'         => 'instant'
    )
);