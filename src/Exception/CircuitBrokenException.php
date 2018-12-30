<?php

namespace Drupal\circuit_breaker\Exception;

/**
 * Thrown when a circuit is broken.
 */
class CircuitBrokenException extends \Exception {

  /**
   * CircuitBrokenException constructor.
   *
   * @param string $key
   *   The circuit breaker ID.
   */
  public function __construct($key) {
    parent::__construct("Circuit '$key' is open");
  }

}
