<?php


namespace Drupal\Tests\circuit_breaker\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests of circuit breaker module.
 *
 * @group Circuit Breaker
 */
class CircuitBreakerTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['circuit_breaker', ];

  protected $profile = 'minimal';

  function testCanConfigure() {
    $user = $this->drupalCreateUser(['access administration pages', 'administer circuit breakers']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/services/circuit_breaker');
    $this->assertLink('Add a circuit breaker');
    $this->clickLink('Add a circuit breaker');
    $this->assertText('Label');
    $this->assertText('Failure threshold');
    $this->assertText('Interval after which a retry may be made');
    $this->assertText('Interval after which a retry must be made');
  }
}