**Lightweight library that discovers available [PSR-14 Event Dispatcher](https://www.php-fig.org/psr/psr-14/) implementations by searching for a list of well-known classes that implement the relevant interface, and returns an instance of the first one that is found.**

This package is part of the [PSR Discovery](https://github.com/psr-discovery) utility suite, which also supports [PSR-18 HTTP Clients](https://github.com/psr-discovery/http-client-implementations), [PSR-17 HTTP Factories](https://github.com/psr-discovery/http-factory-implementations), [PSR-11 Containers](https://github.com/psr-discovery/container-implementations), [PSR-6 Caches](https://github.com/psr-discovery/cache-implementations) and [PSR-3 Logs](https://github.com/psr-discovery/log-implementations).

This is largely intended for inclusion in libraries like SDKs that wish to support PSR-14 Event Dispatchers without requiring hard dependencies on specific implementations or demanding extra configuration by users.

-   [Requirements](#requirements)
-   [Implementations](#implementations)
-   [Installation](#installation)
-   [Usage](#usage)
-   [Handling Failures](#handling-failures)
-   [Exceptions](#exceptions)
-   [Singletons](#singletons)
-   [Mocking Priority](#mocking-priority)
-   [Preferring an Implementation](#preferring-an-implementation)
-   [Using a Specific Implementation](#using-a-specific-implementation)

## Requirements

-   PHP 8.2+
-   Composer 2.0+

Successful discovery requires the presence of a compatible implementation in the host application. This library does not install any implementations for you.

## Implementations

The following `psr/event-dispatcher-implementation` implementations are discovered and instantiated automatically:

-   [carlosas/simple-event-dispatcher](https://github.com/carlosas/simple-event-dispatcher) ^0.1.0
-   [league/event](https://github.com/thephpleague/event) ^3.0
-   [symfony/event-dispatcher](https://github.com/symfony/event-dispatcher) ^4.3 | ^5.0 | ^6.0 | ^7.0
-   [yiisoft/event-dispatcher](https://github.com/yiisoft/event-dispatcher) ^1.0

The following mock implementations are also available:

-   [psr-mock/event-dispatcher-implementation](https://github.com/psr-mock/event-dispatcher-implementation) ^1.0

If [a particular implementation](https://packagist.org/providers/psr/event-dispatcher-implementation) is missing that you'd like to see, please open a pull request adding support.

## Installation

```bash
composer require psr-discovery/event-dispatcher-implementations
```

## Usage

```php
use PsrDiscovery\Discover;

// Return an instance of the first discovered PSR-14 Event Dispatcher implementation.
$eventDispatcher = Discover::eventDispatcher();

// Send a request using the discovered Event Dispatcher.
eventDispatcher->dispatch(...);
```

## Handling Failures

If the library is unable to discover a suitable PSR-14 implementation, the `Discover::eventDispatcher()` discovery method will simply return `null`. This allows you to handle the failure gracefully, for example by falling back to a default implementation.

Example:

```php
use PsrDiscovery\Discover;

$eventDispatcher = Discover::eventDispatcher();

if ($eventDispatcher === null) {
    // No suitable Event Dispatcher implementation was discovered.
    // Fall back to a default implementation.
    $eventDispatcher = new DefaultEventDispatcher();
}
```

## Singletons

By default, the `Discover::eventDispatcher()` method will always return a new instance of the discovered implementation. If you wish to use a singleton instance instead, simply pass `true` to the `$singleton` parameter of the discovery method.

Example:

```php
use PsrDiscovery\Discover;

// $eventDispatcher1 !== $eventDispatcher2 (default)
$eventDispatcher1 = Discover::eventDispatcher();
$eventDispatcher2 = Discover::eventDispatcher();

// $eventDispatcher1 === $eventDispatcher2
$eventDispatcher1 = Discover::eventDispatcher(singleton: true);
$eventDispatcher2 = Discover::eventDispatcher(singleton: true);
```

## Mocking Priority

This library will give priority to searching for a known, available mocking library before searching for a real implementation. This is to allow for easier testing of code that uses this library.

The expectation is that these mocking libraries will always be installed as development dependencies, and therefore if they are available, they are intended to be used.

## Preferring an Implementation

If you wish to prefer a specific implementation over others, you can `prefer()` it by package name:

```php
use PsrDiscovery\Discover;
use PsrDiscovery\Implementations\Psr14\EventDispatchers;

// Prefer the a specific implementation of PSR-14 over others.
EventDispatchers::prefer('league/event');

// Return an instance of League\Event\Dispatcher,
// or the next available from the list of candidates,
// Returns null if none are discovered.
$dispatcher = Discover::eventDispatcher();
```

This will cause the `eventDispatcher()` method to return the preferred implementation if it is available, otherwise, it will fall back to the default behavior.

Note that assigning a preferred implementation will give it priority over the default preference of mocking libraries.

## Using a Specific Implementation

If you wish to force a specific implementation and ignore the rest of the discovery candidates, you can `use()` its package name:

```php
use PsrDiscovery\Discover;
use PsrDiscovery\Implementations\Psr14\EventDispatchers;

// Only discover a specific implementation of PSR-14.
EventDispatchers::use('league/event');

// Return an instance of League\Event\Dispatcher,
// or null if it is not available.
$dispatcher = Discover::eventDispatcher();
```

This will cause the `eventDispatcher()` method to return the preferred implementation if it is available, otherwise, it will return `null`.

---

This library is not produced or endorsed by, or otherwise affiliated with, the PHP-FIG.
