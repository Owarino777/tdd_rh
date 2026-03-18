<?php

declare(strict_types=1);

namespace TddRh\Domain;

final class IsOuvrable
{
    public function isOuvrable(\DateTimeImmutable $date): bool
    {
        return (int) $date->format('N') !== 7;
    }
}
