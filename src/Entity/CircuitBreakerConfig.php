<?php

namespace Drupal\circuit_breaker\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Circuit breaker configuration entity.
 *
 * @ConfigEntityType(
 *   id = "circuit_breaker_config",
 *   label = @Translation("circuit breaker"),
 *   label_plural = @Translation("circuit breakers"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\circuit_breaker\CircuitBreakerConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\circuit_breaker\Form\CircuitBreakerConfigForm",
 *       "edit" = "Drupal\circuit_breaker\Form\CircuitBreakerConfigForm",
 *       "delete" = "Drupal\circuit_breaker\Form\CircuitBreakerConfigDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\circuit_breaker\CircuitBreakerConfigHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "circuit_breaker_config",
 *   admin_permission = "administer circuit breakers",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/circuit_breaker/{circuit_breaker_config}",
 *     "add-form" = "/admin/config/services/circuit_breaker/add",
 *     "edit-form" = "/admin/config/services/circuit_breaker/{circuit_breaker_config}/edit",
 *     "delete-form" = "/admin/config/services/circuit_breaker/{circuit_breaker_config}/delete",
 *     "collection" = "/admin/config/services/circuit_breaker"
 *   }
 * )
 */
class CircuitBreakerConfig extends ConfigEntityBase implements CircuitBreakerConfigInterface {

  /**
   * The Circuit breaker config ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Circuit breaker config label.
   *
   * @var string
   */
  protected $label;

  /**
   * Number of failures before breaker trips.
   *
   * @var int
   */
  protected $threshold = 5;

  /**
   * Number of seconds since last failure before a retry may be attempted.
   *
   * @var int
   */
  protected $test_retry_min_interval = 60;

  /**
   * Number of seconds since last failure before a retry will be attempted.
   *
   * @var int
   */
  protected $test_retry_max_interval = 300;

  /**
   * The failure threshold.
   *
   * @return int
   *   Number of failures before breaker trips.
   */
  public function threshold() {
    return $this->threshold;
  }

  /**
   * Get the interval lower limit for a randomized retry.
   *
   * @return int
   *   Number of seconds since last failure before a retry may be attempted.
   */
  public function testRetryMinInterval() {
    return $this->test_retry_min_interval;
  }

  /**
   * Get the interval upper limit for a randomized retry.
   *
   * @return int
   *   Number of seconds since last failure before a retry will be attempted.
   */
  public function testRetryMaxInterval() {
    return $this->test_retry_max_interval;
  }

}
