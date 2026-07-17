**Lightweight library that discovers available [PSR-18 HTTP Client](https://www.php-fig.org/psr/psr-18/) implementations by searching for a list of well-known classes that implement the relevant interface, and returns an instance of the first one that is found.**

This package is part of the [PSR Discovery](https://github.com/psr-discovery) utility suite, which also supports [PSR-17 HTTP Factories](https://github.com/psr-discovery/http-factory-implementations), [PSR-14 Event Dispatchers](https://github.com/psr-discovery/event-dispatcher-implementations), [PSR-11 Containers](https://github.com/psr-discovery/container-implementations), [PSR-6 Caches](https://github.com/psr-discovery/cache-implementations) and [PSR-3 Logs](https://github.com/psr-discovery/log-implementations).

This is largely intended for inclusion in libraries like SDKs that wish to support PSR-18 HTTP Clients without requiring hard dependencies on specific implementations or demanding extra configuration by users.

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

The following `psr/http-client-implementation` implementations are discovered and instantiated automatically:

-   [guzzlehttp/guzzle](https://github.com/guzzle/guzzle) ^7.0 | ^8.0
-   [joomla/http](https://github.com/voku/httpful) ^2.0 | ^3.0
-   [kriswallsmith/buzz](https://github.com/kriswallsmith/Buzz) ^1.0
-   [php-http/curl-client](https://github.com/php-http/curl-client) ^2.1
-   [php-http/guzzle5-adapter](https://github.com/php-http/guzzle5-adapter) ^2.0
-   [php-http/guzzle6-adapter](https://github.com/php-http/guzzle6-adapter) ^2.0
-   [php-http/guzzle7-adapter](https://github.com/php-http/guzzle7-adapter) ^0.1 | ^1.0
-   [php-http/socket-client](https://github.com/php-http/socket-client) ^2.0
-   [symfony/http-client](https://github.com/symfony/http-client) ^4.3 | ^5.0 | ^6.0 | ^7.0 | ^8.0
-   [voku/httpful](https://github.com/voku/httpful) ^2.2 | ^3.0
-   [nimbly/shuttle](https://github.com/nimbly/shuttle) ^1.0

The following mock implementations are also available:

-   [php-http/mock-client](https://github.com/php-http/mock-client) ^1.5
-   [psr-mock/http-client-implementation](https://github.com/psr-mock/http-client-implementation) ^1.0

If [a particular implementation](https://packagist.org/providers/psr/http-client-implementation) is missing that you'd like to see, please open a pull request adding support.

## Installation

```bash
composer require psr-discovery/http-client-implementations
```

## Usage

```php
use PsrDiscovery\Discover;

// Return an instance of the first discovered PSR-18 HTTP Client implementation.
$httpClient = Discover::httpClient();

// Send a request using the discovered HTTP Client.
$httpClient->sendRequest(...);
```

## Handling Failures

If the library is unable to discover a suitable PSR-18 implementation, the `Discover::httpClient()` discovery method will simply return `null`. This allows you to handle the failure gracefully, for example by falling back to a default implementation.

Example:

```php
use PsrDiscovery\Discover;

$httpClient = Discover::httpClient();

if ($httpClient === null) {
    // No suitable HTTP Client implementation was discovered.
    // Fall back to a default implementation.
    $httpClient = new DefaultHttpClient();
}
```

## Singletons

By default, the `Discover::httpClient()` method will always return a new instance of the discovered implementation. If you wish to use a singleton instance instead, simply pass `true` to the `$singleton` parameter of the discovery method.

Example:

```php
use PsrDiscovery\Discover;

// $httpClient1 !== $httpClient2 (default)
$httpClient1 = Discover::httpClient();
$httpClient2 = Discover::httpClient();

// $httpClient1 === $httpClient2
$httpClient1 = Discover::httpClient(singleton: true);
$httpClient2 = Discover::httpClient(singleton: true);
```

## Mocking Priority

This library will give priority to searching for a known, available mocking library before searching for a real implementation. This is to allow for easier testing of code that uses this library.

The expectation is that these mocking libraries will always be installed as development dependencies, and therefore if they are available, they are intended to be used.

## Preferring an Implementation

If you wish to prefer a specific implementation over others, you can `prefer()` it by package name:

```php
use PsrDiscovery\Discover;
use PsrDiscovery\Implementations\Psr18\Clients;

// Prefer the a specific implementation of PSR-18 over others.
Clients::prefer('guzzlehttp/guzzle');

// Return an instance of GuzzleHttp\Client,
// or the next available from the list of candidates,
// Returns null if none are discovered.
$client = Discover::httpClient();
```

This will cause the `httpClient()` method to return the preferred implementation if it is available, otherwise, it will fall back to the default behavior.

Note that assigning a preferred implementation will give it priority over the default preference of mocking libraries.

## Using a Specific Implementation

If you wish to force a specific implementation and ignore the rest of the discovery candidates, you can `use()` its package name:

```php
use PsrDiscovery\Discover;
use PsrDiscovery\Implementations\Psr18\Clients;

// Only discover a specific implementation of PSR-18.
Clients::use('guzzlehttp/guzzle');

// Return an instance of GuzzleHttp\Client,
// or null if it is not available.
$client = Discover::httpClient();
```

This will cause the `httpClient()` method to return the preferred implementation if it is available, otherwise, it will return `null`.

---

This library is not produced or endorsed by, or otherwise affiliated with, the PHP-FIG.
