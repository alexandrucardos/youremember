<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309095236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD event_start_date DATETIME NOT NULL after status, ADD needs_manual_processing TINYINT(1) NOT NULL after event_start_date');
        $this->addSql('DROP INDEX UNIQ_6A2CA10C81257D5D ON media');
        $this->addSql('ALTER TABLE media DROP entity_id');
        $this->addSql('ALTER TABLE user CHANGE created_at created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CHANGE modified_at modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media ADD entity_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A2CA10C81257D5D ON media (entity_id)');
        $this->addSql('ALTER TABLE event DROP event_start_date, DROP needs_manual_processing');
        $this->addSql('ALTER TABLE user CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE modified_at modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
    }
}
