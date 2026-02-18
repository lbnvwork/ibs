<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20260218100758AddOptimizationIndexes extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes to optimize patient filtering by drug group';
    }

    public function up(Schema $schema): void
    {
        // Индекс для связи treatments -> patient
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_treatment_patient_id ON treatments (patient_id)');

        // Комбинированный индекс для поиска последнего лечения пациента (по дате)
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_treatment_patient_beg_dt ON treatments (patient_id, beg_dt DESC)');

        // Индекс для связи treatments -> drug
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_treatment_drug_id ON treatments (drug_id)');

        // Индекс для фильтрации drugs по группе
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_drug_group_id ON drugs (group_id)');

        // Обновляем статистику планировщика (рекомендуется после создания индексов)
        $this->addSql('ANALYZE treatments');
        $this->addSql('ANALYZE drugs');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_treatment_patient_id');
        $this->addSql('DROP INDEX IF EXISTS idx_treatment_patient_beg_dt');
        $this->addSql('DROP INDEX IF EXISTS idx_treatment_drug_id');
        $this->addSql('DROP INDEX IF EXISTS idx_drug_group_id');
    }
}
