<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526081751AlterPatientsAddEmail extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patients ADD email VARCHAR(180) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE patients DROP email');
    }
}
