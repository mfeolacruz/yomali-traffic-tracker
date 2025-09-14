<?php

declare(strict_types=1);

use Yomali\Tracker\Application\Tracking\Command\TrackVisitCommandHandler;
use Yomali\Tracker\Infrastructure\Persistence\MySQL\Repository\MySQLVisitRepository;
use Yomali\Tracker\Presentation\Api\Controller\TrackingController;

require_once __DIR__ . '/../../../vendor/autoload.php';

// Create controller with dependencies
$controller = new TrackingController(
    new TrackVisitCommandHandler(
        new MySQLVisitRepository()
    )
);

// Handle the request
$controller->track();