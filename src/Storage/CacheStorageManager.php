<?php

namespace Drupal\circuit_breaker\Storage;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Circuit breaker storage using Drupal cache.
 */
class CacheStorageManager implements StorageManagerInterface {

  /**
   * Interface to Drupal cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Construct a storage manager instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache interface.
   */
  public function __construct(CacheBackendInterface $cacheBackend) {
    $this->cacheBackend = $cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage($key) {
    $data = $this->cacheBackend->get($key);
    return new CacheStorage($key, $this->cacheBackend, $data);
  }

}
