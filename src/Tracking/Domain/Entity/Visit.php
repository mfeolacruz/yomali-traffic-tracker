<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tracking\Domain\Entity;

use Yomali\Tracker\Tracking\Domain\ValueObject\{IpAddress, Url};

/**
 * Visit entity representing a single page visit
 */
final class Visit
{
    private ?int $id = null;

    public function __construct(
        public readonly IpAddress $ipAddress,
        public readonly Url $url,
        public readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {
    }

    /**
     * Factory method to create a new visit
     */
    public static function create(string $ipAddress, string $pageUrl): self
    {
        return new self(
            ipAddress: new IpAddress($ipAddress),
            url: new Url($pageUrl),
        );
    }

    /**
     * Reconstitute from database
     */
    public static function fromPrimitives(
        int $id,
        string $ipAddress,
        string $pageUrl,
        string $createdAt
    ): self {
        $visit = new self(
            ipAddress: new IpAddress($ipAddress),
            url: new Url($pageUrl),
            createdAt: new \DateTimeImmutable($createdAt),
        );
        $visit->id = $id;
        return $visit;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
