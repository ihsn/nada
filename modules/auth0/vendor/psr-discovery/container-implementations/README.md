**Lightweight library that discovers available [PSR-11 Container](https://www.php-fig.org/psr/psr-11/) implementations by searching for a list of well-known classes that implement the relevant interface, and returns an instance of the first one that is found.**

This package is part of the [PSR Discovery](https://github.com/psr-discovery) utility suite, which also supports [PSR-18 HTTP Clients](https://github.com/psr-discovery/http-client-implementations), [PSR-17 HTTP Factories](https://github.com/psr-discovery/http-factory-implementations), [PSR-14 Event Dispatcher](https://github.com/psr-discovery/event-dispatcher-implementations), [PSR-6 Caches](https://github.com/psr-discovery/cache-implementations) and [PSR-3 Logs](https://github.com/psr-discovery/log-implementations).

This is largely intended for inclusion in libraries like SDKs that wish to support PSR-11 Containers without requiring hard dependencies on specific implementations or demanding extra configuration by users.

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

The following `psr/container-implementation` implementations are discovered and instantiated automatically:

-   [aura/di](https://github.com/auraphp/Aura.Di) ^4.0
-   [contributte/psr11-container-interface](https://github.com/contributte/psr11-container-interface) ^0.4
-   [illuminate/container](https://github.com/illuminate/container) ^8.0 | ^9.0 | ^10.0
-   [joomla/di](https://github.com/joomla-framework/di) ^1.5 | ^2.0
-   [laminas/laminas-servicemanager](https://github.com/laminas/laminas-servicemanager) ^3.3
-   [laravel/framework](https://github.com/laravel/framework) ^7.0 | ^8.0 | ^9.0 | ^10.0
-   [league/container](https://github.com/thephpleague/container) ^3.0 | ^4.0
-   [php-di/php-di](https://github.com/PHP-DI/PHP-DI) ^5.4.2 | ^6.0 | ^7.0
-   [silverstripe/framework](https://github.com/silverstripe/silverstripe-framework) ^4.0
-   [symfony/dependency-injection](https://github.com/symfony/dependency-injection) ^3.3 | ^4.0 | ^5.0 | ^6.0 | ^7.0
-   [symfony/symfony](https://github.com/symfony/symfony) ^3.3 | ^4.0 | ^5.0 | ^6.0 | ^7.0
-   [yiisoft/di](https://github.com/yiisoft/di) ^1.0

The following mock implementations are also available:

-   [psr-mock/container-implementation](https://github.com/psr-mock/container-implementation) ^1.0

If [a particular implementation](https://packagist.org/providers/psr/container-implementation) is missing that you'd like to see, please open a pull request adding support.

## Installation

```bash
composer require psr-discovery/container-implementations
```

## Usage

```php
use PsrDiscovery\Discover;

// Return an instance of the first discovered PSR-11 Container implementation.
$container = Discover::container();

$container->get(...)
```

## Handling Failures

If the library is unable to discover a suitable PSR-11 implementation, the `Discover::container()` discovery method will simply return `null`. This allows you to handle the failure gracefully, for example by falling back to a default implementation.

Example:

```php
use PsrDiscovery\Discover;

$container = Discover::container();

if ($container === null) {
    // No suitable Container implementation was discovered.
    // Fall back to a default implementation.
    $container = new DefaultContainer();
}
```

## Singletons

By default, the `Discover::container()` method will always return a new instance of the discovered implementation. If you wish to use a singleton instance instead, simply pass `true` to the `$singleton` parameter of the discovery method.

Example:

```php
use PsrDiscovery\Discover;

// $container1 !== $container2 (default)
$container1 = Discover::container();
$container2 = Discover::container();

// $container1 === $container2
$container1 = Discover::container(singleton: true);
$container2 = Discover::container(singleton: true);
```

## Mocking Priority

This library will give priority to searching for a known, available mocking library before searching for a real implementation. This is to allow for easier testing of code that uses this library.

The expectation is that these mocking libraries will always be installed as development dependencies, and therefore if they are available, they are intended to be used.

## Preferring an Implementation

If you wish to prefer a specific implementation over others, you can `prefer()` it by package name:

```php
use PsrDiscovery\Discover;
use PsrDiscovery\Implementations\Psr11\Containers;

// Prefer the a specific implementation of PSR-11 over others.
Containers::prefer('league/container');

// Return an instance of League\Container\Container,
// or the next available from the list of candidates,
// Returns null if none are discovered.
$container = Discover::container();
```

This will cause the `container()` method to return the preferred implementation if it is available, otherwise, it will fall back to the default behavior.

Note that assigning a preferred implementation will give it priority over the default preference of mocking libraries.

## Using a Specific Implementation

If you wish to force a specific implementation and ignore the rest of the discovery candidates, you can `use()` its package name:

```php
use PsrDiscovery\Discover;
use PsrDiscovery\Implementations\Psr11\Containers;

// Only discover a specific implementation of PSR-11.
Containers::use('league/container');

// Return an instance of League\Container\Container,
// or null if it is not available.
$container = Discover::container();
```

This will cause the `container()` method to return the preferred implementation if it is available, otherwise, it will return `null`.

---

This library is not produced or endorsed by, or otherwise affiliated with, the PHP-FIG.
