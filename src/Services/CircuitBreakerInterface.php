<?php

namespace Drupal\circuit_breaker\Services;


interface CircuitBreakerInterface {

  /**
   * Static constructor
   *
   * @param string $key
   * @param array $config
   * @param \Drupal\circuit_breaker\Services\StorageInterface
   *
   * @return CircuitBreakerInterface
   */
  static function build($key, array $config, $storage);

  /**
   * @param callable $command
   * @param array|callable|null $exceptionFilter
   *
   * @return mixed
   * @throws
   */
  public function execute(callable $command, $exceptionFilter = NULL);

  /**
   * @param string $key
   * @param array $options
   *
   * @return boolean
   */
  public function isBroken();

  /**
   * A successful call has been made.
   *
   * @return void
   */
  public function recordSuccess();

  /**
   * An exception was thrown. Determine if it is a service issue
   * (it might be an application layer issue) and handle it.
   *
   * @param \Exception $exception
   *
   * @return void
   */
  public function handleException(\Exception $exception);

  /**
   * A service failure occurred.
   *
   * @param \Exception $exception
   * @param array|callable|null $exceptionFilter
   *
    * @return void
   */
  public function recordFailure(\Exception $exception, $exceptionFilter = NULL);

}