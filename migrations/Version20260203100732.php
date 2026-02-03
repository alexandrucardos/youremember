<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203100732 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD media_count INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3BAE0AA78D9F6D38 ON event (order_id)');
        $this->addSql('ALTER TABLE user CHANGE created_at created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CHANGE modified_at modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_3BAE0AA78D9F6D38 ON event');
        $this->addSql('ALTER TABLE event DROP media_count');
        $this->addSql('ALTER TABLE user CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE modified_at modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
    }
}
