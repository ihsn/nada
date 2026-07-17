<?php

declare(strict_types=1);

namespace PsrDiscovery\Exceptions;

use Exception;

use function sprintf;

final class SupportPackageNotFoundException extends Exception
{
    /**
     * @var string
     */
    public const MSG_PACKAGE_REQUIRED = 'Discovery of %s implementations requires the %s package';

    public function __construct(
        string $interface,
        string $package,
    ) {
        parent::__construct(sprintf(self::MSG_PACKAGE_REQUIRED, trim($interface), trim($package)));
    }
}
