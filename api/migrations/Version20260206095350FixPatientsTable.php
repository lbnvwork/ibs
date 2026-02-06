<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206095350FixPatientsTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix patients table: move data from secondname to second_name and set correct constraints';
    }

    public function up(Schema $schema): void
    {
// 1. Перенос данных из secondname в second_name, если second_name пустой
        $this->addSql("
            UPDATE patients 
            SET second_name = secondname 
            WHERE (second_name IS NULL OR second_name = '') 
                AND secondname IS NOT NULL 
                AND secondname != ''
        ");

        // 2. Удаляем временный столбец secondname
        $this->addSql('ALTER TABLE patients DROP COLUMN IF EXISTS secondname');

        // 3. Удаляем старый внешний ключ (чтобы Doctrine создал свой)
        $this->addSql('ALTER TABLE patients DROP CONSTRAINT IF EXISTS fk_patients_hospital');

        // 4. Удаляем старый внешний ключ с именем Doctrine
        $this->addSql('ALTER TABLE patients DROP CONSTRAINT IF EXISTS FK_2CCC2E2C63DBB69');

        // 5. Устанавливаем значения по умолчанию для NULL в обязательных полях
        $this->addSql("UPDATE patients SET firstname = '' WHERE firstname IS NULL");
        $this->addSql("UPDATE patients SET second_name = '' WHERE second_name IS NULL");
        $this->addSql("UPDATE patients SET lastname = '' WHERE lastname IS NULL");
        $this->addSql("UPDATE patients SET birthday = '1970-01-01'::timestamp WHERE birthday IS NULL");
        $this->addSql("UPDATE patients SET sex = 0 WHERE sex IS NULL");
        $this->addSql("UPDATE patients SET sms_phone = '' WHERE sms_phone IS NULL");

        // 6. Устанавливаем NOT NULL для обязательных полей
        $this->addSql('ALTER TABLE patients ALTER COLUMN firstname SET NOT NULL');
        $this->addSql('ALTER TABLE patients ALTER COLUMN second_name SET NOT NULL');
        $this->addSql('ALTER TABLE patients ALTER COLUMN lastname SET NOT NULL');
        $this->addSql('ALTER TABLE patients ALTER COLUMN birthday SET NOT NULL');
        $this->addSql('ALTER TABLE patients ALTER COLUMN sex SET NOT NULL');
        $this->addSql('ALTER TABLE patients ALTER COLUMN sms_phone SET NOT NULL');

        // 7. Устанавливаем DEFAULT 0 для sex
        $this->addSql('ALTER TABLE patients ALTER COLUMN sex SET DEFAULT 0');

        // Не создаем новый внешний ключ - Doctrine создаст его сам
    }

    public function down(Schema $schema): void
    {
        // Восстанавливаем старую структуру

        // Возвращаем столбец secondname
        $this->addSql('ALTER TABLE patients ADD COLUMN secondname TEXT DEFAULT NULL');
        $this->addSql('UPDATE patients SET secondname = second_name');

        // Убираем NOT NULL
        $this->addSql('ALTER TABLE patients ALTER COLUMN firstname DROP NOT NULL');
        $this->addSql('ALTER TABLE patients ALTER COLUMN second_name DROP NOT NULL');
        $this->addSql('ALTER TABLE patients ALTER COLUMN lastname DROP NOT NULL');
        $this->addSql('ALTER TABLE patients ALTER COLUMN birthday DROP NOT NULL');
        $this->addSql('ALTER TABLE patients ALTER COLUMN sex DROP NOT NULL');
        $this->addSql('ALTER TABLE patients ALTER COLUMN sms_phone DROP NOT NULL');

        // Убираем DEFAULT
        $this->addSql('ALTER TABLE patients ALTER COLUMN sex DROP DEFAULT');

        // Восстанавливаем внешний ключ (базовый)
        $this->addSql('ALTER TABLE patients ADD CONSTRAINT FK_2CCC2E2C63DBB69 FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
