<?php

namespace Drupal\circuit_breaker;

use Drupal\circuit_breaker\Exception\CircuitBrokenException;
use Drupal\circuit_breaker\Storage\StorageInterface;

/**
 * Class CircuitBreakerInstance.
 *
 * @package Drupal\circuit_breaker\Services
 */
class CircuitBreaker implements CircuitBreakerInterface {

  /**
   * Circuit breaker identifier.
   *
   * @var string
   */
  protected $key;

  /**
   * Configuration parameters.
   *
   * @var array
   */
  protected $config;

  /**
   * Persistent storage of circuit breaker state.
   *
   * @var \Drupal\circuit_breaker\Storage\StorageInterface
   */
  protected $storage;

  /**
   * Is retry allowed in the current state?
   *
   * @var bool
   */
  protected $retryAllowed = TRUE;

  /**
   * Constructor.
   *
   * @param string $key
   *   Circuit breaker ID.
   * @param array $config
   *   Configuration parameters.
   * @param \Drupal\circuit_breaker\Storage\StorageInterface $storage
   *   Persistent storage.
   */
  public function __construct($key, array $config, StorageInterface $storage) {
    $this->key = $key;
    $this->config = $config;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
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
      $this->handleException($exception, $exceptionFilter);
      throw $exception;
    }
    finally {
      $this->storage->persist();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isRetryAllowed() {
    return $this->retryAllowed;
  }

  /**
   * {@inheritdoc}
   */
  public function setRetryAllowed($allowed = TRUE) {
    $this->retryAllowed = $allowed;
  }

  /**
   * {@inheritdoc}
   */
  public static function build($key, array $config, StorageInterface $storage) {
    return new CircuitBreaker($key, $config, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public function isBroken() {
    return $this->storage->isBroken();
  }

  /**
   * Determines when a broken circuit should be tested.
   *
   * @return bool
   *   TRUE when a retry is required, FALSE otherwise.
   */
  protected function shouldRetry() {
    if ($this->retryAllowed) {
      $time = time();
      $last_time = $this->storage->lastFailureTime();
      $interval = $time - $last_time;
      if ($interval >= $this->config['test_retry_min_interval']) {
        if ($interval >= $this->config['test_retry_max_interval']) {
          return TRUE;
        }
        /*
         * Should be an exponential function for best distribution.
         * But use a quadratic as near enough and easier to compute.
         */
        $i = $interval - $this->config['test_retry_min_interval'];
        $w = $this->config['test_retry_max_interval'] - $this->config['test_retry_min_interval'];
        $probability = 100 * ($i * $i) / ($w * $w);
        $random = mt_rand(0, 99);
        return $probability >= 100 ? TRUE : $probability >= $random;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function recordSuccess() {
    $this->storage->setBroken(FALSE);
    $this->storage->purgeFailures();
  }

  /**
   * {@inheritdoc}
   */
  public function recordFailure(\Exception $exception) {
    $this->storage->recordFailure($exception);
    if ($this->storage->failureCount() >= $this->config['threshold']) {
      $this->storage->setBroken(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handleException(\Exception $exception, $exceptionFilter = NULL) {
    if (is_string($exceptionFilter)) {
      $definition = $exceptionFilter;
      $classes = array_filter(preg_split('/\s+/', $definition));
      foreach ($classes as $class) {
        if (is_a($exception, $class)) {
          $this->recordSuccess();
          return;
        }
      }
    }
    if (is_callable($exceptionFilter)) {
      if (call_user_func($exceptionFilter, $exception) === TRUE) {
        $this->recordSuccess();
        return;
      }
    }
    $this->handleException($exception);
  }

}
