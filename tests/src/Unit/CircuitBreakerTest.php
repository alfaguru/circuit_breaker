<?php

namespace Drupal\Tests\circuit_breaker\Unit;

use Drupal\circuit_breaker\CircuitBreaker;
use Drupal\circuit_breaker\Exception\CircuitBrokenException;
use Drupal\circuit_breaker\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of the circuit breaker core class.
 *
 * @group Circuit Breaker
 */
class CircuitBreakerTest extends TestCase {

  /**
   * Test basic operation.
   */
  public function testExecute() {
    // Check that storage isBroken is checked.
    $storageStub = $this->createMock(StorageInterface::class);
    $storageStub
      ->method('isBroken')
      ->willReturn(FALSE);
    $config = [
      'threshold' => 5,
      // After an hour will possibly test again.
      'test_retry_min_interval' => 3600,
      // After a further hour will definitely test again.
      'test_retry_max_interval' => 7200,
    ];
    $cb = new CircuitBreaker('test', $config, $storageStub);
    try {
      $result = $cb->execute(function ($arg1, $arg2) {
        return $arg2;
      }, ['notok', 'ok']);
      $this->assertEquals('ok', $result);
    }
    catch (\Exception $exception) {
      $this->fail('Exception thrown: ' . (string) $exception);
    }
    try {
      $result = $cb->execute(function () {
        throw new \Exception('failure');
      });
      $this->fail('Exception not thrown, result was ' . var_dump($result));
    }
    catch (\Exception $exception) {
      $this->assertEquals($exception->getMessage(), 'failure');
    }

  }

  /**
   * Test a broken circuit.
   */
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
      // After an hour will possibly test again.
      'test_retry_min_interval' => 3600,
      // After a further hour will definitely test again.
      'test_retry_max_interval' => 7200,
    ];
    $cb = new CircuitBreaker('test', $config, $storageStub);
    try {
      $result = $cb->execute(function () {
        return 'ok';
      });
      $this->fail('Exception not thrown, result was ' . var_dump($result));
    }
    catch (CircuitBrokenException $exception) {
      $this->assertTrue(TRUE);
    }

  }

  /**
   * Test that a threshold being passed trips the breaker.
   */
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
      ->with($this->equalTo(TRUE));
    $config = [
      'threshold' => 5,
      // After an hour will possibly test again.
      'test_retry_min_interval' => 3600,
      // After a further hour will definitely test again.
      'test_retry_max_interval' => 7200,
    ];
    $cb = new CircuitBreaker('test', $config, $storageStub);
    try {
      $result = $cb->execute(function () {
        throw new \Exception('failure');
      });
      $this->fail('Exception not thrown, result was ' . var_dump($result));
    }
    catch (\Exception $exception) {
      $this->assertEquals($exception->getMessage(), 'failure');
    }
  }

  /**
   * Test the retry algorithm.
   */
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
      // After an hour will possibly test again.
      'test_retry_min_interval' => 3600,
      // After a further hour will definitely test again.
      'test_retry_max_interval' => 7200,
    ];
    $cb = new CircuitBreaker('test', $config, $storageStub);
    try {
      $result = $cb->execute(function () {
        return 'ok';
      });
      $this->assertEquals('ok', $result);
    }
    catch (\Exception $exception) {
      $this->fail('Exception thrown: ' . (string) $exception);
    }
  }

  /**
   * The current interval for retry testing.
   *
   * @var int
   */
  protected $interval = 0;

  /**
   * Function to fake the last failure time.
   */
  public function intervalFaker() {
    $return = time() - $this->interval;
    $this->interval += 60;
    return $return;
  }

  /**
   * Test the random retry algorithm.
   */
  public function testRandomizationOfRetry() {
    // Run 100 tests and check it retries somewhere in the window.
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
      // After an hour will possibly test again.
      'test_retry_min_interval' => 3600,
      // After a further hour will definitely test again.
      'test_retry_max_interval' => 7200,
    ];
    $cb = new CircuitBreaker('test', $config, $storageStub);
    for ($i = 0; $i < 100; $i++) {
      try {
        $cb->execute(function () {
          return 'ok';
        });
        break;
      }
      catch (\Exception $exception) {
        continue;
      }
    }
    $this->assertGreaterThan(9, $i);
    $this->assertLessThan(71, $i);

    $minutes = 50 + $i;

    echo "On this test run, retried after $minutes minutes\n";
  }

  /**
   * Test that retry does not occur when disallowed.
   */
  public function testRetryPrevented() {
    // Run 100 tests and check it never retries.
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
      ->expects($this->never())
      ->method('setBroken')
      ->with($this->equalTo(FALSE));
    $storageStub
      ->expects($this->never())
      ->method('purgeFailures');
    $config = [
      'threshold' => 5,
      // After an hour will possibly test again.
      'test_retry_min_interval' => 3600,
      // After a further hour will definitely test again.
      'test_retry_max_interval' => 7200,
    ];
    $cb = new CircuitBreaker('test', $config, $storageStub);
    $cb->setRetryAllowed(FALSE);
    for ($i = 0; $i < 100; $i++) {
      try {
        $cb->execute(function () {
          return 'ok';
        });
        break;
      }
      catch (\Exception $exception) {
        continue;
      }
    }
    $this->assertEquals(100, $i);

  }

  /**
   * Test an exception filter in string format.
   */
  public function testStringExceptionfilter() {
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
      // After an hour will possibly test again.
      'test_retry_min_interval' => 3600,
      // After a further hour will definitely test again.
      'test_retry_max_interval' => 7200,
    ];
    $cb = new CircuitBreaker('test', $config, $storageStub);
    try {
      $cb->execute(function () {
        throw new NotSeriousException();
      }, [], 'SomeException ' . NotSeriousException::class . ' AnotherException');
      $this->fail('Exception was not thrown');
    }
    catch (NotSeriousException $exception) {
      $this->assertTrue(TRUE);
    }
    catch (\Exception $exception) {
      $this->fail('Wrong exception thrown ' . get_class($exception));
    }

  }

  /**
   * Test an exception filter in callable format.
   */
  public function testCallableExceptionFilter() {
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
      // After an hour will possibly test again.
      'test_retry_min_interval' => 3600,
      // After a further hour will definitely test again.
      'test_retry_max_interval' => 7200,
    ];
    $cb = new CircuitBreaker('test', $config, $storageStub);
    try {
      $cb->execute(function () {
        throw new OtherException();
      }, [], function ($exception) {
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
      $this->assertTrue(TRUE);
    }
    catch (\Exception $exception) {
      $this->fail('Wrong exception thrown ' . get_class($exception));
    }
  }

}

/**
 * An exception that is filtered.
 */
class NotSeriousException extends \Exception {

}

/**
 * Another exception that is filtered.
 */
class OtherException extends NotSeriousException {

}
