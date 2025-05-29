<?php
/** @var array $_['albums'] */
/** @var string $_['alert_time'] */

// Load JavaScript and CSS for the settings panel
\OCP\Util::addScript('memories_alerts', 'settings'); // Loads js/settings.js or js/settings.min.js if bundled
\OCP\Util::addStyle('memories_alerts', 'settings'); // Loads css/settings.css
?>

<div id="memories_alerts">
    <h2>Memories Alerts</h2>
    <label>
        Daily Alert Time:
        <input type="time" id="alert_time" value="<?php p($_['alert_time']); ?>">
    </label>
    <h3>Your Shared Albums</h3>
    <?php if (empty($_['albums'])): ?>
        <p>No shared albums found.</p>
    <?php else: ?>
        <?php foreach ($_['albums'] as $album): ?>
            <div class="album">
                <h4><?php p($album['name']); ?> (ID: <?php p($album['album_id']); ?>)</h4>
                <?php if (empty($album['shared_users'])): ?>
                    <p>No users are shared with this album.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($album['shared_users'] as $user): ?>
                            <li>
                                <input type="checkbox" 
                                       id="alert_<?php p($album['album_id']); ?>_<?php p($user['uid']); ?>" 
                                       <?php if ($user['alert_enabled']) p('checked'); ?>>
                                <label><?php p($user['uid']); ?></label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>