<?php
App::configure(__DIR__, [
	'%IP%'	   => '127.0.0.1',
	'%PORT%'   => App::allocatePort('memcache'),
	'%SIZE%'   => config('memcache.size'),
]);