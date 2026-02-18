<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218100719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE treatment_code_generator_id_seq');
        $this->addSql('SELECT setval(\'treatment_code_generator_id_seq\', (SELECT MAX(id) FROM treatment_code_generator))');
        $this->addSql('ALTER TABLE treatment_code_generator ALTER id SET DEFAULT nextval(\'treatment_code_generator_id_seq\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE treatment_code_generator ALTER id DROP DEFAULT');
    }
}
