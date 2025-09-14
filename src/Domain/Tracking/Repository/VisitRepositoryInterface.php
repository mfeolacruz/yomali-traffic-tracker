<?php

declare(strict_types=1);

namespace Yomali\Tracker\Domain\Tracking\Repository;

use Yomali\Tracker\Domain\Tracking\Aggregate\Visit;

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
