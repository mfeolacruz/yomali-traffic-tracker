<?php

declare(strict_types=1);

namespace Yomali\Tracker\Application\Tracking\Command;

use Yomali\Tracker\Domain\Tracking\Entity\Visit;
use Yomali\Tracker\Domain\Tracking\Repository\VisitRepositoryInterface;

/**
 * Handles the TrackVisitCommand
 */
readonly class TrackVisitCommandHandler
{
    public function __construct(
        private VisitRepositoryInterface $visitRepository
    ) {
    }

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
