<?php
namespace OCA\MemoriesAlerts;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\BackgroundJob\IJobList;
use Psr\Log\LoggerInterface;
use OCA\MemoriesAlerts\Service\AlertService;
use OCA\MemoriesAlerts\BackgroundJob\SendDailyAlerts;
use OCA\MemoriesAlerts\Controller\SettingsController;

class Application extends App implements IBootstrap {
    public const APP_ID = 'memories_alerts';

    private $logger;

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        // Register the AlertService
        $context->registerService(AlertService::class, function ($c) {
            return new AlertService(
                $c->getServer()->getDatabaseConnection(),
                $c->getServer()->getMailer(),
                $c->getServer()->getConfig(),
                $c->getServer()->get(LoggerInterface::class)
            );
        });

        // Register the background job
        $context->registerService(SendDailyAlerts::class, function ($c) {
            return new SendDailyAlerts(
                $c->getServer()->get(IJobList::class),
                $c->get(AlertService::class),
                $c->getServer()->getUserManager(),
                $c->getServer()->getConfig(),
                $c->getServer()->get(LoggerInterface::class)
            );
        });

        // Register the SettingsController
        $context->registerService(SettingsController::class, function ($c) {
            return new SettingsController(
                self::APP_ID,
                $c->getServer()->getRequest(),
                $c->getServer()->getDatabaseConnection(),
                $c->getServer()->getConfig(),
                $c->getServer()->getUserSession(),
                $c->getServer()->getMailer(),
                $c->getServer()->get(LoggerInterface::class),
                $c->getServer()->getNavigationManager()
            );
        });
    }

    public function boot(IBootContext $context): void {
        try {
            $jobList = $context->getServerContainer()->get(IJobList::class);
            $jobList->add(SendDailyAlerts::class);

            // Initialize logger and log during boot
            $logger = $context->getServerContainer()->get(LoggerInterface::class);
            $logger->info("Memories Alerts app initialized and booted", ['app' => self::APP_ID]);
            $this->logger = $logger; // Assign to property after successful initialization
        } catch (\Exception $e) {
            // Log the error to a file manually since logger might not be available
            file_put_contents('/tmp/memories_alerts_boot_error.log', "Boot error: " . $e->getMessage() . "\n", FILE_APPEND);
            throw $e; // Re-throw to ensure Nextcloud logs the error
        }
    }
}