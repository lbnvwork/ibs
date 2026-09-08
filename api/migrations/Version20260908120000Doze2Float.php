<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * doze2: int → float (дробная вторая доза чередования).
 */
final class Version20260908120000Doze2Float extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'doze2 int → float (дробная вторая доза чередования)';
    }

    public function up(Schema $schema): void
    {
        // Нормализуем 0 → -1 (соглашение: -1 = без чередования).
        $this->addSql('UPDATE appointments SET doze2 = -1 WHERE doze2 = 0');
        $this->addSql('UPDATE test_history SET doze2 = -1 WHERE doze2 = 0');

        // int → double precision.
        $this->addSql('ALTER TABLE appointments ALTER COLUMN doze2 TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE test_history ALTER COLUMN doze2 TYPE DOUBLE PRECISION');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE appointments ALTER COLUMN doze2 TYPE INT USING doze2::int');
        $this->addSql('ALTER TABLE test_history ALTER COLUMN doze2 TYPE INT USING doze2::int');
    }
}
