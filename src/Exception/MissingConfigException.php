<?php

namespace Drupal\circuit_breaker\Exception;

/**
 * Thrown when No configuration exists for a circuit breaker.
 */
class MissingConfigException extends \Exception {

  /**
   * MissingConfigException constructor.
   *
   * @param string $key
   *   The circuit breaker ID.
   */
  public function __construct($key) {
    parent::__construct("Circuit '$key' is not defined");
  }

}
