<?php

declare(strict_types=1);

namespace Yomali\Tracker\Domain\Visit\ValueObjects;

/**
 * IP Address value object
 */
final readonly class IpAddress implements \Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException("Invalid IP address: {$value}");
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}