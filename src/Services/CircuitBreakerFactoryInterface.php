<?php

namespace Drupal\circuit_breaker\Services;


interface CircuitBreakerFactoryInterface {

  /**
   * Create or retrieve a circuit breaker by key
   *
   * @param $key
   *
   * @return CircuitBreakerInterface
   */
  public function load($key);

}