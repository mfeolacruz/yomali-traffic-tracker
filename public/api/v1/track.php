<?php

declare(strict_types=1);

use Yomali\Tracker\Tracking\Application\Command\TrackVisitCommandHandler;
use Yomali\Tracker\Tracking\Infrastructure\Http\Controller\TrackingController;
use Yomali\Tracker\Tracking\Infrastructure\Persistence\Repository\MySQLVisitRepository;

require_once __DIR__ . '/../../../vendor/autoload.php';

// Create controller with dependencies
$controller = new TrackingController(
    new TrackVisitCommandHandler(
        new MySQLVisitRepository()
    )
);

// Handle the request
$controller->track();