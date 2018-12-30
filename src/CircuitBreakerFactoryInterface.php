<?php

namespace Drupal\circuit_breaker;

/**
 * Factory to create circuit breaker instances.
 */
interface CircuitBreakerFactoryInterface {

  /**
   * Create or retrieve a circuit breaker by key.
   *
   * @param string $key
   *   Circuit breaker ID.
   *
   * @return CircuitBreakerInterface|null
   *   The circuit breaker.
   */
  public function load($key);

}
