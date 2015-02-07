<?php
class Validate {
  /**
   * Функция валидации данных
   *
   * @access protected
   * @param array $data
   * @return $this
   *
   * <code>
   * $msgs = Model::create('Photo')->save($form)->getMessages();
   * </code>
   */
  protected function validate($data) {
    foreach ($this->rules( ) as $field => $rule) {
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
}