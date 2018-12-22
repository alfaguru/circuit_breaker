<?php

namespace Drupal\circuit_breaker\Storage;


interface StorageManagerInterface {

  /**
   * @param string $key
   *
   * @return \Drupal\circuit_breaker\Storage\StorageInterface
   */
  function getStorage($key);
}