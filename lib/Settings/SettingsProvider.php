<?php
namespace OCA\MemoriesAlerts\Settings;

use OCP\Settings\IIconSection; // Updated to IIconSection
use OCP\IURLGenerator;
use OCP\IL10N;
use OCP\AppFramework\Http\RedirectResponse;

class SettingsProvider implements IIconSection {
    private $urlGenerator;
    private $l10n;

    public function __construct(IURLGenerator $urlGenerator, IL10N $l10n) {
        $this->urlGenerator = $urlGenerator;
        $this->l10n = $l10n;
    }

    public function getId(): string {
        return 'memories_alerts';
    }

    public function getName(): string {
        return $this->l10n->t('Memories Alerts');
    }

    public function getPriority(): int {
        return 10;
    }

    public function getIcon(): string {
        // Use a default Nextcloud icon or a custom one if available
        return $this->urlGenerator->imagePath('memories_alerts', 'app.svg');
    }

    // Helper method to redirect to the actual settings page
    public function getForm(): RedirectResponse {
        $url = $this->urlGenerator->linkToRoute('memories_alerts.settings.index');
        return new RedirectResponse($url);
    }
}