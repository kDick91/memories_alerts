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
        /** @var \Doctrine\DBAL\Statement $userQuery */
        $userQuery = $this->db->prepare("SELECT uid FROM oc_users");
        $userQuery->execute();
        $users = $userQuery->fetchFirstColumn();

        foreach ($users as $userId) {
            // Get albums the user has access to
            /** @var \Doctrine\DBAL\Statement $ownedQuery */
            $ownedQuery = $this->db->prepare("SELECT album_id, name FROM oc_memories_albums WHERE user = ?");
            $ownedQuery->execute([$userId]);
            $ownedAlbums = $ownedQuery->fetchAllAssociative();

            /** @var \Doctrine\DBAL\Statement $sharedQuery */
            $sharedQuery = $this->db->prepare("
                SELECT a.album_id, a.name
                FROM oc_share s
                JOIN oc_memories_albums a ON s.item_source = a.album_id
                WHERE s.item_type = 'memories/album' AND s.share_with = ?
            ");
            $sharedQuery->execute([$userId]);
            $sharedAlbums = $sharedQuery->fetchAllAssociative();

            $albums = [];
            $albumIds = [];
            foreach (array_merge($ownedAlbums, $sharedAlbums) as $album) {
                if (!in_array($album['album_id'], $albumIds)) {
                    $albumIds[] = $album['album_id'];
                    $enabled = $this->config->getUserValue($userId, 'memories_alerts', "album_{$album['album_id']}_enabled", '0');
                    if ($enabled === '1') {
                        $albums[] = $album;
                    }
                }
            }

            // Process each album with alerts enabled
            foreach ($albums as $album) {
                // Check for new files
                /** @var \Doctrine\DBAL\Statement $fileQuery */
                $fileQuery = $this->db->prepare("
                    SELECT f.name AS file_name, f.uid_owner AS owner
                    FROM oc_memories_album_files af
                    JOIN oc_files f ON af.fileid = f.fileid
                    WHERE af.album_id = ? AND f.mtime > UNIX_TIMESTAMP(NOW() - INTERVAL 1 DAY)
                ");
                $fileQuery->execute([$album['album_id']]);
                $newFiles = $fileQuery->fetchAllAssociative();

                if (empty($newFiles)) {
                    continue;
                }

                // Get user's email
                /** @var \Doctrine\DBAL\Statement $emailQuery */
                $emailQuery = $this->db->prepare("
                    SELECT configvalue
                    FROM oc_preferences
                    WHERE userid = ? AND appid = 'settings' AND configkey = 'email'
                ");
                $emailQuery->execute([$userId]);
                $email = $emailQuery->fetchOne();

                if (!$email) {
                    continue;
                }

                // Prepare email
                $users = array_unique(array_column($newFiles, 'owner'));
                $userList = implode(', ', $users);
                $body = "New files added to album '{$album['name']}' by $userList.";

                $message = $this->mailer->createMessage();
                $message->setSubject('New Files Added to Memories Album');
                $message->setFrom([\OC::$server->getConfig()->getSystemValue('fromaddress', 'no-reply@yourdomain.com') => 'Nextcloud']);
                $message->setTo([$email]);
                $message->setPlainBody($body);

                // Send email
                $this->mailer->send($message);
                \OC::$server->getLogger()->info("Sent Memories alert for album {$album['album_id']} to $email", ['app' => 'memories_alerts']);
            }
        }
    }
}