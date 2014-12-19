<?php
App::exec('task add "memcached -l ' . config('memcache.host') . ' -p ' . config('memcache.port') . ' -m ' . config('memcache.size') . ' #memcache"');