<?php
Env::configure(__DIR__, [
  '%SERVER_NAME%' => config('common.domain'),
]);

App::exec('test ! -f $HTML_DIR/robots.txt && /bin/ln -s $CONFIG_DIR/robots.txt $_');
App::exec('test ! -f $HTML_DIR/sitemap.xml && /bin/ln -s $CONFIG_DIR/sitemap.xml $_');
