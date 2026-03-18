<?php

declare(strict_types=1);

namespace TddRh\Domain;

final class FrenchPublicHolidayProvider
{
    public function isHoliday(string $date): bool
    {
        $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if ($dateTime === false || $dateTime->format('Y-m-d') !== $date) {
            throw new \InvalidArgumentException('La date doit etre au format YYYY-MM-DD.');
        }

        $year = (int) $dateTime->format('Y');
        $holidays = $this->holidaysForYear($year);

        return in_array($date, $holidays, true);
    }

    /**
     * @return list<string>
     */
    private function holidaysForYear(int $year): array
    {
        $easter = (new \DateTimeImmutable('@' . easter_date($year)))->setTimezone(new \DateTimeZone('Europe/Paris'));

        return [
            sprintf('%d-01-01', $year),
            sprintf('%d-05-01', $year),
            sprintf('%d-05-08', $year),
            sprintf('%d-07-14', $year),
            sprintf('%d-08-15', $year),
            sprintf('%d-11-01', $year),
            sprintf('%d-11-11', $year),
            sprintf('%d-12-25', $year),
            $easter->modify('+1 day')->format('Y-m-d'),
            $easter->modify('+39 day')->format('Y-m-d'),
            $easter->modify('+50 day')->format('Y-m-d'),
        ];
    }
}
