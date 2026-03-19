<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260319091454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unused feedback and image_archive tables';
    }

    public function up(Schema $schema): void
    {
        // Drop unused tables that were never implemented in the application
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY FK_D229445871F7E88B');
        $this->addSql('DROP TABLE feedback');
        $this->addSql('ALTER TABLE image_archive DROP FOREIGN KEY FK_40EC0A4071F7E88B');
        $this->addSql('DROP TABLE image_archive');
    }

    public function down(Schema $schema): void
    {
        // Recreate tables if migration needs to be rolled back
        $this->addSql('CREATE TABLE feedback (id INT AUTO_INCREMENT NOT NULL, feedback LONGTEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, event_id INT NOT NULL, INDEX IDX_D229445871F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D229445871F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('CREATE TABLE image_archive (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, event_id INT NOT NULL, INDEX IDX_40EC0A4071F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE image_archive ADD CONSTRAINT FK_40EC0A4071F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
    }
}
