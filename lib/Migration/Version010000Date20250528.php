<?php
namespace OCA\MemoriesAlerts\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010000Date20250528 extends SimpleMigrationStep {
    public function name(): string {
        return 'Initial migration';
    }

    public function description(): string {
        return 'Set up Memories Alerts app';
    }

    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?\OCP\DB\ISchemaWrapper {
        return null; // No custom tables needed
    }
}