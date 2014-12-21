<?php
$cmd = App::exec('which memcached');
App::exec('task add "' . $cmd . ' -l ' . config('memcache.host') . ' -p ' . config('memcache.port') . ' -m ' . config('memcache.size') . ' #memcache"');