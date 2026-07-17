<?php

declare(strict_types=1);

namespace PsrDiscovery\Implementations\Psr18;

use Psr\Http\Client\ClientInterface;
use PsrDiscovery\Collections\CandidatesCollection;
use PsrDiscovery\Contracts\Implementations\Psr18\ClientsContract;
use PsrDiscovery\Discover;
use PsrDiscovery\Entities\CandidateEntity;
use PsrDiscovery\Implementations\Implementation;

final class Clients extends Implementation implements ClientsContract
{
    private static ?CandidatesCollection $candidates = null;

    private static ?CandidatesCollection $extendedCandidates = null;

    private static ?ClientInterface      $singleton = null;

    private static ?ClientInterface      $using = null;

    public static function add(CandidateEntity $candidate): void
    {
        parent::add($candidate);
        self::use(null);
    }

    /**
     * @psalm-suppress MixedInferredReturnType,MixedReturnStatement
     */
    public static function allCandidates(): CandidatesCollection
    {
        if (self::$extendedCandidates instanceof CandidatesCollection) {
            return self::$extendedCandidates;
        }

        self::$extendedCandidates = CandidatesCollection::create();
        self::$extendedCandidates->set(self::candidates());

        return self::$extendedCandidates;
    }

    /**
     * @psalm-suppress MixedInferredReturnType,MixedReturnStatement
     */
    public static function candidates(): CandidatesCollection
    {
        if (self::$candidates instanceof CandidatesCollection) {
            return self::$candidates;
        }

        self::$candidates = CandidatesCollection::create();

        self::$candidates->add(CandidateEntity::create(
            package: 'psr-mock/http-client-implementation',
            version: '^1.0',
            builder: static fn (string $class = '\PsrMock\Psr18\Client'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'php-http/mock-client',
            version: '^1.5',
            builder: static fn (string $class = '\Http\Mock\Client'): object => new $class(
                responseFactory: Discover::httpResponseFactory(),
            ),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'guzzlehttp/guzzle',
            version: '^7.0 | ^8.0',
            builder: static fn (string $class = '\GuzzleHttp\Client'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'symfony/http-client',
            version: '^4.3 | ^5.0 | ^6.0 | ^7.0 | ^8.0',
            builder: static fn (string $class = '\Symfony\Component\HttpClient\Psr18Client'): object => new $class(
                responseFactory: Discover::httpResponseFactory(),
                streamFactory: Discover::httpStreamFactory(),
            ),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'php-http/guzzle6-adapter',
            version: '^2.0',
            builder: static fn (string $class = '\Http\Adapter\Guzzle6\Client'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'php-http/guzzle7-adapter',
            version: '^0.1 | ^1.0',
            builder: static fn (string $class = '\Http\Adapter\Guzzle7\Client'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'php-http/curl-client',
            version: '^2.1',
            builder: static fn (string $class = '\Http\Client\Curl\Client'): object => new $class(
                responseFactory: Discover::httpResponseFactory(),
                streamFactory: Discover::httpStreamFactory(),
            ),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'kriswallsmith/buzz',
            version: '^1.0',
            builder: static fn (string $class = '\Buzz\Client\FileGetContents'): object => new $class(
                responseFactory: Discover::httpResponseFactory(),
            ),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'php-http/socket-client',
            version: '^2.0',
            builder: static fn (string $class = '\Http\Client\Socket\Client'): object => new $class(
                responseFactory: Discover::httpResponseFactory(),
            ),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'php-http/guzzle5-adapter',
            version: '^2.0',
            builder: static fn (string $class = '\Http\Adapter\Guzzle5\Client'): object => new $class(
                responseFactory: Discover::httpResponseFactory(),
            ),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'voku/httpful',
            version: '^2.2 | ^3.0',
            builder: static fn (string $class = '\Httpful\Client'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'joomla/http',
            version: '^2.0 | ^3.0',
            builder: static fn (string $class = '\Joomla\Http\Http'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'nimbly/shuttle',
            version: '^1.0',
            builder: static fn (string $class = '\Nimbly\Shuttle\Shuttle'): object => new $class(
                requestFactory: Discover::httpRequestFactory(),
                responseFactory: Discover::httpResponseFactory(),
                streamFactory: Discover::httpStreamFactory(),
                uriFactory: Discover::httpUriFactory(),
            ),
        ));

        return self::$candidates;
    }

    /**
     * @psalm-suppress MoreSpecificReturnType,LessSpecificReturnStatement
     */
    public static function discover(): ?ClientInterface
    {
        if (self::$using instanceof ClientInterface) {
            return self::$using;
        }

        return Discover::httpClient();
    }

    public static function discoveries(): array
    {
        return Discover::httpClients();
    }

    public static function prefer(string $package): void
    {
        self::$candidates ??= CandidatesCollection::create();
        parent::prefer($package);
        self::use(null);
    }

    public static function set(CandidatesCollection $candidates): void
    {
        self::$candidates ??= CandidatesCollection::create();
        parent::set($candidates);
        self::use(null);
    }

    public static function singleton(): ?ClientInterface
    {
        if (self::$using instanceof ClientInterface) {
            return self::$using;
        }

        return self::$singleton ??= self::discover();
    }

    public static function use(?ClientInterface $instance): void
    {
        self::$singleton = $instance;
        self::$using = $instance;
    }
}
