<?php
/**
 * Класс для работы с системой постраничной навигации
 *
 * @final
 * @package Core
 * @subpackage Pagination
 */
class Pagination {
  /**
   * @property int $limit Количество элементов на страницу
   * @property int $total Количество всех элементов в списке
   * @property string $page_name Имя переменной параметра запроса, содержащего страницу
   * @property string $route Роут для формирования урл листания страниц
   * @property string $params Параметры для создания урл
   */
  private
  $limit     = 1000,
  $total        = 1000,
  $page_name    = 'p',
  $default_page = 1,
  $route        = '',
  $params       = [];

  private static $Instance = null;

  /**
   * @static
   * @access public
   * @return Pagination
   *
   * <code>
   * $list = Pagination::create([
   *  'limit' => 15,
   *  'total' => 10,
   *  'page_name' => 'p',
   * ])->listResult($items_from_database);
   * </code>
   */
  public static function create(array $conf = []) {
    $Obj = new static;
    foreach ($conf as $k => $v) {
      $Obj->$k = $v;
    }
    if (!$Obj->route) {
      $Obj->route = Request::instance()->getRoute();
    }

    if (!$Obj->params) {
      $Obj->params = Request::instance()->param();
    }

    // Если лимита не указано, ставим максимальным
    if (!$Obj->limit)
      $Obj->limit = $Obj->total;

    $Obj->max_page = $Obj->total ? (int) ceil($Obj->total / $Obj->limit) : 1;

    // Определяем начало для выборки данных
    $Obj->offset = ($Obj->getCurrentPage() - 1) * $Obj->limit;
    return $Obj;
  }

  /**
   * Получение установленного роута
   *
   * @return string
   */
  public function getRoute() {
    return $this->route;
  }

  /**
   * Получение текущей страницы
   *
   * @return int
   */
  public function getCurrentPage() {
    $page = (int) Request::instance()
      ->param($this->page_name, $this->default_page)
    ;
    if ($page < 1) {
      $page = 1;
    }
    return $page > ($last_page = $this->getLastPage()) ? $last_page : $page;
  }

  public function getLimit() {
    return $this->limit;
  }

  public function getTotal() {
    return $this->total;
  }

  /**
   * Получение номера последней страницы
   *
   * @return int
   */
  public function getLastPage() {
    return ($this->total && $this->limit)
      ? (int) ceil($this->total / $this->limit)
      : 1;
  }

  public function setTotal($total) {
    $this->total = $total;
    return $this;
  }

  /**
   * Получение итогового массива страниц для отображения
   *
   * @return array [param_name, current, last, next_url, prev_url, pages[[page, url]]
   */
  public function getArray() {
    $data = [];
    // Если не установлен роут, используем текущий
    if (!$route = $this->getRoute()) {
      $route = Request::instance()->getRoute();
    }
    $cur_page   = $this->getCurrentPage();
    $last_page  = $this->getLastPage();

    $data['has_pages']  = $last_page > 1;
    $data['param_name'] = $this->page_name;
    $data['current']    = $cur_page;
    $data['last']       = $last_page;

    $url = function ($page) {
      return '/' . Request::instance()->getRoute() . '?' . $this->page_name . '=' . $page;
    };

    $data['prev_url'] = '';
    if ($cur_page > 1) {
      //$data['prev_url'] = $url($cur_page - 1);

      $data['prev_url'] = $url($cur_page - 1);
    }

    $data['next_url'] = '';
    if ($cur_page !== $last_page) {
      //$data['next_url'] = $url($cur_page + 1);

      $data['next_url'] = $url($cur_page + 1);
    }

    // @TODO: сформировать страницы
    for ($i = 1; $i <= $last_page; $i++) {
      $data['pages'][] = ['page' => $i, 'url' => $url($i), 'current' => $i === $cur_page];
    }
    return $data;
  }

  /**
   * Проверка возвращаемых данных на предмет унификации
   * Добавление необходимых переменных лимитов и постраничного вывода
   *
   * @access protected
   * @param array $result
   *   Набор данных, которые нужно трансформировать в список
   * @return array
   */
  public function listResult(array $result) {
    // Ничего нет? Ну и к черту :D
    if (!$result)
      return [];

    return [
      'items'   => array_values($result),
      'total'   => $this->total,
      'offset'  => $this->offset,
      'limit'   => $this->limit,
      'page'    => $this->page,
      'max_page'      => $this->max_page,
      'has_items'     => !!$result,
      'has_no_items'  => !$result,
      'pagination' => $this->getArray()
    ];
  }

  /**
   * Необходимая обработка и возврат плоского списка
   *
   * @access protected
   * @param array $flat
   * @return array
   */
  public function flatResult(array $flat) {
    return array_filter($flat);
  }
}