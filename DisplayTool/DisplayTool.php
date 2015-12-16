<?php
namespace Plugin\DisplayTool;
class DisplayTool {
  /**
   * Получение человеко-читаемой даты согласно текущей локали
   *
   * @param int | string $date
   * @param string $format
   *   Одно из значений date | time | datetime
   * @return string
   */
  public static function hrdate($date, $format = 'datetime') {
    $ts   = is_int($date) ? $date : strtotime($date);
    $now  = time();
    $diff = $now - $ts;

    // Краткие формы только для даты со временем
    if ($format === 'datetime') {
      // Только что?
      if ($diff <= 60)
        return 'только что';

      // Сколько минут назад
      if ($diff < 3600) {
        $mago = floor($diff / 60);
        return static::plural($mago, ['минуту', 'минуты', 'минут']) . ' назад';
      }
      // Сколько часов назад
      if ($diff < 86400) {
        $hago = floor($diff / 3600);
        return static::plural($hago, ['час', 'часа', 'часов']) . ' назад';
      }
    }

    // Сколько дней назад
    $d = date('d', $ts);
    // Общая схема
    if (date('d') === $d) {
      $p = 'сегодня';
    } else if (date('d', strtotime('yesterday')) === $d) {
      $p = 'вчера';
    } else if ($diff < 604800) { // 7 days
      $dago = floor($diff / 86400);
      $p = static::plural($dago, ['день', 'дня', 'дней']) . ' назад';
    } else {
      $p = '%e %b' . (/* year? */date('Y') !== date('Y', $ts) ? ' %Y' : '');
    }

    if ($format === 'datetime')
      $p .= ' в %H:%M';

    if ($format === 'time')
      $p = '%H:%M:%S';

    return strtolower(trim(strftime($p, $ts)));
  }

  /**
   * Функция для склонения сущ.
   *
   * @param int $n
   * @param array $forms
   *   Нулевая форма, используемая при отсутсвии количества
   *   Если задана нулева форма в виде строки, то именно эта строка и возвращается при нуле
   * @return string
   *
   * <code>
   * echo plural(1, ['Комментарий', 'Комментария', 'Комментариев', 'nil' => 'Комментариев нет']);
   * </code>
   */
  public static function plural($n, array $forms = []) {
    $n = (int) $n;
    // Если нужно сразу же вернуть нулевую форму, ничего не определяя
    if (isset($forms['nil']) && !$n)
      return $forms['nil'];

    // Определяем форму и возвращаем результат
    if (!isset($forms[1]))
      $forms[1] = &$forms[0];

    if (!isset($forms[2]))
      $forms[2] = &$forms[1];

    return ((string) $n . ' ')
      . (($n % 10 === 1 && $n % 100 !== 11)
        ? $forms[0]
        : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)
          ? $forms[1]
          : $forms[2]
          )
        )
    ;
  }
}
