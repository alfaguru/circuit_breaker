<?php

namespace Drupal\circuit_breaker\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CircuitBreakerConfigForm.
 */
class CircuitBreakerConfigForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $circuit_breaker_config = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $circuit_breaker_config->label(),
      '#description' => $this->t("Label for the circuit breaker."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $circuit_breaker_config->id(),
      '#machine_name' => [
        'exists' => '\Drupal\circuit_breaker\Entity\CircuitBreakerConfig::load',
      ],
      '#disabled' => !$circuit_breaker_config->isNew(),
    ];

    $form['threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Failure threshold'),
      '#description' => $this->t('Number of failures before the breaker trips.'),
      '#default_value' => $circuit_breaker_config->threshold(),
      '#required' => TRUE,
    ];

    $form['retry'] = [
      '#type' => 'details',
      '#title' => $this->t('Automatic retry parameters'),
      '#descrption' => $this->t('The circuit will be tested after an interval determined by a randomizing algorithm.
      A retry may be made any time after the lower time limit has elapsed. 
      Once the upper time interval has elapsed a retry must be made. '),
      '#open' => TRUE,
    ];
    $options = [
      0 => $this->t('Immediately'),
      10 => $this->t('After 10 seconds'),
      30 => $this->t('After 30 seconds'),
      60 => $this->t('After 1 minute'),
      300 => $this->t('After 5 minutes'),
      900 => $this->t('After 15 minutes'),
      1800 => $this->t('After 30 minutes'),
      3600 => $this->t('After 1 hour'),
      7200 => $this->t('After 2 hours'),
      21600 => $this->t('After 6 hours'),
      43200 => $this->t('After 12 hours'),
      86400 => $this->t('After 1 day'),
    ];
    $default = (int) $circuit_breaker_config->testRetryMinInterval();
    if (!isset($options[$default])) {
      $options[$default] = $this->t('@i seconds', ['@i' => $default]);
    }
    $form['retry']['test_retry_min_interval'] = [
      '#type' => 'select_or_other_select',
      '#title' => $this->t('Interval after which a retry may be made'),
      '#description' => $this->t('You may enter a different value (in seconds) by selecting "Other".'),
      '#options' => $options,
      '#default_value' => $circuit_breaker_config->testRetryMinInterval(),
      '#required' => TRUE,
    ];
    $options = [
      0 => $this->t('Immediately'),
      10 => $this->t('After 10 seconds'),
      30 => $this->t('After 30 seconds'),
      60 => $this->t('After 1 minute'),
      300 => $this->t('After 5 minutes'),
      900 => $this->t('After 15 minutes'),
      1800 => $this->t('After 30 minutes'),
      3600 => $this->t('After 1 hour'),
      7200 => $this->t('After 2 hours'),
      21600 => $this->t('After 6 hours'),
      43200 => $this->t('After 12 hours'),
      86400 => $this->t('After 1 day'),
    ];
    $default = (int) $circuit_breaker_config->testRetryMaxInterval();
    if (!isset($options[$default])) {
      $options[$default] = $this->t('@i seconds', ['@i' => $default]);
    }
    $form['retry']['test_retry_max_interval'] = [
      '#type' => 'select_or_other_select',
      '#title' => $this->t('Interval after which a retry must be made'),
      '#description' => $this->t('You may enter a different value (in seconds) by selecting "Other".'),
      '#options' => $options,
      '#default_value' => $circuit_breaker_config->testRetryMaxInterval(),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    foreach ($values as $key => $value) {
      if ($key === 'test_retry_min_interval' || $key === 'test_retry_max_interval') {
        $value = $value[0];
      }
      $entity->set($key, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $circuit_breaker_config = $this->entity;
    $status = $circuit_breaker_config->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Circuit breaker config.', [
          '%label' => $circuit_breaker_config->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Circuit breaker config.', [
          '%label' => $circuit_breaker_config->label(),
        ]));
    }
    $form_state->setRedirectUrl($circuit_breaker_config->toUrl('collection'));
  }

}
