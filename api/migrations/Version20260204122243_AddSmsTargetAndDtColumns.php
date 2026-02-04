<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260204122243_AddSmsTargetAndDtColumns extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $checkSmsTarget = $this->connection->fetchOne("
            SELECT COUNT(*) 
            FROM information_schema.columns 
            WHERE table_name = 'sms_out' 
            AND column_name = 'sms_target'
        ");

        if (!$checkSmsTarget) {
            $this->addSql('ALTER TABLE sms_out ADD COLUMN sms_target TEXT');
        }

        $checkDt = $this->connection->fetchOne("
            SELECT COUNT(*) 
            FROM information_schema.columns 
            WHERE table_name = 'sms_in' 
            AND column_name = 'dt'
        ");

        if (!$checkDt) {
            $this->addSql('ALTER TABLE sms_in ADD COLUMN dt TIMESTAMP DEFAULT NULL');
        }

    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sms_out DROP COLUMN IF EXISTS sms_target');
        $this->addSql('ALTER TABLE sms_in DROP COLUMN IF EXISTS dt');
    }
}
