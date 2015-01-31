<?php
class Async extends Thread {
  protected $closure;
  protected $args = [];
  protected $result;

  protected function __construct(Closure $closure, array $args = []) {
    $this->closure = $closure;
    $this->args    = $args;
  }

  public function run() {
    $closure = $this->closure;
    $this->synchronized(function () use ($closure) {
      $this->result = $closure(...$this->args);
      $this->notify();
    });
  }

  public function getResult() {
    return $this->synchronized(function () {
      while (!$this->result) {
        $this->wait();
      }
      return $this->result;
    });
  }

  public static function exec(Closure $closure, array $args = []) {
    $Async = new static($closure, $args);
    $Async->start();
    return $Async;
  }
}