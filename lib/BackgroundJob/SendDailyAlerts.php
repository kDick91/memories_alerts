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
        $currentTime = date('H:i');
        $userTime = \OC::$server->getConfig()->getUserValue(\OC::$server->getUserSession()->getUser()->getUID(), 'memories_alerts', 'alert_time', '23:59');

        if ($currentTime === $userTime) {
            $this->alertService->sendDailyAlerts();
        }
    }
}