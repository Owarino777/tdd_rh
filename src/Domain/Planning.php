<?php

declare(strict_types=1);

namespace TddRh\Domain;

final class Planning
{
    public function __construct(private readonly array $rawPlanning)
    {
    }

    public function worksOn(\DateTimeImmutable $date, string $period): bool
    {
        $weekKey = $date->format('o-\\WW');
        $source = $this->rawPlanning['weeks'][$weekKey] ?? $this->rawPlanning['default'] ?? [];
        $dayKey = strtolower($date->format('l'));

        return (bool) ($source[$dayKey][$period] ?? false);
    }
}
