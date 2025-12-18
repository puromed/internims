<?php

namespace App\Services;

use Carbon\Carbon;

class SemesterService
{
    /**
     * Semester quarter definitions: [start_month, end_month] => quarter
     * March-August = Q1, October-February = Q2
     */
    protected static array $quarters = [
        [3, 8, 1],   // March (3) to August (8) = Quarter 1
        [10, 2, 2],  // October (10) to February (2) = Quarter 2
    ];

    public static function getSemesterCode(Carbon $date): string
    {
        $month = $date->month;
        $year = $date->year;

        // Quarter 1: March-August
        if ($month >= 3 && $month <= 8) {
            return $year . '1';
        }

        // Quarter 2: October-February (spans year boundary)
        // Oct-Dec = current year's Q2, Jan-Feb = previous year's Q2
        if ($month >= 10) {
            return $year . '2';
        }

        if ($month <= 2) {
            return ($year - 1) . '2';  // Jan-Feb belongs to previous year's Q2
        }

        // September is a gap month - default to upcoming Q2
        return $year . '2';
    }

    public static function getCurrentSemesterCode(): string
    {
        return self::getSemesterCode(Carbon::now());
    }
}