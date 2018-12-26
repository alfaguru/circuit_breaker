<?php

namespace Drupal\circuit_breaker_test\Controller;


use Drupal\circuit_breaker\CircuitBreaker;
use Drupal\circuit_breaker\CircuitBreakerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class TestController extends ControllerBase {

  /**
   * @var \Drupal\circuit_breaker\CircuitBreakerInterface
   */
  protected $circuitBreaker;

  public function __construct(CircuitBreakerInterface $circuitBreaker) {
    $this->circuitBreaker = $circuitBreaker;
  }

  public static function create(ContainerInterface $container) {
    $circuitBreakerFactory = $container->get('circuit_breaker.factory');
    return new static($circuitBreakerFactory->load('test'));
  }

  public function pageAlwaysOK(Request $request ) {
    $data = $request->get('data');
    $result = $this->circuitBreaker->execute(function ($data) {
      return $data;
    }, [$data]);
    return [
      '#type' => 'markup',
      '#markup' => 'Test passed OK. ' . $data,
    ];
  }

  public function pageAlwaysFails() {
    return [
      '#type' => 'markup',
      '#markup' => 'Not implemented yet',
    ];

  }


}