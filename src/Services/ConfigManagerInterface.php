<?php

namespace Drupal\circuit_breaker\Services;


interface ConfigManagerInterface {

  /**
   * @param string $key
   * @return array
   */
  public function get($key);

  /**
   * @param string $key
   * @param $value
   *
   * @return void
   */
  public function set($key, array $value);
}