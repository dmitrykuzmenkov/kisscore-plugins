<?php
Env::configure(__DIR__, [
  '%SERVER_NAME%' => config('common.domain'),
]);

App::exec('/bin/rm -f $HTML_DIR/robots.txt 2> /dev/null && /bin/ln -s $CONFIG_DIR/robots.txt $_');
App::exec('/bin/rm -f $HTML_DIR/sitemap.xml 2> /dev/null && /bin/ln -s $CONFIG_DIR/sitemap.xml $_');
