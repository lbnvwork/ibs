<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206122214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE test_histories_by_assistant ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE test_histories_by_assistant ADD CONSTRAINT FK_1539B759A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_1539B759A76ED395 ON test_histories_by_assistant (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE test_histories_by_assistant DROP CONSTRAINT FK_1539B759A76ED395');
        $this->addSql('DROP INDEX IDX_1539B759A76ED395');
        $this->addSql('ALTER TABLE test_histories_by_assistant DROP user_id');
    }
}
