**Lightweight library that discovers available [PSR-3 Log](https://www.php-fig.org/psr/psr-3/) implementations by searching for a list of well-known classes that implement the relevant interface, and returns an instance of the first one that is found.**

This package is part of the [PSR Discovery](https://github.com/psr-discovery) utility suite, which also supports [PSR-18 HTTP Clients](https://github.com/psr-discovery/http-client-implementations), [PSR-17 HTTP Factories](https://github.com/psr-discovery/http-factory-implementations), [PSR-14 Event Dispatchers](https://github.com/psr-discovery/event-dispatcher-implementations), [PSR-11 Containers](https://github.com/psr-discovery/container-implementations) and [PSR-6 Caches](https://github.com/psr-discovery/cache-implementations).

This is largely intended for inclusion in libraries like SDKs that wish to support PSR-3 Logs without requiring hard dependencies on specific implementations or demanding extra configuration by users.

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

The following `psr/log-implementation` implementations are discovered and instantiated automatically:

-   [apix/log](https://github.com/laminas/laminas-log) ^1.0
-   [graylog2/gelf-php](https://github.com/bzikarsky/gelf-php) ^1.2 | ^2.0
-   [laminas/laminas-log](https://github.com/laminas/laminas-log) ^2.9
-   [yiisoft/log](https://github.com/yiisoft/log) ^1.0 | ^2.0

The following implementations can be discovered, but require manual instantiation due to their configuration requirements:

-   [google/cloud-logging](https://github.com/googleapis/google-cloud-php-logging) ^1.22.1
-   [monolog/monolog](https://github.com/Seldaek/monolog) ^1.11 | ^2.0 | ^3.0
-   [neos/flow-log](https://github.com/neos/flow-log) ^5.0 | ^6.0 | ^7.0 | ^8.0

The following mock implementations are also available:

-   [colinodell/psr-testlogger](https://github.com/colinodell/psr-testlogger) ^1.0
-   [metasyntactical/inmemory-logger](https://github.com/MetaSyntactical/inmemory-logger) ^1.0
-   [psr-mock/log-implementation](https://github.com/psr-mock/log-implementation) ^1.0

If [a particular implementation](https://packagist.org/providers/psr/log-implementation) is missing that you'd like to see, please open a pull request adding support.

## Installation

```bash
composer require psr-discovery/log-implementations
```

## Usage

```php
use PsrDiscovery\Discover;

// Return an instance of the first discovered PSR-3 Log implementation.
$log = Discover::log();

$log->info('Hello World!');
```

You can also use `Discover::logs()` to retrieve an array with all discovered implementations. This is useful if you want to support implementations that can't be instantiated without configuration.

```php
use PsrDiscovery\Discover;

$logs = Discover::logs();

foreach ($logs as $log) {
    echo sprintf('Discovered %s v%s', $log->getPackage(), $log->getVersion());
}
```

## Handling Failures

If the library is unable to discover a suitable PSR-6 implementation, the `Discover::log()` discovery method will simply return `null`. This allows you to handle the failure gracefully, for example by falling back to a default implementation.

Example:

```php
use PsrDiscovery\Discover;

$log = Discover::log();

if ($log === null) {
    // No suitable Log implementation was discovered.
    // Fall back to a default implementation.
    $log = new DefaultLog();
}
```

## Singletons

By default, the `Discover::log()` method will always return a new instance of the discovered implementation. If you wish to use a singleton instance instead, simply pass `true` to the `$singleton` parameter of the discovery method.

Example:

```php
use PsrDiscovery\Discover;

// $log1 !== $log2 (default)
$log1 = Discover::log();
$log2 = Discover::log();

// $log1 === $log2
$log1 = Discover::log(singleton: true);
$log2 = Discover::log(singleton: true);
```

## Mocking Priority

This library will give priority to searching for a known, available mocking library before searching for a real implementation. This is to allow for easier testing of code that uses this library.

The expectation is that these mocking libraries will always be installed as development dependencies, and therefore if they are available, they are intended to be used.

## Preferring an Implementation

If you wish to prefer a specific implementation over others, you can `prefer()` it by package name:

```php
use PsrDiscovery\Discover;
use PsrDiscovery\Implementations\Psr3\Logs;

// Prefer the a specific implementation of PSR-3 over others.
Logs::prefer('league/container');

// Return an instance of League\Container\Container,
// or the next available from the list of candidates,
// Returns null if none are discovered.
$log = Discover::log();
```

This will cause the `log()` method to return the preferred implementation if it is available, otherwise, it will fall back to the default behavior.

Note that assigning a preferred implementation will give it priority over the default preference of mocking libraries.

## Using a Specific Implementation

If you wish to force a specific implementation and ignore the rest of the discovery candidates, you can `use()` its package name:

```php
use PsrDiscovery\Discover;
use PsrDiscovery\Implementations\Psr3\Logs;

// Only discover a specific implementation of PSR-3.
Logs::use('league/container');

// Return an instance of League\Container\Container,
// or null if it is not available.
$log = Discover::log();
```

This will cause the `log()` method to return the preferred implementation if it is available, otherwise, it will return `null`.

---

This library is not produced or endorsed by, or otherwise affiliated with, the PHP-FIG.
