<?php
namespace Plugin\Pagination;
use Input;
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
   * @property string $route Роут для формирования урл листания страниц
   * @property string $params Параметры для создания урл
   */
  private
  $limit        = 1000,
  $total        = 0,
  $page         = -1,
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
   *  'page'  => 1,
   *  'limit' => 15,
   *  'total' => 10,
   * ])->listResult($items_from_database);
   * </code>
   */
  public static function create(array $conf = []) {
    $Obj = new static;
    foreach ($conf as $k => $v) {
      $Obj->$k = $v;
    }
    if (!$Obj->route) {
      $Obj->route = Input::get('ROUTE');
    }

    if (!$Obj->params) {
      $Obj->params = Input::get();
    }

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
    $page = $this->page;
    if ($page < 1) {
      $page = 1;
    }
    // Hot fix ffs
    return $page > ($last_page = $this->getLastPage()) ? $last_page : $page;
  }

  public function getMaxPage() {
    return $this->getTotal() ? (int) ceil($this->getTotal() / $this->limit) : 1;
  }

  public function getOffset() {
    return ($this->getCurrentPage() - 1) * $this->getLimit();
  }

  public function getLimit() {
    return $this->limit ?: $this->total;
  }

  public function setTotal($total) {
    $this->total = $total;
    return $this;
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

  /**
   * Получение итогового массива страниц для отображения
   *
   * @return array [param_name, current, last, next_url, prev_url, pages[[page, url]]
   */
  public function getArray() {
    $data = [];
    // Если не установлен роут, используем текущий
    if (!$route = $this->getRoute()) {
      $route = Input::get('ROUTE');
    }
    $cur_page   = $this->getCurrentPage();
    $last_page  = $this->getLastPage();

    $data['has_pages']  = $last_page > 1;
    $data['current']    = $cur_page;
    $data['last']       = $last_page;

    $url = function ($page) {
      return '/' . Input::get('ROUTE') . '?p=' . $page;
    };

    $data['prev_page'] = '';
    if ($cur_page > 1) {
      $data['prev_page'] = $cur_page - 1;
    }

    $data['next_page'] = '';
    if ($cur_page !== $last_page) {
      $data['next_page'] = $cur_page + 1;
    }

    // @TODO: сформировать страницы
    for ($i = 1; $i <= $last_page; $i++) {
      $data['pages'][] = ['page' => $i, 'current' => $i === $cur_page];
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
      'total'   => $this->getTotal(),
      'offset'  => $this->getOffset(),
      'limit'   => $this->getLimit(),
      'page'    => $this->getCurrentPage(),
      'max_page'      => $this->getLastPage(),
      'has_items'     => !!$result,
      'has_no_items'  => !$result,
      'pagination' => $this->getArray()
    ];
  }
}