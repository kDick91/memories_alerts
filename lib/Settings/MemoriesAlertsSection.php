<?php
namespace OCA\Memories_alerts\Settings;

use OCP\Settings\IIconSection;
use OCP\IURLGenerator;

class MemoriesAlertsSection implements IIconSection {
    private $urlGenerator;

    public function __construct(IURLGenerator $urlGenerator) {
        $this->urlGenerator = $urlGenerator;
    }

    public function getID(): string {
        return 'memories_alerts';
    }

    public function getName(): string {
        return 'Memories Alerts';
    }

    public function getPriority(): int {
        return 10;
    }

    public function getIcon(): string {
        return $this->urlGenerator->imagePath('memories_alerts', 'app.svg');
    }
}