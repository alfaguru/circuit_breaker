<?php

namespace Drupal\circuit_breaker\Services;


interface StorageInterface {

  /**
   * Record a failure event (typically an exception).
   * The storage implementation must store the time of the
   * event and keep a counter. It may also log it.
   *
   * @param object $object
   *
   * @return void
   */
  public function recordFailure($object);

  /**
   * Get the number of failures currently recorded.
   *
   * @return int
   */
  public function failureCount();

  /**
   * Timestamp of last recorded event.
   *
   * @return int
   */
  public function lastFailureTime();

  /**
   * Purge all failure data.
   *
   * @return void
   */
  public function purgeFailures();

  /**
   * Is the circuit broken?
   *
   * @return bool
   */
  public function isBroken();

  /**
   * Set the state of the circuit.
   *
   * @param bool $state
   *
   * @return void
   */
  public function setBroken($state);

  /**
   * Save all changes to the persistent store.
   *
   * @return void
   */
  public function persist();

}