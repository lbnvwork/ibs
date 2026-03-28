<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328110547UpdateTreatmentsSetMkb10Refs extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate existing diagnosis_code values in treatments table to mkb10_id foreign key reference';
    }

    public function up(Schema $schema): void
    {
        // 1. Базовое очищение: убираем пробелы, заменяем русские Т, А на латинские, переводим в верхний регистр
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = UPPER(
                REPLACE(
                    REPLACE(
                        REPLACE(TRIM(diagnosis_code), ' ', ''),
                        'Т', 'T'
                    ),
                    'А', 'A'
                )
            )
            WHERE diagnosis_code IS NOT NULL AND diagnosis_code != ''
        ");

        // 2. Удаляем все символы, кроме букв, цифр и точки
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = REGEXP_REPLACE(diagnosis_code, '[^A-Z0-9.]', '', 'g')
            WHERE diagnosis_code IS NOT NULL AND diagnosis_code != ''
        ");

        // 3. Заменяем слеши и запятые на точку
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = REPLACE(REPLACE(diagnosis_code, '/', '.'), ',', '.')
            WHERE diagnosis_code ~ '[/,]'
        ");

        // 4. Убираем лишние точки в конце
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = REGEXP_REPLACE(diagnosis_code, '\\.+$', '')
            WHERE diagnosis_code ~ '\\.$'
        ");

        // 5. Исправляем паттерн I.48.0 → I48.0
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = REGEXP_REPLACE(diagnosis_code, '^I\\.(\\d+\\.\\d+)$', 'I\\1')
            WHERE diagnosis_code ~ '^I\\.\\d+\\.\\d+$'
        ");

        // 6. Удаляем повторяющиеся точки в середине
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = REGEXP_REPLACE(diagnosis_code, '\\.+', '.', 'g')
            WHERE diagnosis_code ~ '\\.\\.'
        ");

        // 7. Для кодов, начинающихся с цифр (например, '48.0'), добавляем 'I' в начало
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = CONCAT('I', diagnosis_code)
            WHERE diagnosis_code ~ '^[0-9]'
        ");

        // 8. Убираем возможные лишние точки после исправления (если добавили I и осталась точка в начале)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = REGEXP_REPLACE(diagnosis_code, '^\\.+', '')
            WHERE diagnosis_code ~ '^\\.'
        ");

        // 9. Обновляем mkb10_id для всех записей, у которых diagnosis_code совпадает с существующим mkb_code
        $this->addSql("
            UPDATE treatments t
            SET mkb10_id = m.id
            FROM mkb10 m
            WHERE t.mkb10_id IS NULL
              AND t.diagnosis_code IS NOT NULL
              AND t.diagnosis_code != ''
              AND m.mkb_code = t.diagnosis_code
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE treatments SET mkb10_id = NULL WHERE mkb10_id IS NOT NULL');
    }
}
