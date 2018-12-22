<?php

namespace Drupal\circuit_breaker\Exception;

class CircuitBrokenException extends \Exception {

  /**
   * CircuitBrokenException constructor.
   */
  public function __construct($key) {
    parent::__construct("Circuit $key is open");
  }
}