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
      $result = $cb->execute(function ($arg1, $arg2) {
        return $arg2;
      }, ['notok', 'ok'] );
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
      ->method('lastFailureTime')
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
      ->method('failureCount')
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
      ->method('lastFailureTime')
      ->willReturn(time() - 8000);
    $storageStub
      ->expects($this->once())
      ->method('setBroken')
      ->with($this->equalTo(FALSE));
    $storageStub
      ->expects($this->once())
      ->method('purgeFailures');
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

  protected $interval = 0;

  function intervalFaker() {
    $return = time() - $this->interval;
    $this->interval += 60;
    return $return;
  }


  function testRandomizationOfRetry() {
    // run 100 tests and check it retries somewhere in the window
    $storageStub = $this->createMock(StorageInterface::class);
    $this->interval = 3000;

    $storageStub
      ->method('isBroken')
      ->willReturn(TRUE);
    $storageStub
      ->method('lastFailureTime')
      ->will($this->returnCallback([$this, 'intervalFaker'])
      );
    $storageStub
      ->expects($this->once())
      ->method('setBroken')
      ->with($this->equalTo(FALSE));
    $storageStub
      ->expects($this->once())
      ->method('purgeFailures');
    $config = [
      'threshold' => 5,
      'test_retry_min_interval' => 3600, // after an hour will possibly test again
      'test_retry_window_size' => 3600, // after a further hour will definitely test again
    ];
    $cb = new CircuitBreaker('test', $config, $storageStub);
    for ($i = 0; $i < 100; $i++) {
      try {
        $cb->execute(function () {
          return 'ok';
        });
        break;
      } catch (\Exception $exception) {
        continue;
      }
    }
    $this->assertGreaterThan(9, $i);
    $this->assertLessThan(71, $i);

    $minutes = 50 + $i;

    echo "On this test run, retried after $minutes minutes\n";
  }

  function testStringExceptionfilter() {
    $storageStub = $this->createMock(StorageInterface::class);
    $storageStub
      ->method('isBroken')
      ->willReturn(FALSE);
    $storageStub
      ->method('failureCount')
      ->willReturn(0);
    $storageStub
      ->expects($this->never())
      ->method('recordFailure');
    $config = [
      'threshold' => 5,
      'test_retry_min_interval' => 3600, // after an hour will possibly test again
      'test_retry_window_size' => 3600, // after a further hour will definitely test again
    ];
    $cb = new CircuitBreaker('test', $config, $storageStub);
    try {
      $cb->execute(function () {
        throw new NotSeriousException();
      }, [], 'SomeException ' .NotSeriousException::class . ' AnotherException' );
      $this->fail('Exception was not thrown');
    }
    catch (NotSeriousException $exception) {
      $this->assertTrue(true);
    }
    catch (\Exception $exception) {
      $this->fail('Wrong exception thrown ' . get_class($exception));
    }

  }

  function testCallableExceptionFilter() {
    $storageStub = $this->createMock(StorageInterface::class);
    $storageStub
      ->method('isBroken')
      ->willReturn(FALSE);
    $storageStub
      ->method('failureCount')
      ->willReturn(0);
    $storageStub
      ->expects($this->never())
      ->method('recordFailure');
    $config = [
      'threshold' => 5,
      'test_retry_min_interval' => 3600, // after an hour will possibly test again
      'test_retry_window_size' => 3600, // after a further hour will definitely test again
    ];
    $cb = new CircuitBreaker('test', $config, $storageStub);
    try {
      $cb->execute(function () {
        throw new OtherException();
      }, [], function($exception) {
        if (get_class($exception) === NotSeriousException::class) {
          return '0';
        }
        if (get_class($exception) === OtherException::class) {
          return TRUE;
        }
        return NULL;
      });
      $this->fail('Exception was not thrown');
    }
    catch (OtherException $exception) {
      $this->assertTrue(true);
    }
    catch (\Exception $exception) {
      $this->fail('Wrong exception thrown ' . get_class($exception));
    }
  }

}
class NotSeriousException extends \Exception {

};
class OtherException extends NotSeriousException {

}
