<?php

namespace Drupal\circuit_breaker\Services;


interface StorageManagerInterface {

  /**
   * @param string $key
   *
   * @return \Drupal\circuit_breaker\Services\StorageInterface
   */
  function getStorage($key);
}