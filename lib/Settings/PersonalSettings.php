<?php
namespace OCA\MemoriesAlerts\Settings;

use OCP\Settings\IIconSection;
use OCP\IURLGenerator;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\MemoriesAlerts\Controller\SettingsController;

class PersonalSettings implements IIconSection {
    private $controller;
    private $urlGenerator;

    public function __construct(SettingsController $controller, IURLGenerator $urlGenerator) {
        $this->controller = $controller;
        $this->urlGenerator = $urlGenerator;
    }

    public function getId(): string {
        return 'memories_alerts';
    }

    public function getName(): string {
        return 'Memories Alerts';
    }

    public function getPriority(): int {
        return 10;
    }

    public function getIcon(): string {
        // Use the app's icon, which we know exists
        return $this->urlGenerator->imagePath('memories_alerts', 'app.svg');
    }

    // Helper method to render the form
    public function getForm(): TemplateResponse {
        return $this->controller->index();
    }
}