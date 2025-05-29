<?php
namespace OCA\Memories_alerts\BackgroundJob; // Updated namespace

use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\IUserManager;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use OCA\Memories_alerts\Service\AlertService; // Updated namespace

class SendDailyAlerts extends TimedJob {
    private $alertService;
    private $userManager;
    private $config;
    private $logger;

    public function __construct(
        IJobList $jobList,
        AlertService $alertService,
        IUserManager $userManager,
        IConfig $config,
        LoggerInterface $logger
    ) {
        parent::__construct($jobList);
        $this->alertService = $alertService;
        $this->userManager = $userManager;
        $this->config = $config;
        $this->logger = $logger;
        $this->setInterval(60 * 60); // Run every hour
    }

    protected function run($argument) {
        $currentTime = date('H:i');
        $users = $this->userManager->search('');
        $usersProcessed = false;

        foreach ($users as $user) {
            $userId = $user->getUID();
            $userTime = $this->config->getUserValue($userId, 'memories_alerts', 'alert_time', '23:59');

            if ($currentTime === $userTime) {
                $this->alertService->sendDailyAlerts($userId);
                $usersProcessed = true;
            }
        }

        if (!$usersProcessed) {
            $this->logger->debug("No users with alert time $currentTime", ['app' => 'memories_alerts']);
        }
    }
}