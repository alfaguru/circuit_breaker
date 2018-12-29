<?php

namespace Drupal\circuit_breaker\Storage;


use Drupal\Core\Cache\CacheBackendInterface;

class CacheStorageManager implements StorageManagerInterface {

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  function __construct(CacheBackendInterface $cacheBackend) {
    $this->cacheBackend = $cacheBackend;
  }

  function getStorage($key) {
    $data = $this->cacheBackend->get($key);
    return new CacheStorage($key, $this->cacheBackend, $data);
  }

}