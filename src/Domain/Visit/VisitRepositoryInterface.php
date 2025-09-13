<?php

declare(strict_types=1);

namespace Yomali\Tracker\Domain\Visit;

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
