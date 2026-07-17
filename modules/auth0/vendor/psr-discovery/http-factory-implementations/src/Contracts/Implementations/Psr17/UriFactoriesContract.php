<?php

declare(strict_types=1);

namespace PsrDiscovery\Contracts\Implementations\Psr17;

use Psr\Http\Message\UriFactoryInterface;

interface UriFactoriesContract extends FactoriesContract
{
    /**
     * Discover and instantiate a matching implementation.
     */
    public static function discover(): ?UriFactoryInterface;

    /**
     * Return a singleton instance of a matching implementation.
     */
    public static function singleton(): ?UriFactoryInterface;

    /**
     * Use a specific implementation instance.
     *
     * @param ?UriFactoryInterface $instance
     */
    public static function use(?UriFactoryInterface $instance): void;
}
