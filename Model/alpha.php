<?php
define('ALPHA_CHARS', '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');

/**
 * Кодирование числа по алфавиту
 *
 * @param int $val
 * @param string $alpha
 * @return string
 */
function alpha_encode($val, $alpha = ALPHA_CHARS) {
  if ($val > PHP_INT_MAX)
    return false;

  $base = strlen($alpha);
  $str  = '';
  do {
    $i   = $val % $base;
    $str = $alpha[$i] . $str;
    $val = ($val - $i) / $base;
  } while ($val > 0);
  return $str;
}

/**
 * Декодирование числа
 *
 * @param string $val
 * @param string $alpha
 * @return int
 */
function alpha_decode($val, $alpha = ALPHA_CHARS) {
  $base = strlen($alpha);
  $len = strlen($val);
  $num = 0;
  $arr = array_flip(str_split($alpha));
  for($i = 0; $i < $len; ++$i) {
    $num += $arr[$val[$i]] * pow($base, $len-$i-1);
  }
  return $num;
}
