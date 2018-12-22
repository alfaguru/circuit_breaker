<?php

namespace Drupal\circuit_breaker;


interface CircuitBreakerInterface {

  /**
   * Static constructor
   *
   * @param string $key
   * @param array $config
   * @param \Drupal\circuit_breaker\Storage\StorageInterface
   *
   * @return CircuitBreakerInterface
   */
  static function build($key, array $config, $storage);

  /**
   * Call the service being monitored by this circuit breaker.
   *
   * @param callable $command
   *    A function that invokes the service
   *
   * @param array $args
   *    Arguments to be passed to the function
   *
   * @param string|callable|null $exceptionFilter
   *    Defines exceptions that do NOT trigger the breaker.
   *    - Whitespace separated list of exception (base)classes.
   *    - A function that takes an exception as argument and returns TRUE for matching exceptions.
   *
   * @return mixed
   * @throws
   */
  public function execute(callable $command, array $args = [], $exceptionFilter = NULL);

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