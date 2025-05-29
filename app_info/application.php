<?php
namespace OCA\MemoriesAlerts;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCA\MemoriesAlerts\Service\AlertService;
use OCA\MemoriesAlerts\BackgroundJob\SendDailyAlerts;

class Application extends App implements IBootstrap {
    public const APP_ID = 'memories_alerts';

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        // Register the AlertService
        $context->registerService(AlertService::class, function ($c) {
            return new AlertService(
                $c->getServer()->getDatabaseConnection(),
                $c->getServer()->getMailer(),
                $c->getServer()->getConfig()
            );
        });

        // Register the background job
        $context->registerService(SendDailyAlerts::class, function ($c) {
            return new SendDailyAlerts(
                $c->get(AlertService::class)
            );
        });

        // Ensure the background job is added to Nextcloud's job list
        $context->registerBackgroundJob(SendDailyAlerts::class);
    }

    public function boot(IBootContext $context): void {
        // No additional boot logic needed for now
    }
}