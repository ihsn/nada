<?php

declare(strict_types=1);

namespace PsrDiscovery\Contracts\Implementations\Psr17;

use Psr\Http\Message\UploadedFileFactoryInterface;

interface UploadedFileFactoriesContract extends FactoriesContract
{
    /**
     * Discover and instantiate a matching implementation.
     */
    public static function discover(): ?UploadedFileFactoryInterface;

    /**
     * Return a singleton instance of a matching implementation.
     */
    public static function singleton(): ?UploadedFileFactoryInterface;

    /**
     * Use a specific implementation instance.
     *
     * @param ?UploadedFileFactoryInterface $instance
     */
    public static function use(?UploadedFileFactoryInterface $instance): void;
}
