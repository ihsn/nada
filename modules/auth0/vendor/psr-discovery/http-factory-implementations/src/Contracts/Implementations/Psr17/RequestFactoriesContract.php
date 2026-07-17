<?php

declare(strict_types=1);

namespace PsrDiscovery\Contracts\Implementations\Psr17;

use Psr\Http\Message\RequestFactoryInterface;

interface RequestFactoriesContract extends FactoriesContract
{
    /**
     * Discover and instantiate a matching implementation.
     */
    public static function discover(): ?RequestFactoryInterface;

    /**
     * Return a singleton instance of a matching implementation.
     */
    public static function singleton(): ?RequestFactoryInterface;

    /**
     * Use a specific implementation instance.
     *
     * @param ?RequestFactoryInterface $instance
     */
    public static function use(?RequestFactoryInterface $instance): void;
}
