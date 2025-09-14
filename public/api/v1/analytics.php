<?php

declare(strict_types=1);

use Yomali\Tracker\Application\Analytics\Query\GetPageAnalyticsHandler;
use Yomali\Tracker\Infrastructure\Persistence\MySQL\Repository\MySQLAnalyticsRepository;
use Yomali\Tracker\Presentation\Api\Controller\AnalyticsController;

require_once __DIR__ . '/../../../vendor/autoload.php';

// Create controller with dependencies
$controller = new AnalyticsController(
    new GetPageAnalyticsHandler(
        new MySQLAnalyticsRepository()
    )
);

// Handle the request
$controller->getPageAnalytics();
