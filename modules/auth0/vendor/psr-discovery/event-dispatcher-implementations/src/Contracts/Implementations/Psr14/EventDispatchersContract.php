<?php

declare(strict_types=1);

namespace PsrDiscovery\Contracts\Implementations\Psr14;

use Psr\EventDispatcher\EventDispatcherInterface;
use PsrDiscovery\Collections\CandidatesCollection;
use PsrDiscovery\Contracts\Implementations\ImplementationContract;
use PsrDiscovery\Entities\CandidateEntity;

interface EventDispatchersContract extends ImplementationContract
{
    /**
     * Add a potential candidate to the discovery process.
     *
     * @param CandidateEntity $candidate The candidate to add.
     */
    public static function add(CandidateEntity $candidate): void;

    /**
     * Return all potential candidates, including those that cannot be instantiated automatically.
     */
    public static function allCandidates(): CandidatesCollection;

    /**
     * Return the candidates collection.
     */
    public static function candidates(): CandidatesCollection;

    /**
     * Discover and instantiate a matching implementation.
     */
    public static function discover(): ?EventDispatcherInterface;

    /**
     * Returns an array with all discovered implementations.
     *
     * @return CandidateEntity[]
     */
    public static function discoveries(): array;

    /**
     * Prefer a package over all others.
     *
     * @param string $package The package to prefer.
     */
    public static function prefer(string $package): void;

    /**
     * Override the discovery process' candidates collection with a new one.
     *
     * @param CandidatesCollection $candidates The new candidates collection.
     */
    public static function set(CandidatesCollection $candidates): void;

    /**
     * Return a singleton instance of a matching implementation.
     */
    public static function singleton(): ?EventDispatcherInterface;

    /**
     * Use a specific implementation instance.
     *
     * @param ?EventDispatcherInterface $instance
     */
    public static function use(?EventDispatcherInterface $instance): void;
}
