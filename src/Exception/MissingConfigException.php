<?php
namespace Drupal\circuit_breaker\Exception;

class MissingConfigException extends \Exception {

  public function __construct($key) {
    parent::__construct("Circuit '$key' is not defined");
  }
}