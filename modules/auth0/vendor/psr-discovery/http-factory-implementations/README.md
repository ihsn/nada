**Lightweight library that discovers available [PSR-17 HTTP Factory](https://www.php-fig.org/psr/psr-17/) implementations by searching for a list of well-known classes that implement the relevant interface, and returns an instance of the first one that is found.**

This package is part of the [PSR Discovery](https://github.com/psr-discovery) utility suite, which also supports [PSR-18 HTTP Clients](https://github.com/psr-discovery/http-client-implementations), [PSR-14 Event Dispatchers](https://github.com/psr-discovery/event-dispatcher-implementations), [PSR-11 Containers](https://github.com/psr-discovery/container-implementations), [PSR-6 Caches](https://github.com/psr-discovery/cache-implementations) and [PSR-3 Logs](https://github.com/psr-discovery/log-implementations).

This is largely intended for inclusion in libraries like SDKs that wish to support PSR-17 Factories without requiring hard dependencies on specific implementations or demanding extra configuration by users.

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

The following `psr/http-factory-implementation` implementations are discovered and instantiated automatically:

-   [guzzlehttp/psr7](https://github.com/guzzle/psr7) ^2.0
-   [http-interop/http-factory-guzzle](https://github.com/http-interop/http-factory-guzzle) ^0.2 | ^1.0
-   [httpsoft/http-message](https://github.com/httpsoft/http-message) ^1.0.4
-   [laminas/laminas-diactoros](https://github.com/laminas/laminas-diactoros) ^2.0
-   [nimbly/capsule](https://github.com/nimbly/Capsule) ^2.0
-   [nyholm/psr7](https://github.com/Nyholm/psr7) ^0.2.2 | ^1.0
-   [slim/psr7](https://github.com/slimphp/Slim-Psr7) ^1.0
-   [tuupola/http-factory](https://github.com/tuupola/http-factory) ^1.0.2
-   [typo3/core](https://github.com/TYPO3-CMS/core) ^10.1 | ^11.0 | ^12.0
-   [zendframework/zend-diactoros](https://github.com/zendframework/zend-diactoros) ^2.0

The following mock implementations are also available:

-   [psr-mock/http-factory-implementation](https://github.com/psr-mock/http-factory-implementation) ^1.0

If [a particular implementation](https://packagist.org/providers/psr/http-factory-implementation) is missing that you'd like to see, please open a pull request adding support.

## Installation

```bash
composer require --dev psr-discovery/http-factory-implementations
```

## Usage

```php
use PsrDiscovery\Discover;

// Returns a PSR-17 RequestFactoryInterface instance
$requestFactory = Discover::httpRequestFactory();

// Returns a PSR-17 ResponseFactoryInterface instance
$responseFactory = Discover::httpResponseFactory();

// Returns a PSR-17 StreamFactoryInterface instance
$streamFactory = Discover::httpStreamFactory();

// Returns a PSR-17 UploadedFileFactoryInterface instance
$uploadedFileFactory = Discover::httpUploadedFileFactory();

// Returns a PSR-17 UriFactoryInterface instance
$uriFactory = Discover::httpUriFactory();

// Returns a PSR-7 RequestInterface instance
$request = $requestFactory->createRequest('GET', 'https://example.com');
```

## Handling Failures

If the library is unable to discover a suitable PSR-17 implementation, the `Discover::httpRequestFactory()`, `Discover::httpResponseFactory()` or `Discover::httpStreamFactory()` discovery methods will simply return `null`. This allows you to handle the failure gracefully, for example by falling back to a default implementation.

Example:

```php
use PsrDiscovery\Discover;

$requestFactory = Discover::httpRequestFactory();

if ($requestFactory === null) {
    // No suitable HTTP RequestFactory implementation was discovered.
    // Fall back to a default implementation.
    $requestFactory = new DefaultRequestFactory();
}
```

## Singletons

By default, the `Discover::httpRequestFactory()`, `Discover::httpResponseFactory()` or `Discover::httpStreamFactory()` methods will always return a new instance of the discovered implementation. If you wish to use a singleton instance instead, simply pass `true` to the `$singleton` parameter of the discovery method.

Example:

```php
use PsrDiscovery\Discover;

// $httpResponseFactory1 !== $httpResponseFactory2 (default)
$httpResponseFactory1 = Discover::httpResponseFactory();
$httpResponseFactory2 = Discover::httpResponseFactory();

// $httpResponseFactory1 === $httpResponseFactory2
$httpResponseFactory1 = Discover::httpResponseFactory(singleton: true);
$httpResponseFactory2 = Discover::httpResponseFactory(singleton: true);
```

## Mocking Priority

This library will give priority to searching for a known, available mocking library before searching for a real implementation. This is to allow for easier testing of code that uses this library.

The expectation is that these mocking libraries will always be installed as development dependencies, and therefore if they are available, they are intended to be used.

## Preferring an Implementation

If you wish to prefer a specific implementation over others, you can `prefer()` it by package name:

```php
use PsrDiscovery\Discover;
use PsrDiscovery\Implementations\Psr17\RequestFactories;

// Prefer the a specific implementation of PSR-17 over others.
RequestFactories::prefer('nyholm/psr7');

// Return an instance of Nyholm\Psr7\Factory\Psr17Factory,
// or the next available from the list of candidates,
// Returns null if none are discovered.
$factory = Discover::httpRequestFactory();
```

In this case, this will cause the `httpRequestFactory()` method to return the preferred implementation if it is available, otherwise, it will fall back to the default behavior. The same applies to `httpResponseFactory()` and `httpStreamFactory()` when their relevant classes are configured similarly.

Note that assigning a preferred implementation will give it priority over the default preference of mocking libraries.

## Using a Specific Implementation

If you wish to force a specific implementation and ignore the rest of the discovery candidates, you can `use()` its package name:

```php
use PsrDiscovery\Discover;
use PsrDiscovery\Implementations\Psr17\ResponseFactories;

// Only discover a specific implementation of PSR-17.
ResponseFactories::use('nyholm/psr7');

// Return an instance of Nyholm\Psr7\Factory\Psr17Factory,
// or null if it is not available.
$factory = Discover::httpResponseFactory();
```

In this case, this will cause the `httpResponseFactory()` method to return the preferred implementation if it is available, otherwise, it will return `null`. The same applies to `httpRequestFactory()` and `httpStreamFactory()` when their relevant classes are configured similarly.

---

This library is not produced or endorsed by, or otherwise affiliated with, the PHP-FIG.
