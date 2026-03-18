<?php

declare(strict_types=1);

namespace TddRh\Application;

use TddRh\Domain\FrenchPublicHolidayProvider;
use TddRh\Domain\IsOuvrable;
use TddRh\Domain\Planning;

final class LeaveCalculator
{
    public function __construct(
        private readonly FrenchPublicHolidayProvider $holidayProvider = new FrenchPublicHolidayProvider(),
        private readonly IsOuvrable $isOuvrable = new IsOuvrable()
    ) {
    }

    public function checkFerie(string $date): bool
    {
        return $this->holidayProvider->isHoliday($date);
    }

    public function checkIsOuvrable(string $date): bool
    {
        return $this->isOuvrable->isOuvrable($this->parseDate($date));
    }

    public function calculJoursConges(
        string $dateDebut,
        string $periodeDebut,
        string $dateFin,
        string $periodeFin,
        array $planning,
        string $typeCalcul,
        int $typeDemande
    ): float {
        $domainPlanning = new Planning($planning);

        $halfDays = $this->countDemiJourneeLoupee(
            $dateDebut,
            $periodeDebut,
            $dateFin,
            $periodeFin,
            $domainPlanning,
            $typeCalcul,
            $typeDemande
        );

        return $halfDays / 2;
    }

    public function countDemiJourneeLoupee(
        string $dateDebut,
        string $periodeDebut,
        string $dateFin,
        string $periodeFin,
        Planning $planning,
        string $typeCalcul,
        int $typeDemande
    ): int {
        $this->assertInput($dateDebut, $periodeDebut, $dateFin, $periodeFin, $typeCalcul, $typeDemande);

        $start = ['date' => $this->parseDate($dateDebut), 'period' => $periodeDebut];
        $end = ['date' => $this->parseDate($dateFin), 'period' => $periodeFin];

        if ($this->compareSlots($start, $end) > 0) {
            throw new \InvalidArgumentException('La date de debut doit etre inferieure ou egale a la date de fin.');
        }

        $requestedHalfDays = $this->countHalfDaysInRange($start, $end, $planning, $typeCalcul, $typeDemande);

        if ($typeCalcul === 'ouvrable' && $typeDemande === 1) {
            $returnToWork = $this->findReturnToWorkSlot($end, $planning);
            $afterEnd = $this->nextSlot($end);
            $majoredHalfDays = $this->countMajorationHalfDays($afterEnd, $returnToWork);

            return $requestedHalfDays + min(5, $majoredHalfDays);
        }

        return $requestedHalfDays;
    }

    private function assertInput(
        string $dateDebut,
        string $periodeDebut,
        string $dateFin,
        string $periodeFin,
        string $typeCalcul,
        int $typeDemande
    ): void {
        $allowedPeriods = ['morning', 'afternoon'];
        $allowedCalculs = ['ouvre', 'ouvrable'];
        $allowedDemandes = [1, 2, 3, 4];

        if (!in_array($periodeDebut, $allowedPeriods, true) || !in_array($periodeFin, $allowedPeriods, true)) {
            throw new \InvalidArgumentException('La periode doit etre morning ou afternoon.');
        }

        if (!in_array($typeCalcul, $allowedCalculs, true)) {
            throw new \InvalidArgumentException('Le type de calcul doit etre ouvre ou ouvrable.');
        }

        if (!in_array($typeDemande, $allowedDemandes, true)) {
            throw new \InvalidArgumentException('Le type de demande doit etre compris entre 1 et 4.');
        }

        $this->parseDate($dateDebut);
        $this->parseDate($dateFin);
    }

    private function parseDate(string $date): \DateTimeImmutable
    {
        $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if ($dateTime === false || $dateTime->format('Y-m-d') !== $date) {
            throw new \InvalidArgumentException('La date doit etre au format YYYY-MM-DD.');
        }

        return $dateTime;
    }

    /**
     * @param array{date:\DateTimeImmutable, period:string} $start
     * @param array{date:\DateTimeImmutable, period:string} $end
     */
    private function countHalfDaysInRange(
        array $start,
        array $end,
        Planning $planning,
        string $typeCalcul,
        int $typeDemande
    ): int {
        $count = 0;
        $cursor = $start;

        while ($this->compareSlots($cursor, $end) <= 0) {
            if ($this->shouldCountSlot($cursor, $planning, $typeCalcul, $typeDemande)) {
                $count++;
            }

            $cursor = $this->nextSlot($cursor);
        }

        return $count;
    }

    /**
     * @param array{date:\DateTimeImmutable, period:string} $slot
     */
    private function shouldCountSlot(array $slot, Planning $planning, string $typeCalcul, int $typeDemande): bool
    {
        $date = $slot['date'];
        $period = $slot['period'];

        if ($this->holidayProvider->isHoliday($date->format('Y-m-d'))) {
            return false;
        }

        if ($typeCalcul === 'ouvre') {
            if ((int) $date->format('N') > 5) {
                return false;
            }

            if ($typeDemande === 1) {
                return true;
            }

            return $planning->worksOn($date, $period);
        }

        if (!$this->isOuvrable->isOuvrable($date)) {
            return false;
        }

        if ($typeDemande === 1) {
            return true;
        }

        return $planning->worksOn($date, $period);
    }

    /**
     * @param array{date:\DateTimeImmutable, period:string} $slot
     */
    private function nextSlot(array $slot): array
    {
        if ($slot['period'] === 'morning') {
            return ['date' => $slot['date'], 'period' => 'afternoon'];
        }

        return ['date' => $slot['date']->modify('+1 day'), 'period' => 'morning'];
    }

    /**
     * @param array{date:\DateTimeImmutable, period:string} $left
     * @param array{date:\DateTimeImmutable, period:string} $right
     */
    private function compareSlots(array $left, array $right): int
    {
        if ($left['date'] < $right['date']) {
            return -1;
        }

        if ($left['date'] > $right['date']) {
            return 1;
        }

        if ($left['period'] === $right['period']) {
            return 0;
        }

        return $left['period'] === 'morning' ? -1 : 1;
    }

    /**
     * @param array{date:\DateTimeImmutable, period:string} $end
     * @return array{date:\DateTimeImmutable, period:string}
     */
    private function findReturnToWorkSlot(array $end, Planning $planning): array
    {
        $cursor = $this->nextSlot($end);

        for ($i = 0; $i < 1500; $i++) {
            $date = $cursor['date']->format('Y-m-d');

            if (
                !$this->holidayProvider->isHoliday($date)
                && $planning->worksOn($cursor['date'], $cursor['period'])
            ) {
                return $cursor;
            }

            $cursor = $this->nextSlot($cursor);
        }

        throw new \RuntimeException('Impossible de trouver un retour au travail.');
    }

    /**
     * @param array{date:\DateTimeImmutable, period:string} $start
     * @param array{date:\DateTimeImmutable, period:string} $returnToWork
     */
    private function countMajorationHalfDays(array $start, array $returnToWork): int
    {
        $count = 0;
        $cursor = $start;

        while ($this->compareSlots($cursor, $returnToWork) < 0) {
            $date = $cursor['date'];

            if (
                !$this->holidayProvider->isHoliday($date->format('Y-m-d'))
                && $this->isOuvrable->isOuvrable($date)
            ) {
                $count++;
            }

            $cursor = $this->nextSlot($cursor);
        }

        return $count;
    }
}
