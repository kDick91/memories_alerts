<?php
// No namespace declaration

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\BackgroundJob\IJobList;
use OCP\Settings\IManager as ISettingsManager;
use Psr\Log\LoggerInterface;
use OCA\Memories_alerts\Service\AlertService;
use OCA\Memories_alerts\BackgroundJob\SendDailyAlerts;
use OCA\Memories_alerts\Controller\SettingsController;
use OCA\Memories_alerts\Settings\MemoriesAlertsSection; // New section class
use OCA\Memories_alerts\Settings\MemoriesAlertsSettings; // New settings class

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

        // Register the MemoriesAlertsSection
        $context->registerService(MemoriesAlertsSection::class, function ($c) {
            return new MemoriesAlertsSection(
                $c->getServer()->getURLGenerator()
            );
        });

        // Register the MemoriesAlertsSettings
        $context->registerService(MemoriesAlertsSettings::class, function ($c) {
            return new MemoriesAlertsSettings(
                $c->get(SettingsController::class)
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
            $this->logger = $logger;

            // Register the personal settings section and form
            $settingsManager = $context->getServerContainer()->get(ISettingsManager::class);
            $settingsManager->registerSection('personal', MemoriesAlertsSection::class);
            $settingsManager->registerSetting('personal', MemoriesAlertsSettings::class);
        } catch (\Exception $e) {
            file_put_contents('/tmp/memories_alerts_boot_error.log', "Boot error: " . $e->getMessage() . "\n", FILE_APPEND);
            throw $e;
        }
    }
}