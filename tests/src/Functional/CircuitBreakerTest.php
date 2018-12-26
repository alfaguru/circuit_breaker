<?php


namespace Drupal\Tests\circuit_breaker\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests of circuit breaker module.
 *
 * @group Circuit Breaker
 * @group legacy
 */
class CircuitBreakerTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['circuit_breaker', 'circuit_breaker_test', ];

  protected $profile = 'minimal';

  /**
   * Test basic functionality of the configuration interface.
   */
  function testCanConfigure() {
    $user = $this->drupalCreateUser(['access administration pages', 'administer circuit breakers']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/services/circuit_breaker');
    $this->assertSession()->linkExists('Add a circuit breaker');
    $this->clickLink('Add a circuit breaker');
    $this->assertSession()->pageTextContains('Label');
    $this->assertSession()->pageTextContains('Failure threshold');
    $this->assertSession()->pageTextContains('Interval after which a retry may be made');
    $this->assertSession()->pageTextContains('Interval after which a retry must be made');
    $this->submitForm(
      [
        'label' => 'Test of CB',
        'id' => 'test',
        'threshold' => 3,
        'test_retry_min_interval[other]' => 15,
        'test_retry_max_interval[select]' => 300,
      ],
      'Save'
    );
    $this->assertSession()->addressEquals('/admin/config/services/circuit_breaker');
    $this->assertSession()->pageTextContains('Test of CB');
  }

  /**
   * Test that the passthru to a service is transparent.
   */
  function testPassthru() {
    $random = $this->randomString();
    $this->drupalGet('/cbtest/ok', ['query' => ['data' => $random]]);
    $this->assertSession()->pageTextContains('Test passed OK');
    $this->assertSession()->pageTextContains($random);
    $this->assertSession()->statusCodeEquals(200);
  }
}