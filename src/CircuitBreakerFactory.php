<?php

namespace Drupal\circuit_breaker;

use Drupal\circuit_breaker\Config\ConfigManagerInterface;
use Drupal\circuit_breaker\Storage\StorageManagerInterface;

/**
 * Factory for circuit breakers.
 *
 * Class CircuitBreakerFactory.
 *
 * @package Drupal\circuit_breaker\Services
 */
class CircuitBreakerFactory implements CircuitBreakerFactoryInterface {

  /**
   * The configuration manager.
   *
   * @var \Drupal\circuit_breaker\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The storage manager.
   *
   * @var \Drupal\circuit_breaker\Storage\StorageManagerInterface
   */
  protected $storageManager;

  /**
   * Local cache for circuit breakers.
   *
   * @var CircuitBreakerInterface[]
   */
  protected $cbs = [];

  /**
   * Constructor.
   */
  public function __construct(ConfigManagerInterface $configManager, StorageManagerInterface $storagemanager) {
    $this->configManager = $configManager;
    $this->storageManager = $storagemanager;
  }

  /**
   * Get a circuit breaker by ID.
   *
   * @param string $key
   *   The circuit breaker ID.
   *
   * @return CircuitBreakerInterface
   *   The circuit breaker.
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
   * Get the configuration for a circuit breaker.
   *
   * @param string $key
   *   The circuit breaker ID.
   *
   * @return array
   *   Configuration parameters.
   */
  protected function getConfig($key) {
    return $this->configManager->get($key) + [
      'class' => CircuitBreaker::class,
    ];
  }

}
