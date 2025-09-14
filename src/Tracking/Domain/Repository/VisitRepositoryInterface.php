<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tracking\Domain\Repository;

use Yomali\Tracker\Tracking\Domain\Entity\Visit;

/**
 * Repository interface for Visit persistence
 */
interface VisitRepositoryInterface
{
    /**
     * Save a visit to the repository
     */
    public function save(Visit $visit): void;
}
