<?php

namespace Drupal\Tests\circuit_breaker\Unit;

use Drupal\circuit_breaker\Services\CircuitBreaker;
use Drupal\circuit_breaker\Services\CircuitBrokenException;
use Drupal\circuit_breaker\Services\StorageInterface;
use PHPUnit\Framework\TestCase;

class CircuitBreakerTest extends TestCase {

  public function testExecute() {
    // check that storage isBroken is checked
    $storageStub = $this->createMock(StorageInterface::class);
    $storageStub
      ->method('isBroken')
      ->willReturn(FALSE);
    $config = [
      'threshold' => 5,
      'min_decay_time' => 3600, // after an hour will test again
      'max_decay_time' => 7200,
    ];
    $cb = new CircuitBreaker('test', $config, $storageStub);
    try {
      $result = $cb->execute(function () {
        return 'ok';
      });
      $this->assertEquals('ok', $result);
    } catch (\Exception $exception) {
      $this->fail('Exception thrown: ' . (string) $exception);
    }
    try {
      $result = $cb->execute(function () {
        throw new \Exception('failure');
      });
      $this->fail('Exception not thrown, result was ' . var_dump($result));
    } catch (\Exception $exception) {
      $this->assertEquals($exception->getMessage(), 'failure');
    }

  }
  public function testBroken() {
    // check that storage isBroken is checked
    $storageStub = $this->createMock(StorageInterface::class);
    $storageStub
      ->method('isBroken')
      ->willReturn(TRUE);
    $config = [
      'threshold' => 5,
      'min_decay_time' => 3600, // after an hour will test again
      'max_decay_time' => 7200,
    ];
    $cb = new CircuitBreaker('test', $config, $storageStub);
    try {
      $result = $cb->execute(function () {
        return 'ok';
      });
      $this->fail('Exception not thrown, result was ' . var_dump($result));
    } catch (CircuitBrokenException $exception) {
      $this->assertTrue(true);
    }

  }
}
