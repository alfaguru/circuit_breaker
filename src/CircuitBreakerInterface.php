<?php

namespace Drupal\circuit_breaker;

use Drupal\circuit_breaker\Storage\StorageInterface;

/**
 * Interface for a circuit breaker.
 */
interface CircuitBreakerInterface {

  /**
   * Static constructor.
   *
   * @param string $key
   *   The circuit breaker ID.
   * @param array $config
   *   Configuration parameters.
   * @param \Drupal\circuit_breaker\Storage\StorageInterface $storage
   *   Persistent storage for the circuit breaker.
   *
   * @return CircuitBreakerInterface
   *   The circuit breaker.
   */
  public static function build($key, array $config, StorageInterface $storage);

  /**
   * Call the service being monitored by this circuit breaker.
   *
   * @param callable $command
   *   A function that invokes the service.
   * @param array $args
   *   Arguments to be passed to the function.
   * @param string|callable|null $exceptionFilter
   *   Defines exceptions that do NOT trigger the breaker.
   *
   *   Either:
   *    - Whitespace separated list of exception (base)classes.
   *    - A function that takes an exception as argument and returns TRUE
   *      for matching exceptions.
   *
   * @return mixed
   *   The return value from the service call.
   *
   * @throws \Drupal\circuit_breaker\Exception\CircuitBrokenException
   *   Indicates the circuit is broken.
   * @throws \Throwable
   *   Exception thrown by the service call.
   */
  public function execute(callable $command, array $args = [], $exceptionFilter = NULL);

  /**
   * Can the circuit breaker make a retry in the scope of this request?
   *
   * @return bool
   *   TRUE if retry is allowed, FALSE otherwise.
   */
  public function isRetryAllowed();

  /**
   * Set whether the circuit breaker can retry or not.
   *
   * @param bool $allowed
   *   TRUE if retry is to be allowed, FALSE otherwise.
   */
  public function setRetryAllowed($allowed = TRUE);

  /**
   * Is the circuit currently broken?
   *
   * @return bool
   *   TRUE if the circuit is broken, FALSE otherwise.
   */
  public function isBroken();

  /**
   * A successful call has been made.
   */
  public function recordSuccess();

  /**
   * A service failure occurred.
   *
   * @param \Exception $exception
   *   The related exception.
   */
  public function recordFailure(\Exception $exception);

  /**
   * An exception was thrown.
   *
   * Determine if it is a service issue (it might be an application
   * issue such as a 404 Not Found) and handle it.
   *
   * @param \Exception $exception
   *   The exception.
   * @param string|callable|null $exceptionFilter
   *   Defines exceptions that are not service failures.
   *
   *     Either:
   *     - A space-separated string of class names, or
   *     - A callable that returns TRUE for such exceptions.
   */
  public function handleException(\Exception $exception, $exceptionFilter = NULL);

}
