<?php
App::configure(__DIR__, [
	'%DB_DIR%' => config('mysql.data_dir'),
	'%HOST%'   => config('mysql.host'),
	'%IP%'	   => config('mysql.host'),
	'%PORT%'   => config('mysql.port'),
	'%USER%'	 => config('mysql.user'),
	'%PASSWORD%' => config('mysql.password'),
	'%INNODB_BUFFER_POOL_SIZE%' => config('mysql.innodb_buffer_pool_size'),
	'%INNODB_BUFFER_POOL_INSTANCES%' => config('mysql.innodb_buffer_pool_instances'),
]);

$cmd = App::exec('which mysqld_safe');
App::exec('task add "' . $cmd . ' --defaults-file=$CONFIG_DIR/mariadb.cnf #mysql"');