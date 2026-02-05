<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260205114838 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE media (id INT AUTO_INCREMENT NOT NULL, file_path VARCHAR(500) NOT NULL, thumbnail_path VARCHAR(500) NOT NULL, uploader_hash VARCHAR(50) NOT NULL, file_type VARCHAR(20) NOT NULL, file_size INT NOT NULL, original_filename VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, event_id INT NOT NULL, INDEX IDX_6A2CA10C71F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10C71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE user CHANGE created_at created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CHANGE modified_at modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10C71F7E88B');
        $this->addSql('DROP TABLE media');
        $this->addSql('ALTER TABLE user CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE modified_at modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
    }
}
