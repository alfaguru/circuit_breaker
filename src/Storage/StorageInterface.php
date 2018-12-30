<?php

namespace Drupal\circuit_breaker\Storage;

/**
 * Persistent storage for a circuit breaker.
 */
interface StorageInterface {

  /**
   * Record a failure event (typically an exception).
   *
   * The storage implementation must store the time of the
   * event and keep a counter. It may also log it.
   *
   * @param object $object
   *   The failure event.
   */
  public function recordFailure($object);

  /**
   * Get the number of failures currently recorded.
   *
   * @return int
   *   The number of failures.
   */
  public function failureCount();

  /**
   * Timestamp of last recorded event.
   *
   * @return int
   *   Last failure time in seconds since epoch.
   */
  public function lastFailureTime();

  /**
   * Set the timestamp of last recorded event.
   *
   * @param int $time
   *   The time in seconds since epoch.
   */
  public function setlastFailureTime($time);

  /**
   * Purge all failure data.
   */
  public function purgeFailures();

  /**
   * Is the circuit broken?
   *
   * @return bool
   *   The current state of the circuit.
   */
  public function isBroken();

  /**
   * Set the state of the circuit.
   *
   * @param bool $state
   *   The new state value.
   */
  public function setBroken($state);

  /**
   * Save all changes to the persistent store.
   */
  public function persist();

}
