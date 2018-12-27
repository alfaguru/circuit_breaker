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

  function configureTest() {
    $user = $this->drupalCreateUser(['access administration pages', 'administer circuit breakers']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/services/circuit_breaker/add');
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
  }

  /**
   * Test that the passthru to a service is transparent.
   */
  function testPassthru() {
    $this->configureTest();
    $random = $this->randomMachineName();
    $this->drupalGet('/cbtest/ok', ['query' => ['data' => $random]]);
    $this->assertSession()->pageTextContains('Test passed OK');
    $this->assertSession()->pageTextContains($random);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test that repeated failure causes circuit to break.
   */
  function testFailure() {
    $this->configureTest();
    $random = $this->randomMachineName();
    $this->drupalGet('/cbtest/fail', ['query' => ['data' => $random]]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContainsOnce('1 Exception (failure)');
    $this->assertSession()->pageTextContainsOnce('2 Exception (failure)');
    $this->assertSession()->pageTextContainsOnce('3 Exception (failure)');
    $this->assertSession()->pageTextContainsOnce('4 Exception (Circuit \'test\' is open)');
    $this->assertSession()->pageTextContainsOnce('5 Exception (Circuit \'test\' is open)');
    $this->drupalGet('/cbtest/ok', ['query' => ['data' => $random]]);
    $this->assertSession()->pageTextNotContains('Test passed OK');
    $this->assertSession()->pageTextContains('Test failed');
    $this->assertSession()->statusCodeEquals(200);
  }

  function testRetry() {
    $this->configureTest();
    $random = $this->randomMachineName();
    $this->drupalGet('/cbtest/fail', ['query' => ['data' => $random]]);
    $this->drupalGet('/cbtest/ok', ['query' => ['data' => $random]]);
    $this->assertSession()->pageTextContains('Test failed');
    $this->assertSession()->statusCodeEquals(200);
    // simulate passage of time
    $now = time();
    $interval = 5;
    for ($i = 0; $i < 20; $i++) {
      $time = $now - $interval;
      $interval += 5;
      $this->drupalGet('/cbtest/time', ['query' => ['time' => $time]]);
      $this->drupalGet('/cbtest/ok', ['query' => ['data' => $random]]);
      if ($this->getSession()->getPage()->hasContent('Test passed OK')) {
        break;
      }
    }
    $this->assertGreaterThanOrEqual(15, $interval);
    $this->assertLessThanOrEqual(300, $interval);
  }

}