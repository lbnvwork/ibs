<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds max_deeplinks table for protected MAX deep-link tokens.
 */
final class Version20260821120000AddMaxDeeplinks extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add max_deeplinks table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE max_deeplinks (id SERIAL NOT NULL, patient_id INT NOT NULL, token VARCHAR(64) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_max_deeplink_patient ON max_deeplinks (patient_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_max_deeplink_token ON max_deeplinks (token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE max_deeplinks');
    }
}
