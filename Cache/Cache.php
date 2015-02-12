<?php
/**
* Класс реализации методов для кэширования объектов в памяти
* Представляет из себя wrapper класса Memcached
*
* @uses \Memcached
* @link http://www.php.net/manual/en/class.memcache.php
*
* @final
* @package Core
* @subpackage Cache
*/
class Cache {
  final protected function __construct() {}

  /**
   * Подключение к серверну мемкэша
   * @return Memcached
   */
  protected static function connect() {
    static $Con;
    if (!$Con) {
      $Con = new Memcached;
      $Con->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
      Memcached::HAVE_JSON && $Con->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_JSON);
      $Con->setOption(Memcached::OPT_COMPRESSION, true);
      $Con->setOption(Memcached::OPT_CONNECT_TIMEOUT, 50);
      $Con->setOption(Memcached::OPT_RETRY_TIMEOUT, 50);
      $Con->setOption(Memcached::OPT_SEND_TIMEOUT, 50);
      $Con->setOption(Memcached::OPT_RECV_TIMEOUT, 50);
      $Con->setOption(Memcached::OPT_POLL_TIMEOUT, 50);
      if (!$Con->addServer(config('memcache.host'), config('memcache.port'))) {
        App::error('Ошибка при попытке подключения к серверу кэша в оперативной памяти.');
      }
    }

    return $Con;
  }

  /**
   * Получение данных из кэша по ключу
   *
   * @param mixed $key
   * @param mixed $default Closure | mixed если это замыкание, то кэш записвыается
   * @return mixed кэшированное данное
   */
  public static function get($key, $default = null) {
    $items = static::doCommand(is_string($key) ? 'get' : 'getMulti', [$key]);

    // Если массив, то нужно выполнить преобразования для возвращаемых данных
    if (is_array($key)) {
      // Если возникла ошибка или же просто нет данных, то возвращаем массив
      // Т.к. было запрошен кэш по нескольким ключам
      if (!$items) {
        $items = [];
      } else {
        $map = array_flip($key); unset($key);
        //$result = new SplFixedArray(sizeof($items));
        foreach ($items as $k => $item) {
          $result[$map[$k]] = $item;
        }
        unset($items);
        $items = &$result;
      }
    }
    if (is_callable($default)) {
      $default = $default();
      if (is_string($key)) {
        static::set($key, $default);
      }
    }
    return $items ?: $default;
  }

  public static function getCas($key) {
    return static::doCommand('getCas', [$key]);
  }

  public static function setWithCas($key, $val, $token) {
    return static::doCommand('setWithCas', [$key, $val, $token]);
  }

  /**
   * Установка данные для ключа, перезапись в случае нахождения
   *
   * @param mixed $key Массив или строка
   * @param mixed $val
   * @param int $ttl
   * @return mixed Булевый тип или же массив с булевыми значениями для всех ключей
   */
  public static function set($key, $val, $ttl = 0) {
    assert("is_string(\$key) || is_array(\$key)");
    assert("is_int(\$ttl)");

    $ret = false;
    // Если нужно выполнить multiset
    if (is_array($key)) {
      $args = func_get_args();
      $ttl = $args[1];
      $ret = [];
      foreach ($args as $key => $val) {
        $ret[] = static::doCommand('setMulti', [
          $key,
          $val // Выступает в качестве $ttl
        ]);
      }
    } else {
      $ret = static::doCommand('set', [$key, $val, $ttl]);
    }
    return $ret;
  }

  /**
   * Добавление данных в кэш, если их там нет
   *
   * @param string $key
   * @param mixed $val данные для добавления в кэш
   *  @param int $ttl время жизни кэшируемого объекта
   * @return bool
   */
  public static function add($key, $val, $ttl = 0) {
    return static::doCommand('add', [$key, $val, $ttl]);
  }

  /**
  * Добавление какого-то текста к данному в конец строки
  *
  * @param string $key
  * @param string $val
  * @return bool
  */
  public static function append($key, $val) {
    return static::doCommand('append', [$key, $val]);
  }

  /**
   * Добавление какого-то текста к данному в начало строки
   *
   * @param string $key
   * @param string $val
   * @return bool
   */
  public static function prepend($key, $val) {
    return static::doCommand('prepend', [$key, $val]);
  }

  /**
   * Удаление данного по ключу из кэша
   *
   * @param string $key
   * @return bool
   */
  public static function delete($key) {
    return static::remove($key);
  }

  /**
   * Алиас для функции удаления
   *
   * @see self::delete()
   */
  public static function remove($key) {
    return static::doCommand('delete', [$key]);
  }

  /**
   * Увеличения счетчика на n число раз
   * Если ключа нет, он создается
   *
   * @param string $key
   * @param int $count количество, на которое необходимо увеличить счетчик
   * @return mixed Новое значение с учетом увеличения или FALSE
   */
  public static function increment($key, $count = 1) {
    if (false === $result = static::doCommand('increment', [$key, $count])) {
      static::set($key, $count);
      return $count;
    }
    return $result;
  }

  /**
   * Уменьшение счетчика на n число раз
   *
   * @see self::increment()
   */
  public static function decrement($key, $count = 1) {
    return static::increment($key, -$count);
  }

  /**
   * Выполнение комманды к серверу
   *
   * @param string $command
   * @param array $data
   * @return mixed
   */
  protected static function doCommand($command, array $data = []) {
    return call_user_func_array([static::connect(), $command], $data);
  }

  /**
   * Очистка всего пула кэша
   * @return bool
   */
  public static function flush() {
    return static::doCommand('flush');
  }
}