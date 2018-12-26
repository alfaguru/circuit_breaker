<?php

namespace Drupal\circuit_breaker\Storage;


use Drupal\Core\Cache\CacheBackendInterface;

class CacheStorage implements StorageInterface {

  /**
   * @var string
   */
  protected $key;

  /**
   * @var object
   */
  protected $data;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * CacheStorage constructor.
   *
   * @param string $key
   * @param CacheBackendInterface $cacheBackend
   * @param object $data#
   */
  public function __construct($key, CacheBackendInterface $cacheBackend, $data) {
    $this->key = $key;
    $this->data = $data? $data: $this->defaultData();
    $this->cacheBackend = $cacheBackend;
  }

  protected function defaultData() {
    $data = new \stdClass();
    $data->failureCount = 0;
    $data->lastFailureTime = 0;
    $data->isBroken = FALSE;
    return $data;
  }

  public function recordFailure($object) {
    $this->data->failureCount++;
    $this->data->lastFailureTime = time();
  }

  public function failureCount() {
    return $this->data->failureCount;
  }

  public function lastFailureTime() {
    return $this->data->lastFailureTime;
  }

  public function purgeFailures() {
    $this->data->failureCount = 0;
    $this->data->lastFailureTime = 0;
  }

  public function isBroken() {
    return $this->data->isBroken;
  }

  public function setBroken($state) {
    $this->data->isBroken = $state;
  }

  public function persist() {
    $this->cacheBackend->set($this->key, $this->data);
  }


}