<?php

declare(strict_types=1);

namespace PsrDiscovery\Implementations\Psr17;

use Psr\Http\Message\RequestFactoryInterface;
use PsrDiscovery\Collections\CandidatesCollection;
use PsrDiscovery\Contracts\Implementations\Psr17\RequestFactoriesContract;
use PsrDiscovery\Discover;
use PsrDiscovery\Entities\CandidateEntity;
use PsrDiscovery\Implementations\Implementation;

final class RequestFactories extends Implementation implements RequestFactoriesContract
{
    private static ?CandidatesCollection    $candidates = null;

    private static ?CandidatesCollection    $extendedCandidates = null;

    private static ?RequestFactoryInterface $singleton = null;

    private static ?RequestFactoryInterface $using = null;

    public static function add(CandidateEntity $candidate): void
    {
        self::$candidates ??= CandidatesCollection::create();
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
            package: 'psr-mock/http-factory-implementation',
            version: '^1.0',
            builder: static fn (string $class = '\PsrMock\Psr17\RequestFactory'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'guzzlehttp/psr7',
            version: '^2.0',
            builder: static fn (string $class = '\GuzzleHttp\Psr7\HttpFactory'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'nyholm/psr7',
            version: '^0.2.2',
            builder: static fn (string $class = '\Nyholm\Psr7\Factory\MessageFactory'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'nyholm/psr7',
            version: '^1.0',
            builder: static fn (string $class = '\Nyholm\Psr7\Factory\Psr17Factory'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'http-interop/http-factory-guzzle',
            version: '^0.2 | ^1.0',
            builder: static fn (string $class = '\Http\Factory\Guzzle\RequestFactory'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'zendframework/zend-diactoros',
            version: '^2.0',
            builder: static fn (string $class = '\Zend\Diactoros\RequestFactory'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'laminas/laminas-diactoros',
            version: '^2.0',
            builder: static fn (string $class = '\Laminas\Diactoros\RequestFactory'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'slim/psr7',
            version: '^1.0',
            builder: static fn (string $class = '\Slim\Psr7\Factory\RequestFactory'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'typo3/core',
            version: '^10.1 | ^11.0 | ^12.0',
            builder: static fn (string $class = '\TYPO3\CMS\Core\Http\RequestFactory'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'nimbly/capsule',
            version: '^2.0',
            builder: static fn (string $class = '\Nimbly\Capsule\Factory\RequestFactory'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'tuupola/http-factory',
            version: '^1.0.2',
            builder: static fn (string $class = '\Tuupola\Http\Factory\RequestFactory'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'httpsoft/http-message',
            version: '^1.0.4',
            builder: static fn (string $class = '\HttpSoft\Message\RequestFactory'): object => new $class(),
        ));

        return self::$candidates;
    }

    /**
     * @psalm-suppress MoreSpecificReturnType,LessSpecificReturnStatement
     */
    public static function discover(): ?RequestFactoryInterface
    {
        if (self::$using instanceof RequestFactoryInterface) {
            return self::$using;
        }

        return Discover::httpRequestFactory();
    }

    public static function discoveries(): array
    {
        return Discover::httpRequestFactories();
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

    public static function singleton(): ?RequestFactoryInterface
    {
        if (self::$using instanceof RequestFactoryInterface) {
            return self::$using;
        }

        return self::$singleton ??= self::discover();
    }

    public static function use(?RequestFactoryInterface $instance): void
    {
        self::$singleton = $instance;
        self::$using = $instance;
    }
}
