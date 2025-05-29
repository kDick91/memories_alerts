<?php
/** @var array $_['albums'] */
/** @var string $_['alert_time'] */

/**
 * Escapes a string for safe output in HTML.
 * @param string $value The value to escape
 * @return void
 */
function p($value) {}

// Load JavaScript and CSS for the settings panel
\OCP\Util::addScript('memories_alerts', 'settings');
\OCP\Util::addStyle('memories_alerts', 'settings');
?>

<div id="memories_alerts">
    <h2>Memories Alerts</h2>
    <div class="settings-section">
        <label>
            Daily Alert Time:
            <input type="time" id="alert_time" value="<?php p($_['alert_time']); ?>">
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
                           id="alert_<?php p($album['album_id']); ?>" 
                           class="album-checkbox"
                           data-album-id="<?php p($album['album_id']); ?>"
                           <?php if ($album['alert_enabled']) p('checked'); ?>>
                    <label><?php p($album['name']); ?> (ID: <?php p($album['album_id']); ?>)</label>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>