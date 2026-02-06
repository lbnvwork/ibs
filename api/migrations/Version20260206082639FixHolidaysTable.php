<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206082639FixHolidaysTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix holidays table structure for Doctrine';
    }

    public function up(Schema $schema): void
    {
        // 1. Сначала проверим, какие данные у нас есть в столбцах

        // Если в h_month NULL, а в hmonth есть значение, копируем из hmonth в h_month
        $this->addSql("
            UPDATE holidays 
            SET h_month = hmonth 
            WHERE h_month IS NULL AND hmonth IS NOT NULL
        ");

        // Если в h_day NULL, а в hday есть значение, копируем из hday в h_day
        $this->addSql("
            UPDATE holidays 
            SET h_day = hday 
            WHERE h_day IS NULL AND hday IS NOT NULL
        ");

        // 2. Теперь удаляем старые столбцы hmonth и hday
        $this->addSql('ALTER TABLE holidays DROP COLUMN IF EXISTS hmonth');
        $this->addSql('ALTER TABLE holidays DROP COLUMN IF EXISTS hday');

        // 3. Переименовываем hyear в h_year
        $this->addSql('ALTER TABLE holidays RENAME COLUMN hyear TO h_year');

        // 4. Устанавливаем NOT NULL для h_month, h_day, h_year
        // Сначала установим значения по умолчанию для NULL
        $this->addSql("UPDATE holidays SET h_month = 1 WHERE h_month IS NULL");
        $this->addSql("UPDATE holidays SET h_day = 1 WHERE h_day IS NULL");
        $this->addSql("UPDATE holidays SET h_year = 2015 WHERE h_year IS NULL");

        // Теперь устанавливаем NOT NULL
        $this->addSql('ALTER TABLE holidays ALTER COLUMN h_month SET NOT NULL');
        $this->addSql('ALTER TABLE holidays ALTER COLUMN h_day SET NOT NULL');
        $this->addSql('ALTER TABLE holidays ALTER COLUMN h_year SET NOT NULL');

        // 5. Устанавливаем DEFAULT 2015 для h_year
        $this->addSql('ALTER TABLE holidays ALTER COLUMN h_year SET DEFAULT 2015');
    }

    public function down(Schema $schema): void
    {
        // Возвращаем старые имена
        $this->addSql('ALTER TABLE holidays RENAME COLUMN h_month TO hmonth');
        $this->addSql('ALTER TABLE holidays RENAME COLUMN h_day TO hday');
        $this->addSql('ALTER TABLE holidays RENAME COLUMN h_year TO hyear');

        // Убираем NOT NULL
        $this->addSql('ALTER TABLE holidays ALTER COLUMN hmonth DROP NOT NULL');
        $this->addSql('ALTER TABLE holidays ALTER COLUMN hday DROP NOT NULL');
        $this->addSql('ALTER TABLE holidays ALTER COLUMN hyear DROP NOT NULL');

        // Убираем DEFAULT
        $this->addSql('ALTER TABLE holidays ALTER COLUMN hyear DROP DEFAULT');
    }
}
