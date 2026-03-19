<?php

declare(strict_types = 1);

namespace App\EventListener;

use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

class SQLiteTimestampListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs): void
    {
        $schema = $eventArgs->getSchema();
        $platform = $eventArgs->getEntityManager()->getConnection()->getDatabasePlatform();

        if (!str_contains($platform->getName(), 'sqlite')) {
            return;
        }

        foreach ($schema->getTables() as $table) {
            foreach ($table->getColumns() as $column) {
                $this->removeOnUpdateFromColumn($column);
            }
        }
    }

    private function removeOnUpdateFromColumn(Column $column): void
    {
        $platformOptions = $column->getPlatformOptions();

        if (isset($platformOptions['on update'])) {
            unset($platformOptions['on update']);
            $column->setPlatformOptions($platformOptions);
        }

        $default = $column->getDefault();
        if ($default !== null && str_contains(strtoupper((string) $default), 'ON UPDATE')) {
            $parts = explode(' ON UPDATE', strtoupper((string) $default));
            $column->setDefault(trim($parts[0]));
        }

        $columnDef = $column->getColumnDefinition();
        if ($columnDef !== null && str_contains(strtoupper($columnDef), 'ON UPDATE')) {
            $parts = explode(' ON UPDATE', strtoupper($columnDef));
            $column->setColumnDefinition(trim($parts[0]));
        }
    }
}
