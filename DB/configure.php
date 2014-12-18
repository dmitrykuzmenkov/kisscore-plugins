<?php
App::configure(__DIR__, [
	'%DB_DIR%' => getenv('VAR_DIR') . '/mariadb',
	'%IP%'	   => '127.0.0.1',
	'%PORT%'   => App::allocatePort('mysql'),
	'%INNODB_BUFFER_POOL_SIZE%' => config('mysql.innodb_buffer_pool_size'),
]);