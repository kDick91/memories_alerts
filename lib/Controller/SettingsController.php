<?php
namespace OCA\MemoriesAlerts\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Mail\IMailer;

class SettingsController extends Controller {
    private $db;
    private $config;
    private $userSession;
    private $mailer;

    public function __construct($appName, IRequest $request, IDBConnection $db, IConfig $config, IUserSession $userSession, IMailer $mailer) {
        parent::__construct($appName, $request);
        $this->db = $db;
        $this->config = $config;
        $this->userSession = $userSession;
        $this->mailer = $mailer;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        $userId = $this->userSession->getUser()->getUID();

        // Get albums owned by the user
        /** @var \Doctrine\DBAL\Statement $ownedQuery */
        $ownedQuery = $this->db->prepare("SELECT album_id, name FROM oc_memories_albums WHERE user = ?");
        $ownedQuery->execute([$userId]);
        $ownedAlbums = $ownedQuery->fetchAllAssociative();

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

        // Combine and deduplicate albums
        $albums = [];
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
     */
    public function saveTime() {
        $time = $this->request->getParam('time');
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            $this->config->setUserValue($this->userSession->getUser()->getUID(), 'memories_alerts', 'alert_time', $time);
            return new \OCP\AppFramework\Http\JSONResponse(['success' => true]);
        }
        return new \OCP\AppFramework\Http\JSONResponse(['error' => 'Invalid time'], 400);
    }

    /**
     * @NoAdminRequired
     */
    public function saveAlert() {
        $albumId = $this->request->getParam('albumId');
        $enabled = $this->request->getParam('enabled') ? '1' : '0';

        if (!is_numeric($albumId)) {
            return new \OCP\AppFramework\Http\JSONResponse(['error' => 'Invalid album ID'], 400);
        }

        $this->config->setUserValue($this->userSession->getUser()->getUID(), 'memories_alerts', "album_{$albumId}_enabled", $enabled);
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
            return new \OCP\AppFramework\Http\JSONResponse(['error' => 'No email address configured'], 400);
        }

        // Send test email
        try {
            $message = $this->mailer->createMessage();
            $message->setSubject('Test Alert from Memories Alerts');
            $message->setFrom([\OC::$server->getConfig()->getSystemValue('fromaddress', 'no-reply@yourdomain.com') => 'Nextcloud']);
            $message->setTo([$emailResult]);
            $message->setPlainBody("This is a test alert from the Memories Alerts app.");
            $this->mailer->send($message);
            return new \OCP\AppFramework\Http\JSONResponse(['success' => true, 'message' => 'Test alert sent']);
        } catch (\Exception $e) {
            return new \OCP\AppFramework\Http\JSONResponse(['error' => 'Failed to send test alert: ' . $e->getMessage()], 500);
        }
    }
}