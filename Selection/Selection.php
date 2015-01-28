<?php
namespace Plugin;

class Selection {
  use \TDatabase, \TCache;

  /**
   * @property bool $is_cacheable
   */
  protected $is_cacheable = false;

  /**
   * Первичная инициализация и установка необходимой таблицы для использования
   *
   * @param string $name имя списка для подгрузки
   * @return $this
   */
  public function __construct($name) {
    // Реинициализируем таблицу изначальные данные
    $this->table  = 'selection_' . $name;

    $this
      ->initDatabase()
      ->initCache()
    ;
  }

  /**
   * Формирование списка с единичной выборкой элемента
   *
   * @param mixed $key значение выбранного или сохраненного элемента
   * @return mixed
   */
  public function getSingle($key = null) {
    $list = $this->getAll( );

    // дополняем список полем выборки
    foreach ($list as $k=>$v) {
      $list[$k]['selected'] = ($key == $v['key'] ? true : false);
      $list[$k]['disabled'] = false;
    }
    return $list;
  }

  /**
   * Получение списка в нужном формате по указанным данным
   *
   * @param array $data
   * @param string $key_name
   * @param mixed $key_value
   * @param array $disabled_ids
   * @return array
   */
  public static function getSingleByData($data, $key_name = null, $key_value = null, $disabled = array( )) {
    foreach ($data as $k=>$item) {
      $data[$k]['key']      = $item[$key_name];
      $data[$k]['selected'] = ($key_value == $item[$key_name]) ? true : false;
      $data[$k]['disabled'] =  in_array($item[$key_name], $disabled) ? true : false;
    }
    return $data;
  }

  /**
   * Получение названия элемента списка по значению
   *
   * @param mixed $$key
   * @return array
   */
  public function getSingleItem($key) {
    $list = $this->getAll( );
    $keys = array_column($list, 'key');
    return $list[array_search($key, $keys)];
  }

  /**
   * Получение названий элементов по нескольким значениям
   *
   * @param array $keys
   * @return array
   */
  public function getSingleItems($keys) {
    $list = $this->getAll( );
    $map = array_flip(array_column($list, 'key'));
    $keys = array_flip($keys);
    $result = array( );
    foreach ($map as $key => $index) {
      if (isset($keys[$key])) {
        $result[] = $list[$index];
      }
    }
    return $result;
  }

  /**
   * Получение запакованного чесла из переданного массива формы чекбоксов
   *
   * @param array $items массив вида array(2 => 1, 5 => 1), ключами где являются значения, а значения просто флагами выборки 1 или 0
   * @return int результат упаковки выборки в число
   */
  public function getMultipleKeyByArray($items) {
    if (!is_array($items) || !$items) {
      return 0;
    }
    $ret = 0;
    foreach ($items as $key=>$checked) {
      $ret += $checked ? pow(2, $key - 1) : 0;
    }
    return $ret;
  }

  /**
   * Распаковка числа в массив значений
   *
   * @param int $key
   * @return array
   */
  public function getMultipleArrayByKey($key) {
    $bin = strrev((string) decbin($key));
    $checked = array();
    for ($i = 0, $max = strlen($bin); $i < $max; ++$i) {
      if ($bin[$i]) {
        $checked[] = $i + 1;
      }
    }
    return $checked;
  }

  /**
   * Формирование списка с множественной выборкой (checkbox)
   * Список поддерживает до 32х элементов
   *
   * @param int $key значение, содержащие выборку списка
   * @return array
   */
  public function getMultiple($key = null) {
    //$list = $this->get($this->getFields( ), null, array('pos' => 'asc'));
    $list = $this->getAll( );
    // дополняем список полем выборки
    foreach ($list as $k=>$v) {
      $list[$k]['checked'] = ($key & pow(2, $v['key'] - 1) ? true : false);
    }
    return $list;
  }

  /**
   * Получение названия всех элементов множественного списка по значению
   *
   * @param array $key
   * @return int запакованные ид элементов
   */
  public function getMultipleItem($key) {
    if (!$key)
      return array();

    $list = $this->getAll( );
    $map = array_flip(array_column($list, 'key'));
    $result = array( );
    foreach ($map as $k => $item) {
      if (isset($map[$k]) && (2 << $list[$map[$k]]['key'] - 2) & $key) {
        $result[] = $list[$map[$k]];
      }
    }
    return $result;
  }

  /**
   * Получение списка из кэша и/или базы в случае отсутствия
   *
   * @param array
   */
  private function getAll( ) {
    static $stack = null;
    $id = 'selection:' . crc32($this->table);

    // Если есть в локальном стэке
    if (isset($stack[$id])) {
      return $stack[$id];
    }

    return $this->getCacheable($id, function () use($stack, $id) {
      $rows       = $this->dbSelect($this->fields, [], ['pos' => 'ASC']);
      $stack[$id] = $rows;
      return $rows;
    });
  }
}