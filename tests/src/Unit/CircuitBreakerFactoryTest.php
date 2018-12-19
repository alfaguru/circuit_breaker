<?php

namespace Drupal\Tests\circuit_breaker\unit;

use Drupal\circuit_breaker\Services\CircuitBreakerFactory;
use Drupal\circuit_breaker\Services\CircuitBreakerInterface;
use Drupal\circuit_breaker\Services\ConfigManagerInterface;
use Drupal\circuit_breaker\Services\StorageManagerInterface;


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
