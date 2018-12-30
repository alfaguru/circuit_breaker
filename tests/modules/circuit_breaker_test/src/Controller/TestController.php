<?php

namespace Drupal\circuit_breaker_test\Controller;

use Drupal\circuit_breaker\CircuitBreakerInterface;
use Drupal\circuit_breaker\Storage\StorageManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class TestController extends ControllerBase {

  /**
   * @var \Drupal\circuit_breaker\CircuitBreakerInterface
   */
  protected $circuitBreaker;

  /**
   * @var \Drupal\circuit_breaker\Storage\StorageManagerInterface
   */
  protected $storageManager;

  /**
   *
   */
  public function __construct(CircuitBreakerInterface $circuitBreaker, StorageManagerInterface $storageManager) {
    $this->circuitBreaker = $circuitBreaker;
    $this->storageManager = $storageManager;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    $circuitBreakerFactory = $container->get('circuit_breaker.factory');
    return new static(
      $circuitBreakerFactory->load('test'),
      $container->get('circuit_breaker.storage_manager')
    );
  }

  /**
   *
   */
  public function pageAlwaysOk(Request $request) {
    $data = $request->get('data');
    $doNotRetry = $request->get('doNotRetry');
    if ($doNotRetry) {
      $this->circuitBreaker->setRetryAllowed(FALSE);
    }
    try {
      $result = $this->circuitBreaker->execute(function ($data) {
        return $data;
      }, [$data]);
      return [
        '#type' => 'markup',
        '#markup' => 'Test passed OK. ' . $result,
        '#cache' => ['max-age' => 0],
      ];
    }
    catch (\Exception $exception) {
      return [
        '#type' => 'markup',
        '#markup' => 'Test failed. ' . $exception->getMessage() . '<br>' .
        'Time now = ' . time() . '. Last failure time = ' . $this->storageManager->getStorage('test')->lastFailureTime(),
        '#cache' => ['max-age' => 0],
      ];
    }
  }

  /**
   *
   */
  public function pageAlwaysFails(Request $request) {
    $doNotRetry = $request->get('doNotRetry');
    if ($doNotRetry) {
      $this->circuitBreaker->setRetryAllowed(FALSE);
    }
    $results = [];
    for ($i = 1; $i < 8; $i++) {
      try {
        $result = $this->circuitBreaker->execute(function ($data) {
          throw new \Exception($data);
        }, ['failure']);
      }
      catch (\Exception $exception) {
        $results[] = "$i Exception ({$exception->getMessage()})";
      }
    }
    return [
      '#theme' => 'item_list',
      '#items' => $results,
      '#cache' => ['max-age' => 0],
    ];

  }

  /**
   *
   */
  public function timeMachine(Request $request) {
    $timestamp = $request->get('tval');
    if ($timestamp) {
      $storage = $this->storageManager->getStorage('test');
      $storage->setlastFailureTime($timestamp);
      $storage->persist();
    }
    return [
      '#type' => 'markup',
      '#markup' => "OK set time to $timestamp time now " . time(),
      '#cache' => ['max-age' => 0],
    ];
  }

}
