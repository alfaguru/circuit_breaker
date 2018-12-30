<?php

namespace Drupal\circuit_breaker\Storage;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Persistent state for a circuit breaker using Drupal's cache.
 */
class CacheStorage implements StorageInterface {

  /**
   * The circuit breaker ID.
   *
   * @var string
   */
  protected $key;

  /**
   * Cache data.
   *
   * @var object
   */
  protected $data;

  /**
   * The cache storage interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * CacheStorage constructor.
   *
   * @param string $key
   *   The circuit breaker ID.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache storage.
   * @param object $data
   *   Current data (if any).
   */
  public function __construct($key, CacheBackendInterface $cacheBackend, $data) {
    $this->key = $key;
    $this->data = $data ? $data->data : $this->defaultData();
    $this->cacheBackend = $cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultData() {
    $data = new \stdClass();
    $data->failureCount = 0;
    $data->lastFailureTime = 0;
    $data->isBroken = FALSE;
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function recordFailure($object) {
    $this->data->failureCount++;
    $this->data->lastFailureTime = time();
  }

  /**
   * {@inheritdoc}
   */
  public function failureCount() {
    return $this->data->failureCount;
  }

  /**
   * {@inheritdoc}
   */
  public function lastFailureTime() {
    return $this->data->lastFailureTime;
  }

  /**
   * {@inheritdoc}
   */
  public function setlastFailureTime($time) {
    $this->data->lastFailureTime = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function purgeFailures() {
    $this->data->failureCount = 0;
    $this->data->lastFailureTime = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function isBroken() {
    return $this->data->isBroken;
  }

  /**
   * {@inheritdoc}
   */
  public function setBroken($state) {
    $this->data->isBroken = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function persist() {
    $this->cacheBackend->set($this->key, $this->data);
  }

}
