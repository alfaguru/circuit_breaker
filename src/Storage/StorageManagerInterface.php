<?php

namespace Drupal\circuit_breaker\Storage;

/**
 * Provides access to storage for circuit breakers.
 */
interface StorageManagerInterface {

  /**
   * Get the storage for a circuit breaker.
   *
   * @param string $key
   *   The circuit breaker ID.
   *
   * @return \Drupal\circuit_breaker\Storage\StorageInterface
   *   Storage for circuit breaker state.
   */
  public function getStorage($key);

}
