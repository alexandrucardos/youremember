<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260220170446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE image_archive (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, event_id INT NOT NULL, INDEX IDX_40EC0A4071F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE image_archive ADD CONSTRAINT FK_40EC0A4071F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE user CHANGE created_at created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CHANGE modified_at modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image_archive DROP FOREIGN KEY FK_40EC0A4071F7E88B');
        $this->addSql('DROP TABLE image_archive');
        $this->addSql('ALTER TABLE user CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE modified_at modified_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
    }
}
