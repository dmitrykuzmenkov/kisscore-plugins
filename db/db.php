<?php
/**
 * Выполнение запроса к базе данных, выполняет коннект на запросе
 * 
 * @param string $query
 * @param array $params
 * @param int $shard_id
 * @throws Exception
 */
function db($query, array $params = [], $shard_id = 0) {
  assert("is_string(\$query)");
  assert("\is_array(\$params)");
  assert("is_int(\$shard_id)");
  assert("\$shard_id >= 0 && \$shard_id < 4096 /* only 4096 shards allowed */");
  
  static $time = 0;
  static $pool = [];
  static $shards = [];

  if (!$shards) {
    $shards = config('mysql.shard');
  }

  if (!$pool) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  }

  if (!isset($shards[$shard_id])) {
    trigger_error('No shards for mysql server specified');
  }

  // Если на этом шарде еще коннетка нет
  if (!isset($pool[$shard_id])) {
    $dsn = &$shards[$shard_id];
    $dsn_key = function ($key) use($dsn) {
      preg_match("|$key=([^;]+)|", $dsn, $m);
      return $m ? $m[1] : null;
    };

    $DB = &$pool[$shard_id];

    $DB = new mysqli($dsn_key('host'), $dsn_key('user'), $dsn_key('password'), $dsn_key('dbname'), $dsn_key('port'));
  }

  $DB = &$pool[$shard_id];
  if (range(0, sizeof($params) - 1) === array_keys($params)) {
    $query = preg_replace_callback('|\?|', function () { static $count = 0; return ':' . $count++; }, $query);
  }

  $params = array_combine(
    array_map(function ($k) { return ':' . $k; }, array_keys($params)),
    array_map(
      function ($item) use ($DB) { 
        return filter_var($item, FILTER_VALIDATE_INT | FILTER_VALIDATE_FLOAT | FILTER_VALIDATE_BOOLEAN) ? $item : '"' . $DB->real_escape_string($item) . '"';
      },
      $params
    )
  );
  $Result = $DB->query(strtr($query, $params));

  // Определяем результат работы функции в зависимости от типа запроса к базе
  switch (strtolower(strtok($query, ' '))) {
    case 'insert':
      return $DB->insert_id;
      break;

    case 'update':
    case 'delete':
      return $DB->affected_rows;
      break;

    case 'select':
    case 'describe':
      $result = $Result->fetch_all(MYSQLI_ASSOC);
      $Result->close();
      return $result;
      break;

    default:
      trigger_error('Undefined call for database query');
  }
}