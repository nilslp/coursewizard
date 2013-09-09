<?php
/* List of handlers */
$handlers = array (
	'user_created' => array (
        'handlerfile'      => '/local/dlelegacytools/eventhandlers.php',
        'handlerfunction'  => 'dle_user_created',
        'schedule'         => 'instant'
    ),
	'policy_agreed' => array (
        'handlerfile'      => '/local/dlelegacytools/eventhandlers.php',
        'handlerfunction'  => 'dle_user_policy_agreed',
        'schedule'         => 'instant'
    )
);

