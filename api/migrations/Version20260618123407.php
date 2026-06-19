<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260618123407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE genetic_marker_values (id SERIAL NOT NULL, marker_id INT NOT NULL, value VARCHAR(50) NOT NULL, label VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B1C6BC1E474460EB ON genetic_marker_values (marker_id)');
        $this->addSql('ALTER TABLE genetic_marker_values ADD CONSTRAINT FK_B1C6BC1E474460EB FOREIGN KEY (marker_id) REFERENCES genetic_markers (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE genetic_markers DROP possible_values');
        $this->addSql('ALTER TABLE patient_genetic_results ADD marker_value_id INT NOT NULL');
        $this->addSql('ALTER TABLE patient_genetic_results DROP value');
        $this->addSql('ALTER TABLE patient_genetic_results ADD CONSTRAINT FK_2BEC85AF198FDE2D FOREIGN KEY (marker_value_id) REFERENCES genetic_marker_values (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_2BEC85AF198FDE2D ON patient_genetic_results (marker_value_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE patient_genetic_results DROP CONSTRAINT FK_2BEC85AF198FDE2D');
        $this->addSql('ALTER TABLE genetic_marker_values DROP CONSTRAINT FK_B1C6BC1E474460EB');
        $this->addSql('DROP TABLE genetic_marker_values');
        $this->addSql('ALTER TABLE genetic_markers ADD possible_values JSON NOT NULL');
        $this->addSql('DROP INDEX IDX_2BEC85AF198FDE2D');
        $this->addSql('ALTER TABLE patient_genetic_results ADD value VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE patient_genetic_results DROP marker_value_id');
    }
}
