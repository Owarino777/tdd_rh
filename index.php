<?php

declare(strict_types=1);

use TddRh\Application\LeaveCalculator;

require_once __DIR__ . '/vendor/autoload.php';

function calculJoursConges(
    string $dateDebut,
    string $periodeDebut,
    string $dateFin,
    string $periodeFin,
    array $planning,
    string $typeCalcul,
    int $typeDemande
): float {
    $calculator = new LeaveCalculator();

    return $calculator->calculJoursConges(
        $dateDebut,
        $periodeDebut,
        $dateFin,
        $periodeFin,
        $planning,
        $typeCalcul,
        $typeDemande
    );
}