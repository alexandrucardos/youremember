<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260202120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop hash column from user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN hash');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD hash VARCHAR(50) DEFAULT NULL');
    }
}
