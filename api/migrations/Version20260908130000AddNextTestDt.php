<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Добавить next_test_dt (дата следующей сдачи МНО) в appointments.
 */
final class Version20260908130000AddNextTestDt extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавить next_test_dt (дата следующей сдачи МНО) в appointments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE appointments ADD COLUMN next_test_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("COMMENT ON COLUMN appointments.next_test_dt IS 'Дата следующей сдачи МНО'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("COMMENT ON COLUMN appointments.next_test_dt IS NULL");
        $this->addSql('ALTER TABLE appointments DROP COLUMN next_test_dt');
    }
}
