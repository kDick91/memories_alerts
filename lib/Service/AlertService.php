<?php
namespace OCA\MemoriesAlerts\Service;

use OCP\IDBConnection;
use OCP\Mail\IMailer;
use OCP\IConfig;

class AlertService {
    private $db;
    private $mailer;
    private $config;

    public function __construct(IDBConnection $db, IMailer $mailer, IConfig $config) {
        $this->db = $db;
        $this->mailer = $mailer;
        $this->config = $config;
    }

    public function sendDailyAlerts() {
        // Get all users
        $userQuery = $this->db->prepare("SELECT uid FROM oc_users");
        $userQuery->execute();
        $users = $userQuery->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($users as $userId) {
            // Get albums owned by user
            $albumQuery = $this->db->prepare("SELECT album_id, name FROM oc_memories_albums WHERE user = ?");
            $albumQuery->execute([$userId]);
            $albums = $albumQuery->fetchAll();

            foreach ($albums as $album) {
                // Check for new files
                $fileQuery = $this->db->prepare("
                    SELECT f.name AS file_name, f.uid_owner AS owner
                    FROM oc_memories_album_files af
                    JOIN oc_files f ON af.fileid = f.fileid
                    WHERE af.album_id = ? AND f.mtime > UNIX_TIMESTAMP(NOW() - INTERVAL 1 DAY)
                ");
                $fileQuery->execute([$album['album_id']]);
                $newFiles = $fileQuery->fetchAll();

                if (empty($newFiles)) {
                    continue;
                }

                // Get users with alerts enabled
                $shareQuery = $this->db->prepare("
                    SELECT share_with
                    FROM oc_share
                    WHERE item_type = 'memories/album' AND item_source = ?
                ");
                $shareQuery->execute([$album['album_id']]);
                $sharedUsers = $shareQuery->fetchAll(\PDO::FETCH_COLUMN);

                $recipients = [];
                foreach ($sharedUsers as $sharedUser) {
                    $enabled = $this->config->getUserValue($userId, 'memories_alerts', "album_{$album['album_id']}_{$sharedUser}", '0');
                    if ($enabled === '1') {
                        $emailQuery = $this->db->prepare("
                            SELECT configvalue
                            FROM oc_preferences
                            WHERE userid = ? AND appid = 'settings' AND configkey = 'email'
                        ");
                        $emailQuery->execute([$sharedUser]);
                        $email = $emailQuery->fetchColumn();
                        if ($email) {
                            $recipients[$sharedUser] = $email;
                        }
                    }
                }

                if (empty($recipients)) {
                    continue;
                }

                // Prepare email
                $users = array_unique(array_column($newFiles, 'owner'));
                $userList = implode(', ', $users);
                $body = "New files added to album '{$album['name']}' by $userList.";

                $message = $this->mailer->createMessage();
                $message->setSubject('New Files Added to Memories Album');
                $message->setFrom([\OC::$server->getConfig()->getSystemValue('fromaddress', 'no-reply@yourdomain.com') => 'Nextcloud']);
                $message->setTo($recipients);
                $message->setPlainBody($body);

                // Send email
                $this->mailer->send($message);
                \OC::$server->getLogger()->info("Sent Memories alert for album {$album['album_id']} to " . implode(', ', $recipients), ['app' => 'memories_alerts']);
            }
        }
    }
}