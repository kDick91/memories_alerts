<?php
namespace OCA\Memories_alerts\Settings;

use OCP\Settings\ISettings;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\Memories_alerts\Controller\SettingsController;

class PersonalSettings implements ISettings {
    private $controller;

    public function __construct(SettingsController $controller) {
        $this->controller = $controller;
    }

    public function getForm(): TemplateResponse {
        return $this->controller->index();
    }

    public function getSection(): string {
        return 'memories_alerts';
    }

    public function getPriority(): int {
        return 10;
    }
}