<?php

declare(strict_types=1);

namespace Yomali\Tracker\Application\Commands;

use Yomali\Tracker\Domain\Visit\Visit;
use Yomali\Tracker\Domain\Visit\VisitRepositoryInterface;

/**
 * Handles the TrackVisitCommand
 */
final readonly class TrackVisitCommandHandler
{
    public function __construct(
        private VisitRepositoryInterface $visitRepository
    ) {}

    /**
     * Execute the command to track a visit
     *
     * @throws \InvalidArgumentException if validation fails
     */
    public function handle(TrackVisitCommand $command): void
    {
        // Create the Visit entity using the factory method
        // This will validate IP and URL through Value Objects
        $visit = Visit::create(
            ipAddress: $command->ipAddress,
            pageUrl: $command->pageUrl
        );

        // Persist the visit
        $this->visitRepository->save($visit);
    }
}