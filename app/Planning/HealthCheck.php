<?php

namespace App\Planning;

use App\Enums\HealthState;

/**
 * One measure of the healthy-day check, with the wording the prototype shows.
 */
final readonly class HealthCheck
{
    public function __construct(
        public string $label,
        public string $value,
        public string $target,
        public HealthState $state,
    ) {}

    public function isMet(): bool
    {
        return $this->state === HealthState::Ok;
    }
}
