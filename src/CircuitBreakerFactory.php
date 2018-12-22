<?php

namespace Drupal\circuit_breaker;

use Drupal\circuit_breaker\Config\ConfigManagerInterface;
use Drupal\circuit_breaker\Storage\StorageManagerInterface;

/**
 * Factory for circuit breakers.
 *
 * Class CircuitBreakerFactory
 *
 * @package Drupal\circuit_breaker\Services
 */
class CircuitBreakerFactory implements CircuitBreakerFactoryInterface {

  /**
   * @var \Drupal\circuit_breaker\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * @var \Drupal\circuit_breaker\Storage\StorageManagerInterface
   */
  protected $storageManager;

  function __construct(ConfigManagerInterface $configManager, StorageManagerInterface $storagemanager) {
    $this->configManager = $configManager;
    $this->storageManager = $storagemanager;
  }

  /**
   * @var CircuitBreakerInterface[]
   */
  protected $cbs = [];

  /**
   * @param $key
   *
   * @return CircuitBreakerInterface
   */
  public function load($key) {
    if (!isset($this->cbs[$key])) {
      $config = $this->getConfig($key);
      $storage = $this->storageManager->getStorage($key);
      $class = $config['class'];
      $this->cbs[$key] = call_user_func([$class, 'build'], $key, $config, $storage);
    }
    return $this->cbs[$key];
  }

  /**
   * @param $key
   *
   * @return array
   */
  protected function getConfig($key) {
    return $this->configManager->get($key) + [
        'class' => CircuitBreaker::class,
    ];
  }

}