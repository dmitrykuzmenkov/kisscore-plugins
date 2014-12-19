<?php
/**
 * Абстрактный класс с базовыми возможностями для объекта управления элементами
 *
 * @abstract
 * @package Core
 * @subpackage ItemManager
 */
abstract class ItemManager {
  /**
    * @property string $model
    * @property string $method
    * @property array $calls
    * @property array $states Состояния моделей, получаемых данные
    * @property array $ids
    * @property array $batch Список задач для выполнения, после выполнений текущей (последовательно)
    * @property array $data Данные, которые используются, или куда пишется результат
    */
  protected
  $model    = '',
  $method   = '',
  $calls    = [],
  $states   = [],
  $ids      = [],
  $batch    = [],
  $data     = null;

  /**
   * Финальный конструктор
   */
  final protected function __construct() {}

  /**
   * Добавление вызова к объекту главного fetcher'а перед получением данных
   *
   * @param string $method
   * @param mixed $args аргументы для передачи
   * @return $this
   */
  public function call($method, $args) {
    $this->calls[] = [$method, is_array($args) ? $args : [$args]];
    return $this;
  }
  

  /**
   * Проверка состояния последних выполненных операций всех моделей
   *
   * @return bool
   */
  public function isOk() {
    static $is_ok = null;
    if (!$is_ok) {
      $is_ok = true;
      foreach ($this->states as $state) {
        $is_ok = $is_ok && $state;
      }
    }
    return $is_ok;
  }
}