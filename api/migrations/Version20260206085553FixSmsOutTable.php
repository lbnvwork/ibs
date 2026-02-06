<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206085553FixSmsOutTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove extra columns from sms_out table and ensure text column exists';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sms_out DROP COLUMN IF EXISTS column2');
        $this->addSql('ALTER TABLE sms_out DROP COLUMN IF EXISTS column3');
        $this->addSql('ALTER TABLE sms_out DROP COLUMN IF EXISTS column4');
        $this->addSql('ALTER TABLE sms_out DROP COLUMN IF EXISTS column5');
        $this->addSql('ALTER TABLE sms_out DROP COLUMN IF EXISTS column6');
        $this->addSql('ALTER TABLE sms_out DROP COLUMN IF EXISTS column7');
        $this->addSql('ALTER TABLE sms_out DROP COLUMN IF EXISTS column8');
        $this->addSql('ALTER TABLE sms_out DROP COLUMN IF EXISTS column9');

    }

    public function down(Schema $schema): void
    {
    }
}
