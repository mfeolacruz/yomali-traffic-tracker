<?php

declare(strict_types=1);

namespace Yomali\Tracker\Application\Commands;

/**
 * Command to track a new visit
 */
final readonly class TrackVisitCommand
{
    public function __construct(
        public string $ipAddress,
        public string $pageUrl,
    ) {
    }
}
