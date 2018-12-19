<?php

namespace Drupal\circuit_breaker\Services;

/**
 * Class CircuitBreakerInstance
 *
 * @package Drupal\circuit_breaker\Services
 */
class CircuitBreaker implements CircuitBreakerInterface {

  /**
   * @var string
   */
  protected $key;

  /**
   * @var array
   */
  protected $config;

  /**
   * @var \Drupal\circuit_breaker\Services\StorageInterface
   */
  protected $storage;

  /**
   * CircuitBreakerInstance constructor.
   *
   * @param string $key
   * @param array $config
   * @param \Drupal\circuit_breaker\Services\StorageInterface $storage
   */
  public function __construct($key, array $config, $storage) {
    $this->key = $key;
    $this->config = $config;
    $this->storage = $storage;
  }

  /**
   * @param callable $command
   * @param null|array|callable $exceptionFilter
   * @return mixed
   * @throws
   */
  public function execute(callable $command, $exceptionFilter = NULL) {
    if ($this->isBroken()) {
      throw new CircuitBrokenException($this->key);
    }
    try {
      $return = call_user_func($command);
      $this->recordSuccess();
      return $return;
    }
    catch (\Exception $exception) {
      $this->recordFailure($exception, $exceptionFilter);
      throw $exception;
    }

  }

  /**
   * @param string $key
   * @param array $config
   * @param \Drupal\circuit_breaker\Services\StorageInterface $storage
   *
   * @return \Drupal\circuit_breaker\Services\CircuitBreaker|\Drupal\circuit_breaker\Services\CircuitBreakerInterface
   */
  static function build($key, array $config, $storage) {
    return new CircuitBreaker($key, $config, $storage);
  }

  public function isBroken() {
    return $this->storage->isBroken();
  }

  public function recordSuccess() {
    // TODO: Implement recordSuccess() method.
  }

  public function handleException(\Exception $exception) {
    // TODO: Implement handleException() method.
  }

  /**
   * @param $exception
   * @param null|array|callable $exceptionFilter
   */
  public function recordFailure(\Exception $exception, $exceptionFilter = NULL) {
    $this->storage->addEvent($exception);
  }


}