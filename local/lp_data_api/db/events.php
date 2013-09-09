<?php

$handlers = array (
	'user_created' => array (
                            'handlerfile'      => '/local/lp_data_api/event_handler.php',
                            'handlerfunction'  => 'map_user_created',
                            'schedule'         => 'instant'
                        ),
        'user_updated' => array (
                            'handlerfile'      => '/local/lp_data_api/event_handler.php',
                            'handlerfunction'  => 'map_user_updated',
                            'schedule'         => 'instant'
                        )
);

?>