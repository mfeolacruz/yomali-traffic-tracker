<?php

declare(strict_types=1);

namespace Yomali\Tracker\Application\Analytics\Query;

use Yomali\Tracker\Domain\Analytics\ValueObject\AnalyticsFilter;

final readonly class GetPageAnalyticsQuery
{
    public function __construct(
        public AnalyticsFilter $filter,
    ) {
    }
}
