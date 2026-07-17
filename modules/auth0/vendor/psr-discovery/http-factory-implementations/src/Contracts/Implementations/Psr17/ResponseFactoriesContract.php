<?php

declare(strict_types=1);

namespace PsrDiscovery\Contracts\Implementations\Psr17;

use Psr\Http\Message\ResponseFactoryInterface;

interface ResponseFactoriesContract extends FactoriesContract
{
    /**
     * Discover and instantiate a matching implementation.
     */
    public static function discover(): ?ResponseFactoryInterface;

    /**
     * Return a singleton instance of a matching implementation.
     */
    public static function singleton(): ?ResponseFactoryInterface;

    /**
     * Use a specific implementation instance.
     *
     * @param ?ResponseFactoryInterface $instance
     */
    public static function use(?ResponseFactoryInterface $instance): void;
}
