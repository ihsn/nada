<?php

declare(strict_types=1);

namespace PsrDiscovery\Implementations\Psr6;

use Psr\Cache\CacheItemPoolInterface;
use PsrDiscovery\Collections\CandidatesCollection;
use PsrDiscovery\Contracts\Implementations\Psr6\CachesContract;
use PsrDiscovery\Discover;
use PsrDiscovery\Entities\CandidateEntity;
use PsrDiscovery\Implementations\Implementation;

final class Caches extends Implementation implements CachesContract
{
    private static ?CandidatesCollection   $candidates = null;

    private static ?CandidatesCollection   $extendedCandidates = null;

    private static ?CacheItemPoolInterface $singleton = null;

    private static ?CacheItemPoolInterface $using = null;

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

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'symfony/cache',
            version: '^3.1 | ^4.0 | ^5.0 | ^6.0 | ^7.0',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'symfony/symfony',
            version: '^3.1.4 | ^4.0 | ^5.0 | ^6.0 | ^7.0',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'laminas/laminas-cache',
            version: '^2.8 | ^3.0 | ^4.0',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'cache/filesystem-adapter',
            version: '^1.0',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'cache/redis-adapter',
            version: '^1.0',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'tedivm/stash',
            version: '^0.14',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'cache/predis-adapter',
            version: '^1.0',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'cache/memcached-adapter',
            version: '^1.0',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'matthiasmullie/scrapbook',
            version: '^1.0',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'neos/cache',
            version: '^4.0 | ^5.0 | ^6.0| ^7.0| ^8.0',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'apix/cache',
            version: '^1.2',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'cache/chain-adapter',
            version: '^1.0',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'cache/doctrine-adapter',
            version: '^1.0',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'cache/memcache-adapter',
            version: '^1.0',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'psx/cache',
            version: '^1.0',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'cache/mongodb-adapter',
            version: '^1.0',
            builder: static fn () => null,
        ));

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
            package: 'psr-mock/cache-implementation',
            version: '^1.0',
            builder: static fn (string $class = '\PsrMock\Psr6\Cache'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'cache/array-adapter',
            version: '^1.0',
            builder: static fn (string $class = '\Cache\Adapter\PHPArray\ArrayCachePool'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'tedivm/stash',
            version: '^0.14',
            builder: static fn (string $class = '\Stash\Pool'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'cache/apcu-adapter',
            version: '^1.0',
            builder: static fn (string $class = '\Cache\Adapter\Apcu\ApcuCachePool'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'cache/void-adapter',
            version: '^1.0',
            builder: static fn (string $class = '\Cache\Adapter\Void\VoidCachePool'): object => new $class(),
        ));

        return self::$candidates;
    }

    /**
     * @psalm-suppress MoreSpecificReturnType,LessSpecificReturnStatement
     */
    public static function discover(): ?CacheItemPoolInterface
    {
        if (self::$using instanceof CacheItemPoolInterface) {
            return self::$using;
        }

        return Discover::cache();
    }

    public static function discoveries(): array
    {
        return Discover::caches();
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

    public static function singleton(): ?CacheItemPoolInterface
    {
        if (self::$using instanceof CacheItemPoolInterface) {
            return self::$using;
        }

        return self::$singleton ??= self::discover();
    }

    public static function use(?CacheItemPoolInterface $instance): void
    {
        self::$singleton = $instance;
        self::$using = $instance;
    }
}
