<?php
Env::configure(__DIR__, [
  '%SERVER_NAME%' => config('common.domain'),
]);
