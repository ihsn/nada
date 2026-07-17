<?php

declare(strict_types=1);

namespace PsrDiscovery\Implementations\Psr14;

use Psr\EventDispatcher\EventDispatcherInterface;
use PsrDiscovery\Collections\CandidatesCollection;
use PsrDiscovery\Contracts\Implementations\Psr14\EventDispatchersContract;
use PsrDiscovery\Discover;
use PsrDiscovery\Entities\CandidateEntity;
use PsrDiscovery\Implementations\Implementation;

final class EventDispatchers extends Implementation implements EventDispatchersContract
{
    private static ?CandidatesCollection     $candidates = null;

    private static ?CandidatesCollection     $extendedCandidates = null;

    private static ?EventDispatcherInterface $singleton = null;

    private static ?EventDispatcherInterface $using = null;

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
            package: 'psr-mock/event-dispatcher-implementation',
            version: '^1.0',
            builder: static fn (string $class = '\PsrMock\Psr14\EventDispatcher'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'symfony/event-dispatcher',
            version: '^4.3 | ^5.0 | ^6.0 | ^7.0',
            builder: static fn (string $class = '\Symfony\Component\EventDispatcher\EventDispatcher'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'league/event',
            version: '^3.0',
            builder: static fn (string $class = '\League\Event\EventDispatcher'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'yiisoft/event-dispatcher',
            version: '^1.0',
            builder: static fn (string $class = '\Yiisoft\EventDispatcher\Dispatcher\Dispatcher'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'carlosas/simple-event-dispatcher',
            version: '^0.1.0',
            builder: static fn (string $class = '\PHPAT\EventDispatcher\EventDispatcher'): object => new $class(),
        ));

        return self::$candidates;
    }

    /**
     * @psalm-suppress MoreSpecificReturnType,LessSpecificReturnStatement
     */
    public static function discover(): ?EventDispatcherInterface
    {
        if (self::$using instanceof EventDispatcherInterface) {
            return self::$using;
        }

        return Discover::eventDispatcher();
    }

    public static function discoveries(): array
    {
        return Discover::eventDispatchers();
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

    public static function singleton(): ?EventDispatcherInterface
    {
        if (self::$using instanceof EventDispatcherInterface) {
            return self::$using;
        }

        return self::$singleton ??= self::discover();
    }

    public static function use(?EventDispatcherInterface $instance): void
    {
        self::$singleton = $instance;
        self::$using = $instance;
    }
}
