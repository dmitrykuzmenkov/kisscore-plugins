<?php
namespace Plugin\Async;
use Closure;
// Still buggy. Use only for simple calls without dependencies on core
class Async extends Thread {
  protected $closure;
  protected $args = [];
  protected $result;
  protected $processed = false;
  protected $error = [];

  protected function __construct(Closure $closure, array $args = []) {
    $this->closure = $closure;
    $this->args    = $args;
  }

  public function run() {
    $this->synchronized(function ($closure) {
      try {
        $this->result = $closure(...$this->args);
      } catch (Exception $E) {
        $this->error = ['message' => $E->getMessage(), 'trace' => $E->getTraceAsString()];
      } finally {
        $this->processed = true;
        $this->notify();
      }
    }, $this->closure);
  }

  public function getResult() {
    return $this->synchronized(function () {
      while (!$this->processed) {
        $this->wait();
      }
      if ($this->error) {
        App::log($this->error['message'], ['trace' => $this->error['trace']]);
        trigger_error('Error during thread process');
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
