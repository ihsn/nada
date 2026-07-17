**Lightweight library that discovers available [PSR-6 Cache](https://www.php-fig.org/psr/psr-6/) implementations by searching for a list of well-known classes that implement the relevant interface, and returns an instance of the first one that is found.**

This package is part of the [PSR Discovery](https://github.com/psr-discovery) utility suite, which also supports [PSR-18 HTTP Clients](https://github.com/psr-discovery/http-client-implementations), [PSR-17 HTTP Factories](https://github.com/psr-discovery/http-factory-implementations), [PSR-14 Event Dispatcher](https://github.com/psr-discovery/event-dispatcher-implementations), [PSR-11 Containers](https://github.com/psr-discovery/container-implementations) and [PSR-3 Logs](https://github.com/psr-discovery/log-implementations).

This is largely intended for inclusion in libraries like SDKs that wish to support PSR-6 Caches without requiring hard dependencies on specific implementations or demanding extra configuration by users.

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

The following `psr/cache-implementation` implementations are discovered and instantiated automatically:

-   [cache/apcu-adapter](https://github.com/php-cache/apcu-adapter) ^1.0
-   [cache/array-adapter](https://github.com/php-cache/array-adapter) ^1.0
-   [cache/void-adapter](https://github.com/php-cache/void-adapter) ^1.0
-   [tedivm/stash](https://github.com/tedious/Stash) ^0.14

The following implementations can be discovered, but require manual instantiation due to their configuration requirements:

-   [apix/cache](https://github.com/apix/cache) ^1.2
-   [cache/chain-adapter](https://github.com/php-cache/chain-adapter) ^1.0
-   [cache/doctrine-adapter](https://github.com/php-cache/doctrine-adapter) ^1.0
-   [cache/filesystem-adapter](https://github.com/php-cache/filesystem-adapter) ^1.0
-   [cache/memcache-adapter](https://github.com/php-cache/memcache-adapter) ^1.0
-   [cache/memcached-adapter](https://github.com/php-cache/memcached-adapter) ^1.0
-   [cache/mongodb-adapter](https://github.com/php-cache/mongodb-adapter) ^1.0
-   [cache/predis-adapter](https://github.com/php-cache/predis-adapter) ^1.0
-   [cache/redis-adapter](https://github.com/php-cache/redis-adapter) ^1.0
-   [laminas/laminas-cache](https://github.com/laminas/laminas-cache) ^2.8 | ^3.0 | ^4.0
-   [matthiasmullie/scrapbook](https://github.com/matthiasmullie/scrapbook) ^1.0
-   [neos/cache](https://github.com/neos/cache) ^4.0 | ^ 5.0 | ^ 6.0 | ^ 7.0 | ^ 8.0
-   [psx/cache](https://github.com/apioo/psx-cache) ^1.0
-   [symfony/cache](https://github.com/symfony/cache) ^3.1 | ^4.0 | ^5.0 | ^6.0 | ^7.0
-   [symfony/symfony](https://github.com/symfony/symfony) ^3.1.4 | ^4.0 | ^5.0 | ^6.0 | ^7.0
-   [tedivm/stash](https://github.com/tedious/Stash) ^0.14

The following mock implementations are also available:

-   [psr-mock/cache-implementation](https://github.com/psr-mock/cache-implementation) ^1.0

If [a particular implementation](https://packagist.org/providers/psr/cache-implementation) is missing that you'd like to see, please open a pull request adding support.

## Installation

```bash
composer require psr-discovery/cache-implementations
```

## Usage

```php
use PsrDiscovery\Discover;

// Return an instance of the first discovered PSR-6 Cache implementation.
$cache = Discover::cache();

$cache->set('foo', 'bar');
```

You can also use `Discover::caches()` to retrieve an array with all discovered implementations. This is useful if you want to support implementations that can't be instantiated without configuration.

```php
use PsrDiscovery\Discover;

$caches = Discover::caches();

foreach ($caches as $cache) {
    echo sprintf('Discovered %s v%s', $cache->getPackage(), $cache->getVersion());
}
```

## Handling Failures

If the library is unable to discover a suitable PSR-6 implementation, the `Discover::cache()` discovery method will simply return `null`. This allows you to handle the failure gracefully, for example by falling back to a default implementation.

Example:

```php
use PsrDiscovery\Discover;

$cache = Discover::cache();

if ($cache === null) {
    // No suitable Cache implementation was discovered.
    // Fall back to a default implementation.
    $cache = new DefaultCache();
}
```

## Singletons

By default, the `Discover::cache()` method will always return a new instance of the discovered implementation. If you wish to use a singleton instance instead, simply pass `true` to the `$singleton` parameter of the discovery method.

Example:

```php
use PsrDiscovery\Discover;

// $cache1 !== $cache2 (default)
$cache1 = Discover::cache();
$cache2 = Discover::cache();

// $cache1 === $cache2
$cache1 = Discover::cache(singleton: true);
$cache2 = Discover::cache(singleton: true);
```

## Mocking Priority

This library will give priority to searching for a known, available mocking library before searching for a real implementation. This is to allow for easier testing of code that uses this library.

The expectation is that these mocking libraries will always be installed as development dependencies, and therefore if they are available, they are intended to be used.

## Preferring an Implementation

If you wish to prefer a specific implementation over others, you can `prefer()` it by package name:

```php
use PsrDiscovery\Discover;
use PsrDiscovery\Implementations\Psr6\Caches;

// Prefer the a specific implementation of PSR-6 over others.
Caches::prefer('league/container');

// Return an instance of League\Container\Container,
// or the next available from the list of candidates,
// Returns null if none are discovered.
$cache = Discover::cache();
```

This will cause the `cache()` method to return the preferred implementation if it is available, otherwise, it will fall back to the default behavior.

Note that assigning a preferred implementation will give it priority over the default preference of mocking libraries.

## Using a Specific Implementation

If you wish to force a specific implementation and ignore the rest of the discovery candidates, you can `use()` its package name:

```php
use PsrDiscovery\Discover;
use PsrDiscovery\Implementations\Psr6\Caches;

// Only discover a specific implementation of PSR-6.
Caches::use('league/container');

// Return an instance of League\Container\Container,
// or null if it is not available.
$cache = Discover::cache();
```

This will cause the `cache()` method to return the preferred implementation if it is available, otherwise, it will return `null`.

---

This library is not produced or endorsed by, or otherwise affiliated with, the PHP-FIG.
