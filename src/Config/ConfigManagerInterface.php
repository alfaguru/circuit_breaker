<?php

namespace Drupal\circuit_breaker\Config;

/**
 * Interface to manager for configuration parameters.
 */
interface ConfigManagerInterface {

  /**
   * Get configuration parameters for a circuit breaker.
   *
   * @param string $key
   *   The circuit breaker ID.
   *
   * @return array
   *   Configuration parameters.
   *
   * @throws \Drupal\circuit_breaker\Exception\MissingConfigException
   */
  public function get($key);

}
