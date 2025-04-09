<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Domain;

use DateTimeInterface;
use DateTime;
use Google\Analytics\Data\V1beta\DateRange;
use Freema\GA4AnalyticsDataBundle\Exception\InvalidPeriodException;

class Period
{
    public DateTimeInterface $startDate;
    public DateTimeInterface $endDate;

    public static function create(DateTimeInterface $startDate, DateTimeInterface $endDate): self
    {
        return new static($startDate, $endDate);
    }

    public static function days(int $numberOfDays): static
    {
        $endDate = new DateTime();
        $startDate = (new DateTime())->modify(sprintf('-%d days', $numberOfDays))->setTime(0, 0);

        return new static($startDate, $endDate);
    }

    public static function months(int $numberOfMonths): static
    {
        $endDate = new DateTime();
        $startDate = (new DateTime())->modify(sprintf('-%d months', $numberOfMonths))->setTime(0, 0);

        return new static($startDate, $endDate);
    }

    public static function years(int $numberOfYears): static
    {
        $endDate = new DateTime();
        $startDate = (new DateTime())->modify(sprintf('-%d years', $numberOfYears))->setTime(0, 0);

        return new static($startDate, $endDate);
    }

    public function __construct(DateTimeInterface $startDate, DateTimeInterface $endDate)
    {
        if ($startDate > $endDate) {
            throw new InvalidPeriodException('Start date cannot be after end date');
        }

        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function toDateRange(): DateRange
    {
        return (new DateRange())
            ->setStartDate($this->startDate->format('Y-m-d'))
            ->setEndDate($this->endDate->format('Y-m-d'));
    }
    
    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }
    
    public function getEndDate(): DateTimeInterface
    {
        return $this->endDate;
    }
}