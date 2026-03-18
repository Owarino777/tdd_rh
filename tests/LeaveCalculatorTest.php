<?php

declare(strict_types=1);

namespace TddRh\Tests;

use PHPUnit\Framework\TestCase;
use TddRh\Application\LeaveCalculator;

final class LeaveCalculatorTest extends TestCase
{
    private LeaveCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new LeaveCalculator();
    }

    public function testCheckFerieDetectsNationalHoliday(): void
    {
        self::assertTrue($this->calculator->checkFerie('2026-07-14'));
        self::assertFalse($this->calculator->checkFerie('2026-07-15'));
    }

    public function testClassicFullWeekPaidLeaveInOuvreAndOuvrable(): void
    {
        $planning = $this->standardPlanning();

        $joursOuvre = $this->calculator->calculJoursConges(
            '2026-07-06',
            'morning',
            '2026-07-10',
            'afternoon',
            $planning,
            'ouvre',
            1
        );

        $joursOuvrable = $this->calculator->calculJoursConges(
            '2026-07-06',
            'morning',
            '2026-07-10',
            'afternoon',
            $planning,
            'ouvrable',
            1
        );

        self::assertSame(5.0, $joursOuvre);
        self::assertSame(6.0, $joursOuvrable);
    }

    public function testWednesdayMorningPaidLeaveCountsHalfDay(): void
    {
        $planning = $this->standardPlanning();

        $jours = $this->calculator->calculJoursConges(
            '2026-07-15',
            'morning',
            '2026-07-15',
            'morning',
            $planning,
            'ouvre',
            1
        );

        self::assertSame(0.5, $jours);
    }

    public function testFridayPaidLeaveHasMajorationInOuvrable(): void
    {
        $planning = $this->standardPlanning();

        $jours = $this->calculator->calculJoursConges(
            '2026-07-10',
            'morning',
            '2026-07-10',
            'afternoon',
            $planning,
            'ouvrable',
            1
        );

        self::assertSame(2.0, $jours);
    }

    public function testFridayUnpaidLeaveHasNoMajorationInOuvrable(): void
    {
        $planning = $this->standardPlanning();

        $jours = $this->calculator->calculJoursConges(
            '2026-07-10',
            'morning',
            '2026-07-10',
            'afternoon',
            $planning,
            'ouvrable',
            2
        );

        self::assertSame(1.0, $jours);
    }

    public function testHolidayIsNeverCounted(): void
    {
        $planning = $this->standardPlanning();

        $jours = $this->calculator->calculJoursConges(
            '2026-07-13',
            'morning',
            '2026-07-14',
            'afternoon',
            $planning,
            'ouvre',
            1
        );

        self::assertSame(1.0, $jours);
    }

    public function testPartTimeInOuvreKeepsFiveDaysForPaidFullWeek(): void
    {
        $planning = $this->partTimePlanning();

        $jours = $this->calculator->calculJoursConges(
            '2026-07-06',
            'morning',
            '2026-07-10',
            'afternoon',
            $planning,
            'ouvre',
            1
        );

        self::assertSame(5.0, $jours);
    }

    public function testShiftedPlanningMondayAfternoonPaidLeave(): void
    {
        $planning = $this->shiftedPlanning();

        $jours = $this->calculator->calculJoursConges(
            '2026-07-13',
            'afternoon',
            '2026-07-13',
            'afternoon',
            $planning,
            'ouvre',
            1
        );

        self::assertSame(0.5, $jours);
    }

    public function testFragmentedPlanningTuesdayPaidLeave(): void
    {
        $planning = $this->fragmentedPlanning();

        $jours = $this->calculator->calculJoursConges(
            '2026-07-07',
            'morning',
            '2026-07-07',
            'afternoon',
            $planning,
            'ouvre',
            1
        );

        self::assertSame(1.0, $jours);
    }

    public function testPlanningCanChangeFromOneWeekToAnother(): void
    {
        $planning = $this->weeklyChangingPlanning();

        $jours = $this->calculator->calculJoursConges(
            '2026-07-20',
            'morning',
            '2026-07-20',
            'afternoon',
            $planning,
            'ouvre',
            2
        );

        self::assertSame(0.0, $jours);
    }

    public function testInvalidPeriodThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->calculJoursConges(
            '2026-07-13',
            'invalid',
            '2026-07-14',
            'afternoon',
            $this->standardPlanning(),
            'ouvre',
            1
        );
    }

    private function standardPlanning(): array
    {
        return [
            'default' => [
                'monday' => ['morning' => true, 'afternoon' => true],
                'tuesday' => ['morning' => true, 'afternoon' => true],
                'wednesday' => ['morning' => true, 'afternoon' => true],
                'thursday' => ['morning' => true, 'afternoon' => true],
                'friday' => ['morning' => true, 'afternoon' => true],
                'saturday' => ['morning' => false, 'afternoon' => false],
                'sunday' => ['morning' => false, 'afternoon' => false],
            ],
        ];
    }

    private function partTimePlanning(): array
    {
        return [
            'default' => [
                'monday' => ['morning' => true, 'afternoon' => true],
                'tuesday' => ['morning' => true, 'afternoon' => true],
                'wednesday' => ['morning' => true, 'afternoon' => false],
                'thursday' => ['morning' => false, 'afternoon' => true],
                'friday' => ['morning' => true, 'afternoon' => true],
                'saturday' => ['morning' => false, 'afternoon' => false],
                'sunday' => ['morning' => false, 'afternoon' => false],
            ],
        ];
    }

    private function shiftedPlanning(): array
    {
        return [
            'default' => [
                'monday' => ['morning' => false, 'afternoon' => true],
                'tuesday' => ['morning' => true, 'afternoon' => true],
                'wednesday' => ['morning' => true, 'afternoon' => true],
                'thursday' => ['morning' => true, 'afternoon' => true],
                'friday' => ['morning' => true, 'afternoon' => true],
                'saturday' => ['morning' => true, 'afternoon' => false],
                'sunday' => ['morning' => false, 'afternoon' => false],
            ],
        ];
    }

    private function fragmentedPlanning(): array
    {
        return [
            'default' => [
                'monday' => ['morning' => true, 'afternoon' => true],
                'tuesday' => ['morning' => true, 'afternoon' => true],
                'wednesday' => ['morning' => true, 'afternoon' => false],
                'thursday' => ['morning' => false, 'afternoon' => true],
                'friday' => ['morning' => true, 'afternoon' => true],
                'saturday' => ['morning' => true, 'afternoon' => true],
                'sunday' => ['morning' => false, 'afternoon' => false],
            ],
        ];
    }

    private function weeklyChangingPlanning(): array
    {
        return [
            'default' => [
                'monday' => ['morning' => true, 'afternoon' => true],
                'tuesday' => ['morning' => true, 'afternoon' => true],
                'wednesday' => ['morning' => true, 'afternoon' => true],
                'thursday' => ['morning' => true, 'afternoon' => true],
                'friday' => ['morning' => true, 'afternoon' => true],
                'saturday' => ['morning' => false, 'afternoon' => false],
                'sunday' => ['morning' => false, 'afternoon' => false],
            ],
            'weeks' => [
                '2026-W30' => [
                    'monday' => ['morning' => false, 'afternoon' => false],
                    'tuesday' => ['morning' => true, 'afternoon' => true],
                    'wednesday' => ['morning' => true, 'afternoon' => true],
                    'thursday' => ['morning' => true, 'afternoon' => true],
                    'friday' => ['morning' => true, 'afternoon' => true],
                    'saturday' => ['morning' => false, 'afternoon' => false],
                    'sunday' => ['morning' => false, 'afternoon' => false],
                ],
            ],
        ];
    }
}
