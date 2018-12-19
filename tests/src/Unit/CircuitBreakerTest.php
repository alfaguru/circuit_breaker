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
      'test_retry_min_interval' => 3600, // after an hour will possibly test again
      'test_retry_window_size' => 3600, // after a further hour will definitely test again
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
    $storageStub = $this->createMock(StorageInterface::class);
    $storageStub
      ->method('isBroken')
      ->willReturn(TRUE);
    $storageStub
      ->method('lastEventTime')
      ->willReturn(time());

    $config = [
      'threshold' => 5,
      'test_retry_min_interval' => 3600, // after an hour will possibly test again
      'test_retry_window_size' => 3600, // after a further hour will definitely test again
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

  public function testThresholdTripped() {
    $storageStub = $this->createMock(StorageInterface::class);
    $storageStub
      ->method('isBroken')
      ->willReturn(FALSE);
    $storageStub
      ->method('getEventCount')
      ->willReturn(5);
    $storageStub
      ->expects($this->once())
      ->method('setBroken')
      ->with($this->equalTo(true));
    $config = [
      'threshold' => 5,
      'test_retry_min_interval' => 3600, // after an hour will possibly test again
      'test_retry_window_size' => 3600, // after a further hour will definitely test again
    ];
    $cb = new CircuitBreaker('test', $config, $storageStub);
    try {
      $result = $cb->execute(function () {
        throw new \Exception('failure');
      });
      $this->fail('Exception not thrown, result was ' . var_dump($result));
    } catch (\Exception $exception) {
      $this->assertEquals($exception->getMessage(), 'failure');
    }
  }

  public function testCircuitRetry() {
    $storageStub = $this->createMock(StorageInterface::class);
    $storageStub
      ->method('isBroken')
      ->willReturn(TRUE);
    $storageStub
      ->method('lastEventTime')
      ->willReturn(time() - 8000);
    $config = [
      'threshold' => 5,
      'test_retry_min_interval' => 3600, // after an hour will possibly test again
      'test_retry_window_size' => 3600, // after a further hour will definitely test again
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
  }
}
