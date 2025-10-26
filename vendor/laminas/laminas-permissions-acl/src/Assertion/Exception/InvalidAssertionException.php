<?php

declare(strict_types=1);

namespace Laminas\Permissions\Acl\Assertion\Exception;

use InvalidArgumentException;
use Laminas\Permissions\Acl\Exception\ExceptionInterface;

class InvalidAssertionException extends InvalidArgumentException implements ExceptionInterface
{
}
