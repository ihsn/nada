<?php

declare(strict_types=1);

namespace PsrDiscovery\Collections;

use InvalidArgumentException;
use PsrDiscovery\Entities\CandidateEntity;

use function array_key_exists;

final class CandidatesCollection
{
    /**
     * @param array<string,CandidateEntity> $candidates
     */
    public function __construct(
        private array $candidates = [],
    ) {
        foreach ($this->candidates as $candidate) {
            if (! $candidate instanceof CandidateEntity) {
                throw new InvalidArgumentException('CandidatesCollection::__construct only accepts an array of valid CandidateEntities.');
            }
        }
    }

    public function add(
        CandidateEntity $candidate,
    ): ?object {
        return $this->candidates[$candidate->getPackage()] = $candidate;
    }

    /**
     * @return array<string,CandidateEntity>
     */
    public function all(): array
    {
        return $this->candidates;
    }

    public function clear(): void
    {
        $this->candidates = [];
    }

    public function get(
        string $package,
    ): ?CandidateEntity {
        return $this->candidates[$package] instanceof CandidateEntity ? $this->candidates[$package] : null;
    }

    public function has(
        string $package,
    ): bool {
        return isset($this->candidates[$package]);
    }

    public function prefer(
        string $candidate,
    ): void {
        $candidates = $this->candidates;
        $candidate = trim($candidate);

        if ('' === $candidate || ! array_key_exists($candidate, $candidates)) {
            return;
        }

        /**
         * @var CandidateEntity $candidate
         */
        $candidate = $candidates[$candidate];

        unset($candidates[$candidate->getPackage()]);

        $candidates = array_reverse($candidates, true);
        $candidates[$candidate->getPackage()] = $candidate;
        $this->candidates = array_reverse($candidates, true);
    }

    public function remove(
        string $package,
    ): bool {
        $candidate = $this->candidates[$package] ?? null;

        if (! $candidate instanceof CandidateEntity) {
            return false;
        }

        unset($this->candidates[$package]);

        return true;
    }

    /**
     * @param CandidatesCollection $candidates
     */
    public function set(
        self $candidates,
    ): void {
        foreach ($candidates->all() as $candidateEntity) {
            if (! $candidateEntity instanceof CandidateEntity) {
                throw new InvalidArgumentException('CandidatesCollection::__construct only accepts an array of valid CandidateEntities.');
            }
        }

        $this->candidates = $candidates->all();
    }

    /**
     * @param array<string,CandidateEntity> $candidates
     */
    public static function create(
        array $candidates = [],
    ): self {
        return new self($candidates);
    }
}
