<?php

namespace Drupal\Tests\circuit_breaker\unit;

use Drupal\circuit_breaker\Services\CircuitBreakerFactory;
use Drupal\circuit_breaker\Services\CircuitBreakerInterface;


class CircuitBreakerFactoryTest extends \PHPUnit\Framework\TestCase {

  public function testloadsCircuitBreaker() {
    $factory = new CircuitBreakerFactory();
    $cb1 = $factory->load('test');
    $cb2 = $factory->load('test');
    $cb3 = $factory->load('test-other');
    $this->assertInstanceOf(CircuitBreakerInterface::class, $cb1);
    $this->assertTrue($cb1 === $cb2);
    $this->assertFalse($cb1 === $cb3);
  }

}
