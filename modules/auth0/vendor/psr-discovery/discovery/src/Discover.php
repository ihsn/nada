<?php

declare(strict_types=1);

namespace PsrDiscovery;

use Composer\InstalledVersions as Composer;
use Composer\Semver\VersionParser as Version;
use PsrDiscovery\Collections\CandidatesCollection;
use PsrDiscovery\Contracts\DiscoverContract;
use PsrDiscovery\Entities\CandidateEntity;
use PsrDiscovery\Exceptions\SupportPackageNotFoundException;

use Throwable;

final class Discover implements DiscoverContract
{
    /**
     * @var string
     */
    private const PSR_CACHE = '\Psr\Cache\CacheItemPoolInterface';

    /**
     * @var string
     */
    private const PSR_CONTAINER = '\Psr\Container\ContainerInterface';

    /**
     * @var string
     */
    private const PSR_EVENT_DISPATCHER = '\Psr\EventDispatcher\EventDispatcherInterface';

    /**
     * @var string
     */
    private const PSR_HTTP_CLIENT = '\Psr\Http\Client\ClientInterface';

    /**
     * @var string
     */
    private const PSR_HTTP_REQUEST_FACTORY = '\Psr\Http\Message\RequestFactoryInterface';

    /**
     * @var string
     */
    private const PSR_HTTP_RESPONSE_FACTORY = '\Psr\Http\Message\ResponseFactoryInterface';

    /**
     * @var string
     */
    private const PSR_HTTP_STREAM_FACTORY = '\Psr\Http\Message\StreamFactoryInterface';

    /**
     * @var string
     */
    private const PSR_HTTP_UPLOADED_FILE_FACTORY = '\Psr\Http\Message\UploadedFileFactoryInterface';

    /**
     * @var string
     */
    private const PSR_HTTP_URI_FACTORY = '\Psr\Http\Message\UriFactoryInterface';

    /**
     * @var string
     */
    private const PSR_LOG = '\Psr\Log\LoggerInterface';

    /**
     * @var CandidatesCollection[]
     */
    private static array $candidates = [];

    /**
     * @var CandidateEntity[]
     */
    private static array $discovered = [];

    /**
     * @var CandidatesCollection[]
     */
    private static array $extendedCandidates = [];

    /**
     * @var object[]
     */
    private static array $singletons = [];

    public static function cache(bool $singleton = false): ?object
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr6\Caches';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-6 Cache', 'psr-discovery/cache-implementations');
        }

        self::$candidates[self::PSR_CACHE] ??= $implementationsPackage::candidates();

        if ($singleton) {
            return self::singleton(self::PSR_CACHE);
        }

        return self::discover(self::PSR_CACHE);
    }

    public static function caches(): array
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr6\Cache';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-6 Cache', 'psr-discovery/cache-implementations');
        }

        self::$extendedCandidates[self::PSR_CACHE] ??= $implementationsPackage::allCandidates();

        return self::discoveries(self::PSR_CACHE);
    }

    public static function container(bool $singleton = false): ?object
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr11\Containers';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-11 Container', 'psr-discovery/container-implementations');
        }

        self::$candidates[self::PSR_CONTAINER] ??= $implementationsPackage::candidates();

        if ($singleton) {
            return self::singleton(self::PSR_CONTAINER);
        }

        return self::discover(self::PSR_CONTAINER);
    }

    public static function containers(): array
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr11\Containers';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-11 Container', 'psr-discovery/container-implementations');
        }

        self::$extendedCandidates[self::PSR_CONTAINER] ??= $implementationsPackage::allCandidates();

        return self::discoveries(self::PSR_CONTAINER);
    }

    public static function eventDispatcher(bool $singleton = false): ?object
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr14\EventDispatchers';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-14 Event Dispatcher', 'psr-discovery/event-dispatcher-implementations');
        }

        self::$candidates[self::PSR_EVENT_DISPATCHER] ??= $implementationsPackage::candidates();

        if ($singleton) {
            return self::singleton(self::PSR_EVENT_DISPATCHER);
        }

        return self::discover(self::PSR_EVENT_DISPATCHER);
    }

    public static function eventDispatchers(): array
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr14\EventDispatchers';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-14 Event Dispatcher', 'psr-discovery/event-dispatcher-implementations');
        }

        self::$extendedCandidates[self::PSR_EVENT_DISPATCHER] ??= $implementationsPackage::allCandidates();

        return self::discoveries(self::PSR_EVENT_DISPATCHER);
    }

    public static function httpClient(bool $singleton = false): ?object
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr18\Clients';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-18 HTTP Client', 'psr-discovery/http-client-implementations');
        }

        self::$candidates[self::PSR_HTTP_CLIENT] ??= $implementationsPackage::candidates();

        if ($singleton) {
            return self::singleton(self::PSR_HTTP_CLIENT);
        }

        return self::discover(self::PSR_HTTP_CLIENT);
    }

    public static function httpClients(): array
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr18\Clients';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-18 HTTP Client', 'psr-discovery/http-client-implementations');
        }

        self::$extendedCandidates[self::PSR_HTTP_CLIENT] ??= $implementationsPackage::allCandidates();

        return self::discoveries(self::PSR_HTTP_CLIENT);
    }

    public static function httpRequestFactories(): array
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr17\RequestFactories';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-17 HTTP Request Factory', 'psr-discovery/http-factory-implementations');
        }

        self::$extendedCandidates[self::PSR_HTTP_REQUEST_FACTORY] ??= $implementationsPackage::allCandidates();

        return self::discoveries(self::PSR_HTTP_REQUEST_FACTORY);
    }

    public static function httpRequestFactory(bool $singleton = false): ?object
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr17\RequestFactories';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-17 HTTP Request Factory', 'psr-discovery/http-factory-implementations');
        }

        self::$candidates[self::PSR_HTTP_REQUEST_FACTORY] ??= $implementationsPackage::candidates();

        if ($singleton) {
            return self::singleton(self::PSR_HTTP_REQUEST_FACTORY);
        }

        return self::discover(self::PSR_HTTP_REQUEST_FACTORY);
    }

    public static function httpResponseFactories(): array
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr17\ResponseFactories';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-17 HTTP Response Factory', 'psr-discovery/http-factory-implementations');
        }

        self::$extendedCandidates[self::PSR_HTTP_RESPONSE_FACTORY] ??= $implementationsPackage::allCandidates();

        return self::discoveries(self::PSR_HTTP_RESPONSE_FACTORY);
    }

    public static function httpResponseFactory(bool $singleton = false): ?object
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr17\ResponseFactories';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-17 HTTP Response Factory', 'psr-discovery/http-factory-implementations');
        }

        self::$candidates[self::PSR_HTTP_RESPONSE_FACTORY] ??= $implementationsPackage::candidates();

        if ($singleton) {
            return self::singleton(self::PSR_HTTP_RESPONSE_FACTORY);
        }

        return self::discover(self::PSR_HTTP_RESPONSE_FACTORY);
    }

    public static function httpStreamFactories(): array
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr17\StreamFactories';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-17 HTTP Stream Factory', 'psr-discovery/http-factory-implementations');
        }

        self::$extendedCandidates[self::PSR_HTTP_STREAM_FACTORY] ??= $implementationsPackage::allCandidates();

        return self::discoveries(self::PSR_HTTP_STREAM_FACTORY);
    }

    public static function httpStreamFactory(bool $singleton = false): ?object
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr17\StreamFactories';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-17 HTTP Stream Factory', 'psr-discovery/http-factory-implementations');
        }

        self::$candidates[self::PSR_HTTP_STREAM_FACTORY] ??= $implementationsPackage::candidates();

        if ($singleton) {
            return self::singleton(self::PSR_HTTP_STREAM_FACTORY);
        }

        return self::discover(self::PSR_HTTP_STREAM_FACTORY);
    }

    public static function httpUploadedFileFactories(): array
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr17\UploadedFileFactories';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-17 HTTP UploadedFile Factory', 'psr-discovery/http-factory-implementations');
        }

        self::$extendedCandidates[self::PSR_HTTP_UPLOADED_FILE_FACTORY] ??= $implementationsPackage::allCandidates();

        return self::discoveries(self::PSR_HTTP_UPLOADED_FILE_FACTORY);
    }

    public static function httpUploadedFileFactory(bool $singleton = false): ?object
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr17\UploadedFileFactories';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-17 HTTP UploadedFile Factory', 'psr-discovery/http-factory-implementations');
        }

        self::$candidates[self::PSR_HTTP_UPLOADED_FILE_FACTORY] ??= $implementationsPackage::candidates();

        if ($singleton) {
            return self::singleton(self::PSR_HTTP_UPLOADED_FILE_FACTORY);
        }

        return self::discover(self::PSR_HTTP_UPLOADED_FILE_FACTORY);
    }

    public static function httpUriFactories(): array
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr17\UriFactories';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-17 HTTP Uri Factory', 'psr-discovery/http-factory-implementations');
        }

        self::$extendedCandidates[self::PSR_HTTP_URI_FACTORY] ??= $implementationsPackage::allCandidates();

        return self::discoveries(self::PSR_HTTP_URI_FACTORY);
    }

    public static function httpUriFactory(bool $singleton = false): ?object
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr17\UriFactories';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-17 HTTP Uri Factory', 'psr-discovery/http-factory-implementations');
        }

        self::$candidates[self::PSR_HTTP_URI_FACTORY] ??= $implementationsPackage::candidates();

        if ($singleton) {
            return self::singleton(self::PSR_HTTP_URI_FACTORY);
        }

        return self::discover(self::PSR_HTTP_URI_FACTORY);
    }

    public static function log(bool $singleton = false): ?object
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr3\Logs';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-3 Logger', 'psr-discovery/log-implementations');
        }

        self::$candidates[self::PSR_LOG] ??= $implementationsPackage::candidates();

        if ($singleton) {
            return self::singleton(self::PSR_LOG);
        }

        return self::discover(self::PSR_LOG);
    }

    public static function logs(): array
    {
        $implementationsPackage = '\PsrDiscovery\Implementations\Psr3\Logs';

        if (! class_exists($implementationsPackage)) {
            throw new SupportPackageNotFoundException('PSR-3 Logger', 'psr-discovery/log-implementations');
        }

        self::$extendedCandidates[self::PSR_LOG] ??= $implementationsPackage::allCandidates();

        return self::discoveries(self::PSR_LOG);
    }

    /**
     * Discover an interface implementation from a list of well-known classes.
     *
     * @param string $interface The interface to discover.
     *
     * @return null|object The discovered implementation, or null if none could be found
     *
     * @psalm-suppress MixedInferredReturnType,MixedReturnStatement,MixedMethodCall
     */
    private static function discover(string $interface): ?object
    {
        // If we've already discovered an implementation, return it.
        if (isset(self::$discovered[$interface])) {
            return self::$discovered[$interface]->build();
        }

        // If we don't have any candidates, return null.
        if (! isset(self::$candidates[$interface])) {
            return null;
        }

        // Try to find a candidate that satisfies the version constraints.
        foreach (self::$candidates[$interface]->all() as $candidateEntity) {
            /** @var CandidateEntity $candidateEntity */
            try {
                if (Composer::satisfies(new Version(), $candidateEntity->getPackage(), $candidateEntity->getVersion())) {
                    self::$discovered[$interface] = $candidateEntity;

                    return $candidateEntity->build();
                }
            } catch (Throwable) {
            }
        }

        return null;
    }

    /**
     * Discover all available interface implementations from a list of well-known classes.
     *
     * @param string $interface The interface to discover.
     *
     * @return CandidateEntity[] The discovered implementations, or null if none could be found
     *
     * @psalm-suppress MixedInferredReturnType,MixedReturnStatement,MixedMethodCall
     */
    private static function discoveries(string $interface): array
    {
        if (! isset(self::$extendedCandidates[$interface])) {
            return [];
        }

        $discovered = [];

        // Try to find a candidate that satisfies the version constraints.
        foreach (self::$extendedCandidates[$interface]->all() as $candidateEntity) {
            try {
                /** @var CandidateEntity $candidateEntity */
                if (Composer::satisfies(new Version(), $candidateEntity->getPackage(), $candidateEntity->getVersion())) {
                    $discovered[] = $candidateEntity;
                }
            } catch (Throwable) {
            }
        }

        return $discovered;
    }

    /**
     * Discover an interface implementation from a list of well-known classes, and cache the resulting instance.
     *
     * @param string $interface The interface to discover.
     *
     * @return null|object The discovered implementation, or null if none could be found
     *
     * @psalm-suppress MixedInferredReturnType,MixedReturnStatement,MixedMethodCall
     */
    private static function singleton(string $interface): ?object
    {
        // If we've already discovered an implementation, return it.
        if (isset(self::$singletons[$interface])) {
            return self::$singletons[$interface];
        }

        $instance = self::discover($interface);

        if (null !== $instance) {
            self::$singletons[$interface] = $instance;
        }

        return $instance;
    }
}
