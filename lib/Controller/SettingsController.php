<?php
namespace OCA\MemoriesAlerts\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\IUserSession;

class SettingsController extends Controller {
    private $db;
    private $config;
    private $userSession;

    public function __construct($appName, IRequest $request, IDBConnection $db, IConfig $config, IUserSession $userSession) {
        parent::__construct($appName, $request);
        $this->db = $db;
        $this->config = $config;
        $this->userSession = $userSession;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        $userId = $this->userSession->getUser()->getUID();

        // Get albums owned by user
        $query = $this->db->prepare("SELECT album_id, name FROM oc_memories_albums WHERE user = ?");
        $query->execute([$userId]);
        $albums = $query->fetchAll();

        // Get shared users and alert settings
        foreach ($albums as &$album) {
            $shareQuery = $this->db->prepare("
                SELECT share_with
                FROM oc_share
                WHERE item_type = 'memories/album' AND item_source = ?
            ");
            $shareQuery->execute([$album['album_id']]);
            $sharedUsers = $shareQuery->fetchAll(\PDO::FETCH_COLUMN);

            $album['shared_users'] = [];
            foreach ($sharedUsers as $uid) {
                $enabled = $this->config->getUserValue($userId, 'memories_alerts', "album_{$album['album_id']}_$uid", '0');
                $album['shared_users'][] = ['uid' => $uid, 'alert_enabled' => $enabled === '1'];
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
        $userId = $this->request->getParam('userId');
        $enabled = $this->request->getParam('enabled') ? '1' : '0';

        $this->config->setUserValue($this->userSession->getUser()->getUID(), 'memories_alerts', "album_{$albumId}_{$userId}", $enabled);
        return new \OCP\AppFramework\Http\JSONResponse(['success' => true]);
    }
}