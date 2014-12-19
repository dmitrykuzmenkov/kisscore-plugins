<?php
/**
 * Получение человеко-читаемой даты согласно текущей локали
 *
 * @param int | string $date
 * @param string $format
 *   Одно из значений date | time | datetime
 * @return string
 */
function hrdate($date, $format = 'datetime') {
  $ts   = is_int($date) ? $date : strtotime($date);
  $now  = $_SERVER['REQUEST_TIME'];
  $diff = $now - $ts;

  // Краткие формы только для даты со временем
  if ($format === 'datetime') {
    // Только что?
    if ($diff <= 60)
      return 'только что';

    // Сколько минут назад
    if ($diff < 3600) {
      $mago = floor($diff / 60);
      return plural($mago, ['минуту', 'минуты', 'минут']) . ' назад';
    }
    // Сколько часов назад
    if ($diff < 86400) {
      $hago = floor($diff / 3600);
      return plural($hago, ['час', 'часа', 'часов']) . ' назад';
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
    $p = plural($dago, ['день', 'дня', 'дней']) . ' назад';
  } else {
    $p = '%e %b' . (/* year? */date('Y') !== date('Y', $ts) ? ' %Y' : '');
  }

  if ($format === 'datetime')
    $p .= ' в %H:%M';

  if ($format === 'time')
    $p = '%H:%M:%S';

  return strtolower(trim(strftime($p, $ts)));
}