<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206093414DropOldAppointmentForeignKeys extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix appointments table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE appointments DROP CONSTRAINT IF EXISTS fk_appointments_sms');
        $this->addSql('ALTER TABLE appointments DROP CONSTRAINT IF EXISTS fk_appointments_treatment');
        $this->addSql('ALTER TABLE appointments ALTER COLUMN appointment_dt SET NOT NULL');
        $this->addSql('ALTER TABLE appointments ALTER COLUMN doze SET NOT NULL');
        $this->addSql('ALTER TABLE appointments ALTER COLUMN doctor_name SET NOT NULL');
        $this->addSql('ALTER TABLE appointments ALTER COLUMN doze2 SET DEFAULT -1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE appointments ALTER COLUMN appointment_dt DROP NOT NULL');
        $this->addSql('ALTER TABLE appointments ALTER COLUMN doze DROP NOT NULL');
        $this->addSql('ALTER TABLE appointments ALTER COLUMN doctor_name DROP NOT NULL');
        $this->addSql('ALTER TABLE appointments ALTER COLUMN doze2 DROP DEFAULT');
    }
}
