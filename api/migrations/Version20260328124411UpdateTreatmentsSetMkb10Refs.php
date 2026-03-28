<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328124411UpdateTreatmentsSetMkb10Refs extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // ==========================================
        // 1. Присваиваем diagnosis_code по id
        // ==========================================

        // Пороки аортального клапана и протезирование
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I35.0' WHERE id IN (4347, 4350)");
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I35.1' WHERE id = 4328");
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I35.2' WHERE id IN (4349, 3751)");
        $this->addSql("UPDATE treatments SET diagnosis_code = 'Z95.2' WHERE id IN (4351, 4352, 4354, 3965, 3982, 4082, 4073, 4209, 4236, 4237, 4293, 4273)");
        $this->addSql("UPDATE treatments SET diagnosis_code = 'Z95.3' WHERE id IN (4108, 4343, 4348, 3983, 4080, 4150, 4301, 4313)");
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I05.5' WHERE id = 1198"); // Пртезирование метрального клапана -> уже I05.5
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I05.9' WHERE id = 19");

        // Тромбозы и ТЭЛА
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I80.1' WHERE id IN (828, 3368, 4137)");
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I26.9' WHERE id = 1950");
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I82.2' WHERE id = 2416"); // мезетериальный тромбоз

        // Инфаркты и ИБС
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I21.9' WHERE id = 3167");
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I25.8' WHERE id IN (1326, 4151, 3613)"); // аневризма ЛЖ, тромбоз полости ЛЖ
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I20.8' WHERE id = 3585"); // I 20.8
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I63.9' WHERE id = 3901"); // инфаркт мозга

        // Фибрилляция предсердий
        $this->addSql("UPDATE treatments SET diagnosis_code = 'I48.1' WHERE id = 3558"); // персистирующая форма ФП

        // ВПС
        $this->addSql("UPDATE treatments SET diagnosis_code = 'Q24.8' WHERE id = 3955"); // коррегированный ВПС, пластика ДМПП

        // Исключаем из обработки записи «Контроль качества» (оставляем без кода)
        $this->addSql("UPDATE treatments SET diagnosis_code = NULL WHERE id IN (2629, 2630)");

        // ==========================================
        // 2. Обновляем mkb10_id для всех, у кого diagnosis_code совпадает с mkb_code
        // ==========================================
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
        // Откат: очищаем только что присвоенные diagnosis_code и сбрасываем mkb10_id для них (не обязательно)
        $this->addSql("
            UPDATE treatments SET diagnosis_code = NULL, mkb10_id = NULL
            WHERE id IN (
                4347, 4349, 4350, 4351, 4352, 4354, 4328, 4331, 4343, 4348,
                3982, 3983, 3965, 4082, 828, 19, 1326, 1950, 2416, 2629, 2630,
                3167, 3368, 3558, 3585, 3751, 3901, 3955, 4073, 4080, 4137,
                4150, 4151, 4209, 4236, 4237, 4273, 4293, 4301, 4313, 1198
            )
        ");
    }
}
