<?php
namespace OCA\MemoriesAlerts;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\BackgroundJob\IJobList;
use OCP\Settings\IManager as ISettingsManager;
use Psr\Log\LoggerInterface;
use OCA\MemoriesAlerts\Service\AlertService;
use OCA\MemoriesAlerts\BackgroundJob\SendDailyAlerts;
use OCA\MemoriesAlerts\Controller\SettingsController;
use OCA\MemoriesAlerts\Settings\SettingsProvider;

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
                $c->getServer()->get(LoggerInterface::class)
            );
        });

        // Register the SettingsProvider
        $context->registerService(SettingsProvider::class, function ($c) {
            return new SettingsProvider(
                $c->getServer()->getURLGenerator(),
                $c->getServer()->getL10N(self::APP_ID)
            );
        });
    }

    public function boot(IBootContext $context): void {
        $jobList = $context->getServerContainer()->get(IJobList::class);
        $jobList->add(SendDailyAlerts::class);

        // Register the settings section
        $settingsManager = $context->getServerContainer()->get(ISettingsManager::class);
        $settingsManager->registerSection('personal', SettingsProvider::class);
    }
}