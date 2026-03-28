<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20260328122502UpdateTreatmentsSetMkb10Refs extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix remaining diagnosis codes and link to mkb10';
    }

    public function up(Schema $schema): void
    {
        // ============================
        // 1. Исправляем некорректные коды
        // ============================

        // I80.20 -> I80.2
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I80.2' WHERE diagnosis_code = 'I80.20'");

        // I481 -> I48.1
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I48.1' WHERE diagnosis_code = 'I481'");

        // I480 -> I48.0
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I48.0' WHERE diagnosis_code = 'I480'");

        // I352 -> I35.2
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I35.2' WHERE diagnosis_code = 'I352'");

        // I269 -> I26.9
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I26.9' WHERE diagnosis_code = 'I269'");

        // I802 -> I80.2
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I80.2' WHERE diagnosis_code = 'I802'");

        // I252 -> I25.2
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I25.2' WHERE diagnosis_code = 'I252'");

        // I22.01 – возможно, нужно оставить, он существует в МКБ-10 (повторный инфаркт передней стенки)
        // I21.01 – существует, оставляем.

        // ============================
        // 2. Заполняем пустые diagnosis_code по тексту diagnosis
        // ============================

        // Фибрилляция предсердий (I48.0, I48.1, I48.9)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = CASE
                WHEN diagnosis ILIKE '%фибрилляция предсердий%' AND (diagnosis ILIKE '%пароксизмальная%' OR diagnosis ILIKE '%пароксизмальная%') THEN 'I48.0'
                WHEN diagnosis ILIKE '%фибрилляция предсердий%' AND (diagnosis ILIKE '%персистирующая%' OR diagnosis ILIKE '%постоянная%') THEN 'I48.1'
                WHEN diagnosis ILIKE '%фибрилляция предсердий%' THEN 'I48.9'
                WHEN diagnosis ILIKE '%мерцательная аритмия%' AND (diagnosis ILIKE '%пароксизмальная%') THEN 'I48.0'
                WHEN diagnosis ILIKE '%мерцательная аритмия%' AND (diagnosis ILIKE '%персистирующая%' OR diagnosis ILIKE '%постоянная%') THEN 'I48.1'
                WHEN diagnosis ILIKE '%мерцательная аритмия%' THEN 'I48.9'
                WHEN diagnosis ILIKE '%ФП%' AND (diagnosis ILIKE '%пароксизмальная%') THEN 'I48.0'
                WHEN diagnosis ILIKE '%ФП%' AND (diagnosis ILIKE '%персистирующая%' OR diagnosis ILIKE '%постоянная%') THEN 'I48.1'
                WHEN diagnosis ILIKE '%ФП%' THEN 'I48.9'
            END
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
        ");

        // Инфаркт миокарда (I21.x)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = CASE
                WHEN diagnosis ILIKE '%инфаркт миокарда%' AND diagnosis ILIKE '%передней%' THEN 'I21.0'
                WHEN diagnosis ILIKE '%инфаркт миокарда%' AND diagnosis ILIKE '%нижней%' THEN 'I21.1'
                WHEN diagnosis ILIKE '%инфаркт миокарда%' AND diagnosis ILIKE '%задней%' THEN 'I21.2'
                WHEN diagnosis ILIKE '%инфаркт миокарда%' AND diagnosis ILIKE '%Q%' THEN 'I21.9'
                WHEN diagnosis ILIKE '%инфаркт миокарда%' AND diagnosis ILIKE '%трансмуральный%' THEN 'I21.9'
                WHEN diagnosis ILIKE '%ИМ%' AND diagnosis ILIKE '%передней%' THEN 'I21.0'
                WHEN diagnosis ILIKE '%ИМ%' AND diagnosis ILIKE '%нижней%' THEN 'I21.1'
                WHEN diagnosis ILIKE '%ИМ%' AND diagnosis ILIKE '%задней%' THEN 'I21.2'
                WHEN diagnosis ILIKE '%ИМ%' THEN 'I21.9'
            END
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
        ");

        // Тромбоз глубоких вен / тромбофлебит (I80.1, I80.2, I80.9)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = CASE
                WHEN diagnosis ILIKE '%тромбофлебит глубоких вен%' AND diagnosis ILIKE '%правой%' THEN 'I80.1'
                WHEN diagnosis ILIKE '%тромбофлебит глубоких вен%' AND diagnosis ILIKE '%левой%' THEN 'I80.1'
                WHEN diagnosis ILIKE '%тромбофлебит глубоких вен%' THEN 'I80.1'
                WHEN diagnosis ILIKE '%илеофеморальный тромбоз%' THEN 'I80.1'
                WHEN diagnosis ILIKE '%тромбоз глубоких вен%' THEN 'I80.1'
                WHEN diagnosis ILIKE '%тромбофлебит%' AND diagnosis ILIKE '%нижних конечностей%' THEN 'I80.1'
                WHEN diagnosis ILIKE '%тромбоз ЗББВ%' THEN 'I80.1'
            END
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
        ");

        // ТЭЛА (I26.0, I26.9)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = CASE
                WHEN diagnosis ILIKE '%ТЭЛА%' AND diagnosis ILIKE '%острое легочное сердце%' THEN 'I26.0'
                WHEN diagnosis ILIKE '%ТЭЛА%' THEN 'I26.9'
            END
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
        ");

        // Протезирование клапанов (Z95.2, Z95.3, Z95.4)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = CASE
                WHEN diagnosis ILIKE '%протезирование аортального клапана%' THEN 'Z95.2'
                WHEN diagnosis ILIKE '%протезирование митрального клапана%' THEN 'Z95.3'
                WHEN diagnosis ILIKE '%протезирование%' AND diagnosis ILIKE '%клапана%' THEN 'Z95.4'
            END
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
        ");

        // Аневризма ЛЖ, тромбоз полости ЛЖ – как осложнения ИБС (I25.8)
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = 'I25.8'
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
              AND (diagnosis ILIKE '%аневризма ЛЖ%' OR diagnosis ILIKE '%тромбоз полости ЛЖ%' OR diagnosis ILIKE '%тромбоз ЛЖ%')
        ");

        // Пороки клапанов
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = CASE
                WHEN diagnosis ILIKE '%митральный стеноз%' THEN 'I05.0'
                WHEN diagnosis ILIKE '%аортальный стеноз%' THEN 'I35.0'
                WHEN diagnosis ILIKE '%недостаточность АК%' THEN 'I35.1'
                WHEN diagnosis ILIKE '%сочетанный порок аортального клапана%' THEN 'I35.2'
                WHEN diagnosis ILIKE '%комбинированный порок аортального клапана%' THEN 'I35.2'
                WHEN diagnosis ILIKE '%ревматическая болезнь%' AND diagnosis ILIKE '%митральный%' THEN 'I05.9'
                WHEN diagnosis ILIKE '%ревматическая болезнь%' AND diagnosis ILIKE '%аортальный%' THEN 'I06.9'
            END
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
        ");

        // Инфекционный эндокардит
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = 'I33.0'
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
              AND diagnosis ILIKE '%инфекционный эндокардит%'
        ");

        // ОНМК / инсульт
        $this->addSql("
            UPDATE treatments
            SET diagnosis_code = 'I64'
            WHERE mkb10_id IS NULL AND (diagnosis_code IS NULL OR diagnosis_code = '')
              AND (diagnosis ILIKE '%ОНМК%' OR diagnosis ILIKE '%инсульт%')
        ");

        // ============================
        // 3. Повторно обновляем mkb10_id для всех записей, у которых diagnosis_code не пуст
        // ============================

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
        $this->addSql("UPDATE treatments SET mkb10_id = NULL WHERE mkb10_id IS NOT NULL");
    }
}
