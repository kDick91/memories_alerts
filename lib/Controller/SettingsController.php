<?php
namespace OCA\Memories_alerts\Controller; // Updated namespace

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Mail\IMailer;
use OCP\INavigationManager;
use Psr\Log\LoggerInterface;

class SettingsController extends Controller {
    private $db;
    private $config;
    private $userSession;
    private $mailer;
    private $logger;
    private $navigationManager;

    public function __construct(
        $appName,
        IRequest $request,
        IDBConnection $db,
        IConfig $config,
        IUserSession $userSession,
        IMailer $mailer,
        LoggerInterface $logger,
        INavigationManager $navigationManager
    ) {
        parent::__construct($appName, $request);
        $this->db = $db;
        $this->config = $config;
        $this->userSession = $userSession;
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->navigationManager = $navigationManager;
        $this->logger->info("SettingsController constructed for app: $appName", ['app' => 'memories_alerts']);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        $userId = $this->userSession->getUser() ? $this->userSession->getUser()->getUID() : 'unknown';
        $this->logger->info("Memories Alerts settings page accessed by user: $userId", ['app' => 'memories_alerts']);

        $albums = [];

        try {
            // Get albums owned by the user
            /** @var \Doctrine\DBAL\Statement $ownedQuery */
            $ownedQuery = $this->db->prepare("SELECT album_id, name FROM oc_memories_albums WHERE user = ?");
            $ownedQuery->execute([$userId]);
            $ownedAlbums = $ownedQuery->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch owned albums for user $userId: " . $e->getMessage(), ['app' => 'memories_alerts']);
            $ownedAlbums = [];
        }

        try {
            // Get albums shared with the user
            /** @var \Doctrine\DBAL\Statement $sharedQuery */
            $sharedQuery = $this->db->prepare("
                SELECT a.album_id, a.name
                FROM oc_share s
                JOIN oc_memories_albums a ON s.item_source = a.album_id
                WHERE s.item_type = 'memories/album' AND s.share_with = ?
            ");
            $sharedQuery->execute([$userId]);
            $sharedAlbums = $sharedQuery->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch shared albums for user $userId: " . $e->getMessage(), ['app' => 'memories_alerts']);
            $sharedAlbums = [];
        }

        // Combine and deduplicate albums
        $albumIds = [];
        foreach (array_merge($ownedAlbums, $sharedAlbums) as $album) {
            if (!in_array($album['album_id'], $albumIds)) {
                $albumIds[] = $album['album_id'];
                $enabled = $this->config->getUserValue($userId, 'memories_alerts', "album_{$album['album_id']}_enabled", '0');
                $album['alert_enabled'] = $enabled === '1';
                $albums[] = $album;
            }
        }

        // Get alert time
        $alertTime = $this->config->getUserValue($userId, 'memories_alerts', 'alert_time', '23:59');

        return new \OCP\AppFramework\Http\TemplateResponse('memories_alerts', 'settings/personal', [
            'albums' => $albums,
            'alert_time' => $alertTime
        ]);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function debugNavigation() {
        $navEntries = $this->navigationManager->getAll();
        $this->logger->info("Navigation entries: " . json_encode($navEntries), ['app' => 'memories_alerts']);
        return new \OCP\AppFramework\Http\JSONResponse($navEntries);
    }

    /**
     * @NoAdminRequired
     */
    public function saveTime() {
        $userId = $this->userSession->getUser()->getUID();
        $time = $this->request->getParam('time');
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            $this->config->setUserValue($userId, 'memories_alerts', 'alert_time', $time);
            $this->logger->info("Alert time saved for user $userId: $time", ['app' => 'memories_alerts']);
            return new \OCP\AppFramework\Http\JSONResponse(['success' => true]);
        }
        $this->logger->warning("Invalid alert time format for user $userId: $time", ['app' => 'memories_alerts']);
        return new \OCP\AppFramework\Http\JSONResponse(['error' => 'Invalid time'], 400);
    }

    /**
     * @NoAdminRequired
     */
    public function saveAlert() {
        $userId = $this->userSession->getUser()->getUID();
        $albumId = $this->request->getParam('albumId');
        $enabled = $this->request->getParam('enabled') ? '1' : '0';

        if (!is_numeric($albumId)) {
            $this->logger->warning("Invalid album ID for user $userId: $albumId", ['app' => 'memories_alerts']);
            return new \OCP\AppFramework\Http\JSONResponse(['error' => 'Invalid album ID'], 400);
        }

        $this->config->setUserValue($userId, 'memories_alerts', "album_{$albumId}_enabled", $enabled);
        $this->logger->info("Alert setting saved for user $userId, album $albumId: enabled=$enabled", ['app' => 'memories_alerts']);
        return new \OCP\AppFramework\Http\JSONResponse(['success' => true]);
    }

    /**
     * @NoAdminRequired
     */
    public function sendTestAlert() {
        $userId = $this->userSession->getUser()->getUID();

        // Get user's email
        /** @var \Doctrine\DBAL\Statement $emailQuery */
        $emailQuery = $this->db->prepare("
            SELECT configvalue
            FROM oc_preferences
            WHERE userid = ? AND appid = 'settings' AND configkey = 'email'
        ");
        $emailQuery->execute([$userId]);
        $emailResult = $emailQuery->fetchOne();

        if (!$emailResult) {
            $this->logger->warning("No email address configured for user $userId", ['app' => 'memories_alerts']);
            return new \OCP\AppFramework\Http\JSONResponse(['error' => 'No email address configured'], 400);
        }

        // Send test email
        try {
            $message = $this->mailer->createMessage();
            $message->setSubject('Test Alert from Memories Alerts');
            $fromAddress = $this->config->getSystemValue('fromaddress', 'no-reply@yourdomain.com');
            $message->setFrom([$fromAddress => 'Nextcloud']);
            $message->setTo([$emailResult]);
            $message->setPlainBody("This is a test alert from the Memories Alerts app.");
            $this->mailer->send($message);
            $this->logger->info("Test alert sent to $emailResult for user $userId", ['app' => 'memories_alerts']);
            return new \OCP\AppFramework\Http\JSONResponse(['success' => true, 'message' => 'Test alert sent']);
        } catch (\Exception $e) {
            $this->logger->error("Failed to send test alert for user $userId: " . $e->getMessage(), ['app' => 'memories_alerts']);
            return new \OCP\AppFramework\Http\JSONResponse(['error' => 'Failed to send test alert: ' . $e->getMessage()], 500);
        }
    }
}