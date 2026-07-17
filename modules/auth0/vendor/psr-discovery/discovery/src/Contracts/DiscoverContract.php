<?php

declare(strict_types=1);

namespace PsrDiscovery\Contracts;

use PsrDiscovery\Entities\CandidateEntity;

interface DiscoverContract
{
    /**
     * Returns a PSR-6 Cache, or null if one cannot is not found.
     *
     * Compatible libraries: https://packagist.org/providers/psr/cache-implementation
     *
     * @return null|\Psr\Cache\CacheItemPoolInterface A PSR-6 Cache, or null if one cannot be found.
     */
    public static function cache(): ?object;

    /**
     * Returns an array with all discovered PSR-6 Cache implementations. No implementations are instantiated by the discovery process.
     *
     * Compatible libraries: https://packagist.org/providers/psr/cache-implementation
     *
     * @return CandidateEntity[] An array of CandidateEntity objects representing all implementations discovered.
     */
    public static function caches(): array;

    /**
     * Returns a PSR-11 Container, or null if one cannot is not found.
     *
     * Compatible libraries: https://packagist.org/providers/psr/container-implementation
     *
     * @return null|\Psr\Container\ContainerInterface A PSR-11 Container, or null if one cannot be found.
     */
    public static function container(): ?object;

    /**
     * Returns an array with all PSR-11 Container implementations discovered. No implementations are instantiated by the discovery process.
     *
     * Compatible libraries: https://packagist.org/providers/psr/container-implementation
     *
     * @return CandidateEntity[] An array of CandidateEntity objects representing all implementations discovered.
     */
    public static function containers(): array;

    /**
     * Returns a PSR-14 Event Dispatcher, or null if one is not found.
     *
     * Compatible libraries: https://packagist.org/providers/psr/event-dispatcher-implementation
     *
     * @return null|\Psr\EventDispatcher\EventDispatcherInterface A PSR-14 Event Dispatcher, or null if one cannot be found.
     */
    public static function eventDispatcher(): ?object;

    /**
     * Returns an array with all PSR-14 Event Dispatcher implementations discovered. No implementations are instantiated by the discovery process.
     *
     * Compatible libraries: https://packagist.org/providers/psr/event-dispatcher-implementation
     *
     * @return CandidateEntity[] An array of CandidateEntity objects representing all implementations discovered.
     */
    public static function eventDispatchers(): array;

    /**
     * Returns a PSR-18 HTTP Client, or null if one is not found.
     *
     * Compatible providers: https://packagist.org/providers/psr/http-client-implementation
     *
     * @return null|\Psr\Http\Client\ClientInterface A PSR-18 HTTP Client, or null if one cannot be found.
     */
    public static function httpClient(): ?object;

    /**
     * Returns an array with all PSR-18 HTTP Client implementations discovered. No implementations are instantiated by the discovery process.
     *
     * Compatible providers: https://packagist.org/providers/psr/http-client-implementation
     *
     * @return CandidateEntity[] An array of CandidateEntity objects representing all implementations discovered.
     */
    public static function httpClients(): array;

    /**
     * Returns an array with all PSR-17 HTTP Request Factory implementations discovered. No implementations are instantiated by the discovery process.
     *
     * Compatible providers: https://packagist.org/providers/psr/http-factory-implementation
     *
     * @return CandidateEntity[] An array of CandidateEntity objects representing all implementations discovered.
     */
    public static function httpRequestFactories(): array;

    /**
     * Returns a PSR-17 HTTP Request factory, or null if one is not found.
     *
     * Compatible libraries: https://packagist.org/providers/psr/http-factory-implementation
     *
     * @return null|\Psr\Http\Message\RequestFactoryInterface A PSR-17 HTTP Request factory, or null if one cannot be found.
     */
    public static function httpRequestFactory(): ?object;

    /**
     * Returns an array with all PSR-17 HTTP Response Factory implementations discovered. No implementations are instantiated by the discovery process.
     *
     * Compatible providers: https://packagist.org/providers/psr/http-factory-implementation
     *
     * @return CandidateEntity[] An array of CandidateEntity objects representing all implementations discovered.
     */
    public static function httpResponseFactories(): array;

    /**
     * Returns a PSR-17 HTTP Response factory, or null if one is not found.
     *
     * Compatible libraries: https://packagist.org/providers/psr/http-factory-implementation
     *
     * @return null|\Psr\Http\Message\ResponseFactoryInterface A PSR-17 HTTP Response factory, or null if one cannot be found.
     */
    public static function httpResponseFactory(): ?object;

    /**
     * Returns an array with all PSR-17 HTTP Stream Factory implementations discovered. No implementations are instantiated by the discovery process.
     *
     * Compatible providers: https://packagist.org/providers/psr/http-factory-implementation
     *
     * @return CandidateEntity[] An array of CandidateEntity objects representing all implementations discovered.
     */
    public static function httpStreamFactories(): array;

    /**
     * Returns a PSR-17 HTTP Stream factory, or null if one is not found.
     *
     * Compatible libraries: https://packagist.org/providers/psr/http-factory-implementation
     *
     * @return null|\Psr\Http\Message\StreamFactoryInterface A PSR-17 HTTP Stream factory, or null if one cannot be found.
     */
    public static function httpStreamFactory(): ?object;

    /**
     * Returns an array with all PSR-17 HTTP Uploaded File Factory implementations discovered. No implementations are instantiated by the discovery process.
     *
     * Compatible providers: https://packagist.org/providers/psr/http-factory-implementation
     *
     * @return CandidateEntity[] An array of CandidateEntity objects representing all implementations discovered.
     */
    public static function httpUploadedFileFactories(): array;

    /**
     * Returns a PSR-17 HTTP Uploaded File factory, or null if one is not found.
     *
     * Compatible libraries: https://packagist.org/providers/psr/http-factory-implementation
     *
     * @return null|\Psr\Http\Message\UploadedFileFactoryInterface A PSR-17 HTTP UploadedFile factory, or null if one cannot be found.
     */
    public static function httpUploadedFileFactory(): ?object;

    /**
     * Returns an array with all PSR-17 HTTP Uri Factory implementations discovered. No implementations are instantiated by the discovery process.
     *
     * Compatible providers: https://packagist.org/providers/psr/http-factory-implementation
     *
     * @return CandidateEntity[] An array of CandidateEntity objects representing all implementations discovered.
     */
    public static function httpUriFactories(): array;

    /**
     * Returns a PSR-17 HTTP Uri factory, or null if one is not found.
     *
     * Compatible libraries: https://packagist.org/providers/psr/http-factory-implementation
     *
     * @return null|\Psr\Http\Message\UriFactoryInterface A PSR-17 HTTP Uri factory, or null if one cannot be found.
     */
    public static function httpUriFactory(): ?object;

    /**
     * Returns a PSR-3 Logger, or null if one is not found.
     *
     * Compatible libraries: https://packagist.org/providers/psr/log-implementation
     *
     * @return null|\Psr\Log\LoggerInterface A PSR-3 Logger, or null if one cannot be found.
     */
    public static function log(): ?object;

    /**
     * Returns an array with all PSR-3 Logger implementations discovered. No implementations are instantiated by the discovery process.
     *
     * Compatible providers: https://packagist.org/providers/psr/log-implementation
     *
     * @return CandidateEntity[] An array of CandidateEntity objects representing all implementations discovered.
     */
    public static function logs(): array;
}
