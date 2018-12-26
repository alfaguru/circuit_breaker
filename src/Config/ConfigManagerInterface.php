<?php

namespace Drupal\circuit_breaker\Config;


interface ConfigManagerInterface {

  /**
   * @param string $key
   *
   * @return array
   */
  public function get($key);
}
