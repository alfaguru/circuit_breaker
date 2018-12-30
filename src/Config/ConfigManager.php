<?php

namespace Drupal\circuit_breaker\Config;

use Drupal\circuit_breaker\Exception\MissingConfigException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Configuration manager using Drupal config entities.
 */
class ConfigManager implements ConfigManagerInterface {

  /**
   * Storage for the entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $configStorage;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->configStorage = $entityTypeManager->getStorage('circuit_breaker_config');
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    $config = $this->configStorage->load($key);
    if ($config) {
      return $config->toArray();
    }
    throw new MissingConfigException($key);
  }

}
