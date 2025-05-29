<?php
namespace OCA\MemoriesAlerts\BackgroundJob;

use OCP\BackgroundJob\TimedJob;
use OCA\MemoriesAlerts\Service\AlertService;

class SendDailyAlerts extends TimedJob {
    private $alertService;

    public function __construct(AlertService $alertService) {
        parent::__construct();
        $this->alertService = $alertService;
        $this->setInterval(60); // Run every minute to check time
    }

    protected function run($argument) {
        // Get all users
        $userManager = \OC::$server->getUserManager();
        $users = $userManager->search('');
        foreach ($users as $user) {
            $userId = $user->getUID();
            $currentTime = date('H:i');
            $config = \OC::$server->getConfig();
            $userTime = $config->getUserValue($userId, 'memories_alerts', 'alert_time', '23:59');

            if ($currentTime === $userTime) {
                $this->alertService->sendDailyAlerts();
                break; // Run once per minute to avoid duplicate emails
            }
        }
    }
}