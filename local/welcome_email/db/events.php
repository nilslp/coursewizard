<?php

$handlers = array (
	'user_created' => array (
        'handlerfile'      => '/local/welcome_email/lib.php',
        'handlerfunction'  => 'local_welcome_email_usercreated',
        'schedule'         => 'instant'
    )
);