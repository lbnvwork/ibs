<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260204144554FixTreatmentsTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fixes for treatments table to match Entity';
    }

    public function up(Schema $schema): void
    {
        // 1. Добавляем новый столбец diagnosis_code как VARCHAR(255)
        $this->addSql('ALTER TABLE treatments ADD diagnosis_code VARCHAR(255) DEFAULT NULL');

        // 2. Переносим данные из старого столбца в новый (если есть данные)
        $this->addSql('UPDATE treatments SET diagnosis_code = "diagnosisCode" WHERE "diagnosisCode" IS NOT NULL');

        // 3. Удаляем старый столбец diagnosisCode
        $this->addSql('ALTER TABLE treatments DROP "diagnosisCode"');

        // 4. Изменяем поля на NOT NULL
        $this->addSql('ALTER TABLE treatments ALTER COLUMN diagnosis SET NOT NULL');
        $this->addSql('ALTER TABLE treatments ALTER COLUMN mno_from SET NOT NULL');
        $this->addSql('ALTER TABLE treatments ALTER COLUMN mno_to SET NOT NULL');
        $this->addSql('ALTER TABLE treatments ALTER COLUMN beg_dt SET NOT NULL');

        // 5. Убираем DEFAULT у beg_dt
        $this->addSql('ALTER TABLE treatments ALTER COLUMN beg_dt DROP DEFAULT');

        // 6. Добавляем DEFAULT значения для hemorrhages и flags
        $this->addSql('ALTER TABLE treatments ALTER COLUMN hemorrhages SET DEFAULT 0');
        $this->addSql('ALTER TABLE treatments ALTER COLUMN flags SET DEFAULT 0');

        // 7. Создаем уникальный индекс на code
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4A48CE0D77153098 ON treatments (code)');
    }

    public function down(Schema $schema): void
    {
        // 1. Удаляем уникальный индекс
        $this->addSql('DROP INDEX UNIQ_4A48CE0D77153098');

        // 2. Возвращаем DEFAULT значения
        $this->addSql('ALTER TABLE treatments ALTER COLUMN hemorrhages DROP DEFAULT');
        $this->addSql('ALTER TABLE treatments ALTER COLUMN flags DROP DEFAULT');

        // 3. Возвращаем DEFAULT у beg_dt
        $this->addSql('ALTER TABLE treatments ALTER COLUMN beg_dt SET DEFAULT NULL');
        $this->addSql('ALTER TABLE treatments ALTER COLUMN beg_dt DROP NOT NULL');

        // 4. Возвращаем nullable для остальных полей
        $this->addSql('ALTER TABLE treatments ALTER COLUMN mno_to DROP NOT NULL');
        $this->addSql('ALTER TABLE treatments ALTER COLUMN mno_from DROP NOT NULL');
        $this->addSql('ALTER TABLE treatments ALTER COLUMN diagnosis DROP NOT NULL');

        // 5. Добавляем обратно старый столбец diagnosisCode
        $this->addSql('ALTER TABLE treatments ADD "diagnosisCode" TEXT DEFAULT NULL');

        // 6. Переносим данные обратно (если были)
        $this->addSql('UPDATE treatments SET "diagnosisCode" = diagnosis_code WHERE diagnosis_code IS NOT NULL');

        // 7. Удаляем новый столбец diagnosis_code
        $this->addSql('ALTER TABLE treatments DROP diagnosis_code');
    }
}