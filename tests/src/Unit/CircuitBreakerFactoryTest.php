<?php

namespace Drupal\Tests\circuit_breaker\unit;

use Drupal\circuit_breaker\CircuitBreakerFactory;
use Drupal\circuit_breaker\CircuitBreakerInterface;
use Drupal\circuit_breaker\Config\ConfigManagerInterface;
use Drupal\circuit_breaker\Storage\StorageManagerInterface;


/**
 * Unit tests of the circuit breaker factory class.
 * 
 * @group Circuit Breaker
 */
class CircuitBreakerFactoryTest extends \PHPUnit\Framework\TestCase {

  public function testloadsCircuitBreaker() {
    $cm = $this->createMock(ConfigManagerInterface::class);
    $cm->method('get')->willReturn([]);
    $sm = $this->createMock(StorageManagerInterface::class);
    $factory = new CircuitBreakerFactory($cm, $sm);
    $cb1 = $factory->load('test');
    $cb2 = $factory->load('test');
    $cb3 = $factory->load('test-other');
    $this->assertInstanceOf(CircuitBreakerInterface::class, $cb1);
    $this->assertTrue($cb1 === $cb2);
    $this->assertFalse($cb1 === $cb3);
  }

}
