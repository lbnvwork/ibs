<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328123737UpdateTreatmentsSetMkb10Refs extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1. Исправляем явные опечатки (если остались)
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I80.1' WHERE diagnosis_code = 'II80.1'");
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I21.0' WHERE diagnosis_code = 'I21.01'");
        // I22.01 оставляем, это корректный код (повторный инфаркт передней стенки)
        // I20.01 оставляем (нестабильная стенокардия)

        // 2. Заполняем пустые diagnosis_code по ключевым словам
        // Используем только коды, которые есть в mkb10 (I80.1, I82.2, Z95.2, I48.0, I48.1, I48.9, I21.0, I21.1, I21.2, I21.9, I35.0, I35.1, I35.2, I33.0, I64, I25.8 и др.)

        // Тромбозы (I80.1, I82.2)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = CASE
                WHEN diagnosis ILIKE '%илеофеморальный%' THEN 'I80.1'
                WHEN diagnosis ILIKE '%тромбоз НПВ%' THEN 'I82.2'
                WHEN diagnosis ILIKE '%тромбоз глубоких вен%' THEN 'I80.1'
                WHEN diagnosis ILIKE '%тромбофлебит%' THEN 'I80.1'
            END
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
              AND (diagnosis ILIKE '%тромбоз%' OR diagnosis ILIKE '%тромбофлебит%')
        ");

        // Протезирование клапанов (Z95.2)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = 'Z95.2'
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
              AND diagnosis ILIKE '%протезирование%' AND diagnosis ILIKE '%клапана%'
        ");

        // Фибрилляция предсердий (I48.x)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = CASE
                WHEN diagnosis ILIKE '%пароксизмальная%' THEN 'I48.0'
                WHEN diagnosis ILIKE '%персистирующая%' OR diagnosis ILIKE '%постоянная%' THEN 'I48.1'
                ELSE 'I48.9'
            END
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
              AND (diagnosis ILIKE '%фибрилляция%' OR diagnosis ILIKE '%мерцательная аритмия%' OR diagnosis ILIKE '%ФП%')
        ");

        // Инфаркт миокарда (I21.x, I22.x)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = CASE
                WHEN diagnosis ILIKE '%передней%' THEN 'I21.0'
                WHEN diagnosis ILIKE '%нижней%' THEN 'I21.1'
                WHEN diagnosis ILIKE '%задней%' THEN 'I21.2'
                WHEN diagnosis ILIKE '%повторный%' AND diagnosis ILIKE '%передней%' THEN 'I22.0'
                WHEN diagnosis ILIKE '%повторный%' THEN 'I22.9'
                ELSE 'I21.9'
            END
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
              AND (diagnosis ILIKE '%инфаркт миокарда%' OR diagnosis ILIKE '%ИМ%')
        ");

        // Пороки аортального клапана (I35.0, I35.1, I35.2)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = CASE
                WHEN diagnosis ILIKE '%стеноз%' AND diagnosis ILIKE '%аортального%' THEN 'I35.0'
                WHEN diagnosis ILIKE '%недостаточность%' AND diagnosis ILIKE '%аортального%' THEN 'I35.1'
                WHEN diagnosis ILIKE '%сочетанный%' OR diagnosis ILIKE '%комбинированный%' THEN 'I35.2'
                WHEN diagnosis ILIKE '%аортальный порок%' THEN 'I35.2'
            END
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
              AND diagnosis ILIKE '%аортальный%' AND diagnosis ILIKE '%клапан%'
        ");

        // Митральный порок (I05.0, I05.1, I05.2, I05.9)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = CASE
                WHEN diagnosis ILIKE '%стеноз%' AND diagnosis ILIKE '%митральный%' THEN 'I05.0'
                WHEN diagnosis ILIKE '%недостаточность%' AND diagnosis ILIKE '%митральный%' THEN 'I05.1'
                WHEN diagnosis ILIKE '%сочетанный%' AND diagnosis ILIKE '%митральный%' THEN 'I05.2'
                ELSE 'I05.9'
            END
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
              AND diagnosis ILIKE '%митральный%'
        ");

        // Инфекционный эндокардит (I33.0)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = 'I33.0'
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
              AND diagnosis ILIKE '%инфекционный эндокардит%'
        ");

        // ОНМК / инсульт (I64)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = 'I64'
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
              AND (diagnosis ILIKE '%ОНМК%' OR diagnosis ILIKE '%инсульт%')
        ");

        // ИБС, нераспределённая (I25.8)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = 'I25.8'
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
              AND diagnosis ILIKE '%ИБС%' AND diagnosis NOT ILIKE '%фибрилляция%' AND diagnosis NOT ILIKE '%инфаркт%'
        ");

        // Гипертоническая болезнь (I10)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = 'I10'
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
              AND diagnosis ILIKE '%гипертоническая болезнь%'
        ");

        // Аневризма ЛЖ, тромбоз полости ЛЖ (I25.8)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = 'I25.8'
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
              AND (diagnosis ILIKE '%аневризма ЛЖ%' OR diagnosis ILIKE '%тромбоз полости ЛЖ%')
        ");

        // 3. Теперь связываем mkb10_id для всех записей, у которых diagnosis_code не пуст и mkb10_id всё ещё NULL
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
    }
}
