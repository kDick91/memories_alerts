<?php
/** @var array $_['albums'] */
/** @var string $_['alert_time'] */

// Load JavaScript and CSS for the settings panel
\OCP\Util::addScript('memories_alerts', 'settings');
\OCP\Util::addStyle('memories_alerts', 'settings');
?>

<div id="memories_alerts">
    <h2>Memories Alerts</h2>
    <div class="settings-section">
        <label>
            Daily Alert Time:
            <input type="time" id="alert_time" value="<?php echo htmlspecialchars($_['alert_time'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <button id="test_alert_button">Send Test Alert</button>
    </div>
    <h3>Your Albums</h3>
    <?php if (empty($_['albums'])): ?>
        <p>No albums found.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($_['albums'] as $album): ?>
                <li class="album-item">
                    <input type="checkbox" 
                           id="alert_<?php echo htmlspecialchars($album['album_id'], ENT_QUOTES, 'UTF-8'); ?>" 
                           class="album-checkbox"
                           data-album-id="<?php echo htmlspecialchars($album['album_id'], ENT_QUOTES, 'UTF-8'); ?>"
                           <?php echo $album['alert_enabled'] ? 'checked' : ''; ?>>
                    <label><?php echo htmlspecialchars($album['name'], ENT_QUOTES, 'UTF-8'); ?> (ID: <?php echo htmlspecialchars($album['album_id'], ENT_QUOTES, 'UTF-8'); ?>)</label>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>