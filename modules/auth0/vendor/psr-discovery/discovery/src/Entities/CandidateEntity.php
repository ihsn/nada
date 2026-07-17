<?php

declare(strict_types=1);

namespace PsrDiscovery\Entities;

use Closure;
use Stringable;

final class CandidateEntity implements Stringable
{
    public function __construct(
        private string $package,
        private string $version,
        private Closure $builder,
    ) {
    }

    public function __toString(): string
    {
        return $this->package . '@' . $this->version;
    }

    /**
     * @psalm-suppress MixedInferredReturnType,MixedReturnStatement
     */
    public function build(): object
    {
        return ($this->builder)();
    }

    public function getPackage(): string
    {
        return $this->package;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public static function create(
        string $package,
        string $version,
        Closure $builder,
    ): self {
        return new self($package, $version, $builder);
    }
}
