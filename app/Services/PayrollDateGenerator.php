<?php

namespace App\Services;

use App\Exceptions\InvalidDateRangeException;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Generator;

class PayrollDateGenerator
{
    private CONST BONUS_PAYMENT_DAY = 15;

    private Carbon $startDate;
    private Carbon $endDate;

    /**
     * @param Carbon $startDate
     * @param Carbon $endDate
     * 
     * @throws InvalidDateRangeException
     * @return void
     */
    public function initialize(Carbon $startDate, Carbon $endDate): void
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        $this->validation();
    }

    private function validation()
    {
        if ($this->startDate > $this->endDate) {
            throw new InvalidDateRangeException();
        }
    }
    
    /**
     * @param Carbon $startDate
     * @param Carbon $endDate
     * 
     * @return Generator
     */
    private function getAllDaysInRange(): Generator
    {
        return CarbonPeriod::create($this->startDate, $this->endDate)->getIterator();
    }

    public function paymentDates(): Generator
    {
        /** @var Carbon $date */
        foreach($this->getAllDaysInRange() as $date){
            
            if(!$date->isLastOfMonth()){
                continue;
            }

            if(!$date->isWeekend()){
                yield [
                    'targetDate' => $date->clone(), 
                    'actualDate' => $date->clone() 
                ];
            };

            if($date->isWeekend()){
                // Would substract 6 or 7 days(for saturday or sunday), and add 5 days(Friday) to get the last 
                // day of the month before the weekend.
                yield [
                    'targetDate' => $date->clone(), 
                    'actualDate' => $date->subDays($this->dayOfWeekStartingFromMonday($date))
                                        ->addDays(Carbon::FRIDAY)->clone()
                ];
            }
        } 
    }

    public function bonusPaymentDates(): Generator
    {
        /** @var Carbon $date */
        foreach($this->getAllDaysInRange() as $date){

            if($date->day !== self::BONUS_PAYMENT_DAY){
                continue;
            }

            if(!$date->isWeekend()){
                yield [
                    'targetDate' => $date->clone(), 
                    'actualDate' => $date->clone() 
                ];
            };

            if($date->isWeekend()){
                yield [
                    'targetDate' => $date->clone(), 
                    // Day of week is 6 or 7 (sa/su), so add 1 or 0, and then add 3 to find next wednesday.
                    'actualDate' => $date->addDays((7 - $this->dayOfWeekStartingFromMonday($date)) + Carbon::WEDNESDAY)->clone()
                ]; 
            }
        } 
    }

    public function flattenedDates(): Generator
    {
        /** @var Carbon $period */
        foreach(CarbonPeriod::create($this->startDate,'1 month', $this->endDate) as $period){
            yield [
                'month' => $period->monthName,
                'salary_payment_date' => collect($this->paymentDates())
                    ->first(fn(array $date) => $date['targetDate']->month === $period->month 
                    && $date['targetDate']->year === $period->year)['actualDate'],
                'bonus_payment_date' =>  collect($this->bonusPaymentDates())
                    ->first(fn(array $date) => $date['targetDate']->month === $period->month 
                    && $date['targetDate']->year === $period->year)['actualDate']
            ];
        }
    }

    /**
     * Converts a date dayOfWeek to 'array starts at 1', where monday is 1 and sunday is 7
     * instead of the date where sunday = 0
     * 
     * @param Carbon $date
     * 
     * @return int
     */
    private function dayOfWeekStartingFromMonday(Carbon $date):int
    {
        return $date->dayOfWeek == 0 ? 7 : $date->dayOfWeek;
    }
}
