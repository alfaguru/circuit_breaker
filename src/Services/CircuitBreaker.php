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
   * @param array $args
   * @param null|string|callable $exceptionFilter
   * @return mixed
   * @throws
   */
  public function execute(callable $command, array $args = [], $exceptionFilter = NULL) {
    if ($this->isBroken() && !$this->shouldRetry()) {
      throw new CircuitBrokenException($this->key);
    }
    try {
      $return = call_user_func_array($command, $args);
      $this->recordSuccess();
      return $return;
    }
    catch (\Exception $exception) {
      $this->recordFailure($exception, $exceptionFilter);
      throw $exception;
    }
    finally {
      $this->storage->persist();
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

  protected function shouldRetry() {
    $time = time();
    $last_time = $this->storage->lastFailureTime();
    $interval = $time - $last_time;
    if ($interval >= $this->config['test_retry_min_interval']) {
      /*
       * Should be an exponential function for best distribution.
       * But use a quadratic as near enough and easier to compute.
       */
      $i = $interval - $this->config['test_retry_min_interval'];
      $w = $this->config['test_retry_window_size'];
      $probability = 100 * ($i * $i) / ($w * $w);
      $random = random_int(0, 99);
      return $probability >= 100? TRUE: $probability >= $random;
    }
  }

  public function recordSuccess() {
    $this->storage->setBroken(false);
    $this->storage->purgeFailures();
  }

  public function handleException(\Exception $exception) {
    $this->storage->recordFailure($exception);
    if ($this->storage->failureCount() >= $this->config['threshold']) {
      $this->storage->setBroken(true);
    }
  }

  /**
   * @param $exception
   * @param null|string|callable $exceptionFilter
   */
  public function recordFailure(\Exception $exception, $exceptionFilter = NULL) {
    if (is_string($exceptionFilter)) {
      $definition = $exceptionFilter;
      $classes = array_filter(preg_split('/\s+/', $definition));
      foreach ($classes as $class) {
        if (is_a($exception, $class)) {
          return;
        }
      }
    }
    if (is_callable($exceptionFilter)) {
      if (call_user_func($exceptionFilter, $exception) === TRUE) {
        return;
      }
    }
    $this->handleException($exception);
  }


}