<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616141213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE genetic_markers (id SERIAL NOT NULL, gene_symbol VARCHAR(30) NOT NULL, full_name VARCHAR(150) NOT NULL, rs_id VARCHAR(50) DEFAULT NULL, possible_values JSON NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE marker_drug_relations (id SERIAL NOT NULL, marker_id INT NOT NULL, drug_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E3C034B5474460EB ON marker_drug_relations (marker_id)');
        $this->addSql('CREATE INDEX IDX_E3C034B5AABCA765 ON marker_drug_relations (drug_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_marker_drug ON marker_drug_relations (marker_id, drug_id)');
        $this->addSql('CREATE TABLE patient_genetic_results (id SERIAL NOT NULL, patient_id INT NOT NULL, marker_id INT NOT NULL, value VARCHAR(20) NOT NULL, test_date DATE DEFAULT NULL, comment TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2BEC85AF6B899279 ON patient_genetic_results (patient_id)');
        $this->addSql('CREATE INDEX IDX_2BEC85AF474460EB ON patient_genetic_results (marker_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_patient_marker ON patient_genetic_results (patient_id, marker_id)');
        $this->addSql('ALTER TABLE marker_drug_relations ADD CONSTRAINT FK_E3C034B5474460EB FOREIGN KEY (marker_id) REFERENCES genetic_markers (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE marker_drug_relations ADD CONSTRAINT FK_E3C034B5AABCA765 FOREIGN KEY (drug_id) REFERENCES drugs (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_genetic_results ADD CONSTRAINT FK_2BEC85AF6B899279 FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_genetic_results ADD CONSTRAINT FK_2BEC85AF474460EB FOREIGN KEY (marker_id) REFERENCES genetic_markers (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_4a48ce0d6b899279 RENAME TO idx_treatment_patient_id');
        $this->addSql('ALTER INDEX idx_4a48ce0daabca765 RENAME TO idx_treatment_drug_id');
        $this->addSql('ALTER INDEX idx_1483a5e939edbb1a RENAME TO idx_users_medpers');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE marker_drug_relations DROP CONSTRAINT FK_E3C034B5474460EB');
        $this->addSql('ALTER TABLE marker_drug_relations DROP CONSTRAINT FK_E3C034B5AABCA765');
        $this->addSql('ALTER TABLE patient_genetic_results DROP CONSTRAINT FK_2BEC85AF6B899279');
        $this->addSql('ALTER TABLE patient_genetic_results DROP CONSTRAINT FK_2BEC85AF474460EB');
        $this->addSql('DROP TABLE genetic_markers');
        $this->addSql('DROP TABLE marker_drug_relations');
        $this->addSql('DROP TABLE patient_genetic_results');
        $this->addSql('ALTER INDEX idx_treatment_patient_id RENAME TO idx_4a48ce0d6b899279');
        $this->addSql('ALTER INDEX idx_treatment_drug_id RENAME TO idx_4a48ce0daabca765');
        $this->addSql('ALTER INDEX idx_users_medpers RENAME TO idx_1483a5e939edbb1a');
    }
}
