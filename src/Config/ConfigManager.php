<?php

namespace Drupal\circuit_breaker\Config;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class ConfigManager
 *
 * @package Drupal\circuit_breaker\Config
 */
class ConfigManager implements ConfigManagerInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $configStorage;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->configStorage = $entityTypeManager->getStorage('circuit_breaker_config');
  }

  public function get($key) {
    $config = $this->configStorage->load($key);
    return $config? $config->toArray(): [];
  }

}