# Simple circuit breaker implementation for Drupal

## Introduction

See https://www.martinfowler.com/bliki/CircuitBreaker.html for an explanation of the circuit breaker concept.

This module provides a simple interface to integrate a circuit breaker with external services.

## Creating circuit breaker configurations

Use the administration interface at Administration -> Configuration -> Web services -> Circuit breakers to define circuit breaker 
configurations. Typically you will need one for each service to be integrated so you might have one for Salesforce, one for Shipwire
and so on.

The possible parameters for your circuit breaker are:

* Threshold: the number of failures before the breaker trips.
* Interval after which a retry may be made: once this interval has passed the breaker will randomly attempt a retry.
* Interval after which a retry will be made: once this interval has passed the breaker will immediately attempt a retry.

By setting the interval values appropriately you can have an immediate retry (both set to zero), a randomised retry after an interval 
(set the first to the interval desired and give the second a much higher value) or a fixed retry interval (set both to the same value).

You also have access in code to a parameter which turns the retry mechanism on or off, for finer grained control.

## Recommended configurations

There are two recommended configurations:

1. Retry via cron. If cron is run frequently enough, then you can use a cron hook to do all retries of your service. You'll need to set a suitable fixed retry interval (both values the same) and set retry as not allowed in all other places the circuit breaker is called.
1. Randomised retry. Set a first retry interval appropriate to the needs of your service and set the second one at a higher value depending on typical traffic (more traffic, longer interval).

## Code example

```php

function callMyService($service, $args) {
  $cb = \Drupal::service('circuit_breaker.factory')->load('my_service');
  // If circuit is broken, don't retry.
  $cb->setRetryAllowed(FALSE);
  try {
    return $cb->execute(function($args) use($service) {
       $service->invoke($args);
    }, $args, MyAllowableException::class);
  }
  catch(MyAllowableException $applicationError) {
    // handle application error
  }
  catch(Throwable $exception) {
    // handle other exceptions
  }
```

Note that the code which invokes your service should throw an exception whenever there is a network or other failure for which the circuit breaker may be tripped.

