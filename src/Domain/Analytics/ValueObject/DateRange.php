<?php

declare(strict_types=1);

namespace Yomali\Tracker\Domain\Analytics\ValueObject;

final readonly class DateRange
{
    public function __construct(
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
    ) {
        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Start date must be before or equal to end date');
        }
    }

    public static function fromStrings(string $startDate, string $endDate): self
    {
        try {
            $start = new \DateTimeImmutable($startDate);
            $end = new \DateTimeImmutable($endDate);

            return new self($start, $end);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid date format: ' . $e->getMessage());
        }
    }

    public static function lastDays(int $days): self
    {
        if ($days < 1) {
            throw new \InvalidArgumentException('Days must be at least 1');
        }

        $end = new \DateTimeImmutable('today 23:59:59');
        $start = $end->modify('-' . ($days - 1) . ' days')->setTime(0, 0, 0);

        return new self($start, $end);
    }

    public static function currentMonth(): self
    {
        $start = new \DateTimeImmutable('first day of this month 00:00:00');
        $end = new \DateTimeImmutable('last day of this month 23:59:59');

        return new self($start, $end);
    }

    public function contains(\DateTimeImmutable $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    public function getDurationInDays(): int
    {
        return (int) $this->startDate->diff($this->endDate)->days + 1;
    }
}
