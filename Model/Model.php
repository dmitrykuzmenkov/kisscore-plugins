<?php
namespace Plugin\Model;
use Plugin\Cache\Cache;
use Plugin\Pagination\Pagination;
use ArrayAccess;
use Closure;
/**
 * Абстрактный класс реализации модели
 *
 * @abstract
 * @package Core
 * @subpackage Model
 *
 * <code>
 * User::create()->save(['name' => 'Dmitry']);
 * </code>
 *
 * <code>
 * User::fetch(1)->save(['name' => 'New name']);
 * </code>
 *
 * <code>
 * User::fetch(1)->delete();
 * </code>
 *
 * <code>
 * echo User::get(1)['name'];
 * </code>
 */
abstract class Model implements ArrayAccess {
  use DatabaseTrait, IdTrait, ArrayTrait;

  /**
   * @property bool $is_new
   *   Флаг, обозначающий является ли текущее данное в модели новым
   */
  protected $is_new = true;

  protected $errors = [];
  protected $is_cacheable = false;

  /**
   * @access protected
   * @property mixed $id
   *   Текущий идентификатор сущности
   */
  protected $id = 0;

  /**
   * @property array $data
   *   Данные текущей выборки
   */
  protected $data   = [];

  /**
   * @property array $map Карта всех моделей
   */
  protected static $map = [];

  // Offsets
  protected $Pagination = null;

  final protected function __construct() {}

  public function setPagination(Pagination $Pagination) {
    $this->Pagination = $Pagination;
    return $this;
  }

  /**
   * Подготовка и постобработка результатов выборки
   *
   * @param array $item
   *   Массив с данными одного элемента
   */
  protected function prepare(array &$item) {}
  protected function onCreate() {}
  protected function onUpdate() {}
  protected function onSave() {}
  protected function onDelete() {}

  /**
   * Правила валидации для необходимых полей
   * Валидирующая функция должна возвращать
   *
   * @access protected
   * @return array
   *
   * <code>
   * return array(
   *   'field1'  => function ($v) {
   *     if ($v === null) return 'ERROR';
   *     return true;
   *   },
   *   'field2' => …
   * );
   * </code>
   */
  protected function rules() {
    return [];
  }

  /**
   * Создание новой записи в базе и возврат объекта
   *
   * @access public
   * @return $this
   */
  public static function create() {
    $Obj = new static;
    $Obj->is_new = true;
    return $Obj;
  }

  /**
   * Метод для обновления каких-то счетчиков в базе
   *
   * @param array $counters
   *   Показатели для обновления + или -, например ['counter' => -1]
   * @param array $ids
   * @return $this
   */
  public function increment(array $counters, array $ids = []) {
    $this->dbUpdateByIds($counters, $ids ?: [$this->getId()], true);
    return $this;
  }

  public static function incrementAll(array $counters) {
    return static::dbQuery(
      'UPDATE ' . static::table() . ' SET ' . self::dbGetSqlStringByParams($counters, ',', true),
      $counters
    );
  }

  /**
   * Сохранение записи
   *
   * @param array $data
   * @return $this
   */
  public function save(array $data) {
    // Не пропускаем к базе возможно установленные левые ключи
    $data = array_intersect_key($data, array_flip(static::fields()));
    $this->data = array_merge($this->data, $data);

    // Ничего на обновление нет?
    if (!$this->data)
      return $this;

    // intersect потому что обработка переменных идет на $this->data, посылам только нужные запросы на сервер
    $data = array_intersect_key($this->data, $data);

    if ($this->validate($data)->errors)
      return $this;

    $saved = false;
    // Валидация прошла успешно, обновляем или вставляем новую запись
    if (!$this->is_new) {
      // Если не нужно обновлять главный ключ
      if (isset($this->data['id']) && $this->id === (string) $this->data['id'])
        unset($this->data['id']);

      $saved = $this->dbUpdateByIds($data, [$this->id]);

      $this->data['id'] = $this->id;

      // Обновим кэш завершающим этапом
      // В кэш обработанные данные через prepare не попадают
      Cache::remove(static::class . ':' . $this->getId());
    } else {
      if (isset($data['id'])) {
        $this->id = (string) $data['id'];
      }
      if (!$this->id)
        $this->id = static::generateId();

      $data['id'] = $this->id;
      $saved = $this->dbInsert($data);

      // Дополняем нулл значениями
      $this->data = array_merge(array_fill_keys(static::fields(), null), $data);
    }

    if ($saved) {
      $this->is_new ? $this->onCreate() : $this->onUpdate();
      $this->onSave();
    }
    $this->is_new = false;

    $this->prepare($this->data);

    return $this;
  }

  /**
   * Удаление текущей редактируемой записи или записей по ID
   *
   * @param array $ids
   * @return int
   *   Число удаленных строк 0/1
   */
  public function delete() {
    $deleted = $this->dbDeleteByIds([$this->id]);

    if ($deleted) {
      $this->onDelete();
      $this->is_new = true;
      $this->data = [];
      $this->id = 0;
    }
    return $deleted;
  }

  public function deleteByIds(array $ids) {
    return $this->dbDeleteByIds($ids);
  }

  /**
   * Удаление по ряду условий (AND)
   *
   * @param array $cond
   * @return int
   */
  public function deleteBy(array $cond = []) {
    return $deleted = $this->dbDelete($cond, 'AND');
  }


  /**
   * Получение текущей ID сущности
   *
   * @access public
   * @return int
   */
  public function getId() {
    return $this->id;
  }

  public function getData() {
    return $this->data;
  }

  /**
   * Получение текущих установленных данных и возвращение ссылки на объект
   *
   * @access public
   * @param int $id
   * @return $this
   */
  public static function get($id) {
    $key = static::class . '-' . $id;
    if (!isset(self::$map[$key])) {
      self::$map[$key] = (new static)->load($id);
    }

    return self::$map[$key];
  }

  /**
   * Fail on emty object after fetching
   */
  public function orFail() {
    if (!$this->getId()) {
      $class = static::class . 'NotFoundException';
      if (!class_exists($class)) {
        $class = NotFoundException::class;
      }
      throw new $class;
    }
    return $this;
  }

  /**
   * Получение нескольких записей по ID
   *
   * @param array $ids
   * @return array
   */
  public static function getByIds(array $ids) {
    $ids = array_unique($ids);

    // Избавляемся от нуль-ид
    if (false !== $key = array_search(0, $ids, true))
      unset($ids[$key]);

    $Obj = new static;
    $data = [];
    $key_ptrn = static::class . ':%s';
    if ($Obj->is_cacheable) {
      foreach ((array) Cache::get(array_map(function ($item) use ($key_ptrn) { return sprintf($key_ptrn, $item); }, $ids)) as $idx => &$val) {
        $data[$ids[$idx]] = $val;
      }

    }

    // Если есть промахи в кэш
    if (($cache_size = sizeof($data)) !== sizeof($ids)) {
      // Вычисляем разницу для подгрузки
      $missed = array_values(
        $cache_size
          ? array_diff(array_values($ids), array_keys($data))
          : $ids
      );

      // Подгружаем только не найденные данные,
      // попутно сортируя в порядке ID
      $result = [];
      $diff   = $missed ? $Obj->dbGetByIds(static::fields(), $missed) : [];

      foreach ($ids as $id) {
        if (isset($diff[$id]))
          Cache::set(sprintf($key_ptrn, $id), $diff[$id]);

        $result[$id] = isset($diff[$id])
          ? $diff[$id]
          : (isset($data[$id]) ? $data[$id] : null);
      }
      $data = &$result;
    }
    $data = array_filter($data);
    array_walk($data, [$Obj, 'prepare']);
    return $data;
  }

  /**
   * Загрузка из базы данных в текущий инстанс объекта
   *
   * @param int $id
   * @return $this
   */
  public function load($id) {
    if ($rows = static::getByIds([$id])) {
      $this->is_new = false;
      $this->id = (string) $id;
      $row = array_shift($rows);
      $this->prepare($row);
      $this->data = $row;
    }
    return $this;
  }


  /**
   * Функция валидации данных
   *
   * @access protected
   * @param array $data
   * @return $this
   *
   * <code>
   * $msgs = Photo::create()->save($form)->getErrors();
   * </code>
   */
  protected function validate($data) {
    foreach ($this->rules() as $field => $rule) {
      if ($this->is_new) { // Если новая запись
        // Еще нет такого поля? Пишем туда нуль и валидируем
        if (!isset($data[$field]))
          $data[$field] = null;
      } else { // Идет обновление
        // Не указано поле? Просто пропускаем правило
        if (!array_key_exists($field, $data))
          continue;
      }

      $res = $rule($data[$field]);

      // Не изменилось поле? удаляем
      if ($data[$field] === null)
        unset($data[$field]);

      // Если результат не TRUE, то там ошибка
      if (isset($res) && true !== $res) {
        $this->addError($field . '_' . $res);
      }
    }
    return $this;
  }

  public function getErrors() {
    return $this->errors;
  }

  protected function addError($error) {
    $this->errors['e_' . strtolower(get_class_name(static::class)) . '_' . $error] = true;
    return $this;
  }

  public function done(&$errors, Closure $callback = null) {
    if (!$errors = $this->getErrors()) {
      $callback && $callback();
    }
    return $this;
  }
}
