<?php

declare(strict_types=1);

namespace PsrDiscovery\Contracts\Implementations\Psr17;

use Psr\Http\Message\StreamFactoryInterface;

interface StreamFactoriesContract extends FactoriesContract
{
    /**
     * Discover and instantiate a matching implementation.
     */
    public static function discover(): ?StreamFactoryInterface;

    /**
     * Return a singleton instance of a matching implementation.
     */
    public static function singleton(): ?StreamFactoryInterface;

    /**
     * Use a specific implementation instance.
     *
     * @param ?StreamFactoryInterface $instance
     */
    public static function use(?StreamFactoryInterface $instance): void;
}
