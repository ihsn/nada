<?php

declare(strict_types=1);

namespace PsrDiscovery\Implementations\Psr3;

use Psr\Log\LoggerInterface;
use PsrDiscovery\Collections\CandidatesCollection;
use PsrDiscovery\Contracts\Implementations\Psr3\LogsContract;
use PsrDiscovery\Discover;
use PsrDiscovery\Entities\CandidateEntity;
use PsrDiscovery\Implementations\Implementation;

final class Logs extends Implementation implements LogsContract
{
    private static ?CandidatesCollection $candidates = null;

    private static ?CandidatesCollection $extendedCandidates = null;

    private static ?LoggerInterface      $singleton = null;

    private static ?LoggerInterface      $using = null;

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
            package: 'monolog/monolog',
            version: '^1.11 | ^2.0 | ^3.0',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'google/cloud-logging',
            version: '^1.22',
            builder: static fn () => null,
        ));

        self::$extendedCandidates->add(CandidateEntity::create(
            package: 'neos/flow-log',
            version: '^5.0 | ^6.0 | ^7.0 | ^8.0',
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
            package: 'psr-mock/log-implementation',
            version: '^1.0',
            builder: static fn (string $class = '\PsrMock\Psr3\Log'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'colinodell/psr-testlogger',
            version: '^1.0',
            builder: static fn (string $class = '\ColinODell\PsrTestLogger\TestLogger'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'metasyntactical/inmemory-logger',
            version: '^1.0',
            builder: static fn (string $class = '\MetaSyntactical\Log\InMemoryLogger\InMemoryLogger'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'graylog2/gelf-php',
            version: '^1.2 | ^2.0',
            builder: static fn (string $class = '\Gelf\Logger'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'laminas/laminas-log',
            version: '^2.9',
            builder: static fn (string $class = '\Laminas\Log\Logger'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'apix/log',
            version: '^1.0',
            builder: static fn (string $class = '\Apix\Log\Logger'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'yiisoft/log',
            version: '^1.0 | ^2.0',
            builder: static fn (string $class = '\Yiisoft\Log\Logger'): object => new $class(),
        ));

        return self::$candidates;
    }

    /**
     * @psalm-suppress MoreSpecificReturnType,LessSpecificReturnStatement
     */
    public static function discover(): ?LoggerInterface
    {
        if (self::$using instanceof LoggerInterface) {
            return self::$using;
        }

        return Discover::log();
    }

    public static function discoveries(): array
    {
        return Discover::logs();
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

    public static function singleton(): ?LoggerInterface
    {
        if (self::$using instanceof LoggerInterface) {
            return self::$using;
        }

        return self::$singleton ??= self::discover();
    }

    public static function use(?LoggerInterface $instance): void
    {
        self::$singleton = $instance;
        self::$using = $instance;
    }
}
