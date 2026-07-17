<?php

declare(strict_types=1);

namespace PsrDiscovery\Implementations\Psr11;

use Psr\Container\ContainerInterface;
use PsrDiscovery\Collections\CandidatesCollection;
use PsrDiscovery\Contracts\Implementations\Psr11\ContainersContract;
use PsrDiscovery\Discover;
use PsrDiscovery\Entities\CandidateEntity;
use PsrDiscovery\Implementations\Implementation;

final class Containers extends Implementation implements ContainersContract
{
    private static ?CandidatesCollection $candidates = null;

    private static ?CandidatesCollection $extendedCandidates = null;

    private static ?ContainerInterface   $singleton = null;

    private static ?ContainerInterface   $using = null;

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
            package: 'laravel/framework',
            version: '^7.0 | ^8.0 | ^9.0 | ^10.0 | ^11.0',
            builder: static fn (string $class = '\Illuminate\Container\Container'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'illuminate/container',
            version: '^8.0 | ^9.0 | ^10.0',
            builder: static fn (string $class = '\Illuminate\Container\Container'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'symfony/symfony',
            version: '^3.3 | ^4.0 | ^5.0 | ^6.0 | ^7.0',
            builder: static fn (string $class = '\Symfony\Component\DependencyInjection\Container'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'symfony/dependency-injection',
            version: '^3.3 | ^4.0 | ^5.0 | ^6.0 | ^7.0',
            builder: static fn (string $class = '\Symfony\Component\DependencyInjection\Container'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'league/container',
            version: '^3.0 | ^4.0',
            builder: static fn (string $class = '\League\Container\Container'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'laminas/laminas-servicemanager',
            version: '^3.3',
            builder: static fn (string $class = '\Laminas\ServiceManager\ServiceManager'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'php-di/php-di',
            version: '^5.4.2 | ^6.0 | ^7.0',
            builder: static fn (string $class = '\DI\Container'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'yiisoft/di',
            version: '^1.0',
            builder: static fn (string $class = '\Yiisoft\Di\Container'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'silverstripe/framework',
            version: '^4.0',
            builder: static fn (string $class = '\SilverStripe\Core\Injector\Injector'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'aura/di',
            version: '^4.0',
            builder: static fn (string $class = '\Aura\Di\Container'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'joomla/di',
            version: '^1.5 | ^2.0',
            builder: static fn (string $class = '\Joomla\DI\Container'): object => new $class(),
        ));

        self::$candidates->add(CandidateEntity::create(
            package: 'contributte/psr11-container-interface',
            version: '^0.4',
            builder: static fn (string $class = '\Contributte\Psr11\Container'): object => new $class(),
        ));

        return self::$candidates;
    }

    /**
     * @psalm-suppress MoreSpecificReturnType,LessSpecificReturnStatement
     */
    public static function discover(): ?ContainerInterface
    {
        if (self::$using instanceof ContainerInterface) {
            return self::$using;
        }

        return Discover::container();
    }

    public static function discoveries(): array
    {
        return Discover::containers();
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

    public static function singleton(): ?ContainerInterface
    {
        if (self::$using instanceof ContainerInterface) {
            return self::$using;
        }

        return self::$singleton ??= self::discover();
    }

    public static function use(?ContainerInterface $instance): void
    {
        self::$singleton = $instance;
        self::$using = $instance;
    }
}
