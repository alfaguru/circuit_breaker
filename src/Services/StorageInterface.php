<?php

namespace Drupal\circuit_breaker\Services;


interface StorageInterface {

  public function addEvent($object);

  public function getEventCount();

  public function getEvents();

  public function isBroken();

  public function setBroken($state);

}