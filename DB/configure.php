<?php
App::configure(__DIR__, [
	'%DB_DIR%' => config('mysql.data_dir'),
	'%HOST%'   => config('mysql.host'),
	'%IP%'	   => config('mysql.ip'),
	'%PORT%'   => config('mysql.port'),
	'%USER%'	 => config('mysql.user'),
	'%PASSWORD%' => config('mysql.password'),
	'%INNODB_BUFFER_POOL_SIZE%' => config('mysql.innodb_buffer_pool_size'),
]);

App::exec('task add "mysqld_safe --defaults-file=$CONFIG_DIR/mariadb.cnf #mysql"');