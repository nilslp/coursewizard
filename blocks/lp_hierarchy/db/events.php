<?php 

/* List of handlers */
$handlers = array (
    'user_created' => array (
        'handlerfile'      => '/blocks/lp_hierarchy/eventhandler.php',
        'handlerfunction'  => 'hierarchy_updated',
        'schedule'         => 'instant'
    ),
    'user_updated' => array (
        'handlerfile'      => '/blocks/lp_hierarchy/eventhandler.php',
        'handlerfunction'  => 'hierarchy_updated',
        'schedule'         => 'instant'
    ),
);