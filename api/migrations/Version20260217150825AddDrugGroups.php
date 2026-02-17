<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217150825AddDrugGroups extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add drug groups and assign each drug to a group';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE drug_groups (
            id SERIAL PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            full_name VARCHAR(255) DEFAULT NULL
        )');

        $this->addSql("INSERT INTO drug_groups (name, full_name) VALUES
            ('АВК', 'Антагонисты витамина К'),
            ('ПОАК', 'Прямые оральные антикоагулянты'),
            ('Гепарины', 'Гепарины (НФГ и НМГ)'),
            ('Антиагреганты', 'Антиагреганты')");

        $this->addSql('ALTER TABLE drugs ADD COLUMN group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE drugs ADD CONSTRAINT fk_drugs_group FOREIGN KEY (group_id) REFERENCES drug_groups (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_drugs_group ON drugs (group_id)');

        $this->addSql("SELECT setval('drugs_id_seq', COALESCE((SELECT MAX(id) FROM drugs), 0) + 1, false)");
        $this->addSql("
            INSERT INTO drugs (nominative, genitive)
            SELECT 'дабигатран', 'дабигатрана'
            WHERE NOT EXISTS (SELECT 1 FROM drugs WHERE nominative = 'дабигатран')
        ");
        $this->addSql("
            INSERT INTO drugs (nominative, genitive)
            SELECT 'ривароксабан', 'ривароксабана'
            WHERE NOT EXISTS (SELECT 1 FROM drugs WHERE nominative = 'ривароксабан')
        ");
        $this->addSql("
            INSERT INTO drugs (nominative, genitive)
            SELECT 'апиксабан', 'апиксабана'
            WHERE NOT EXISTS (SELECT 1 FROM drugs WHERE nominative = 'апиксабан')
        ");
        $this->addSql("
            INSERT INTO drugs (nominative, genitive)
            SELECT 'эдоксабан', 'эдоксабана'
            WHERE NOT EXISTS (SELECT 1 FROM drugs WHERE nominative = 'эдоксабан')
        ");
        $this->addSql("
            INSERT INTO drugs (nominative, genitive)
            SELECT 'НМГ', 'НМГ'
            WHERE NOT EXISTS (SELECT 1 FROM drugs WHERE nominative = 'НМГ')
        ");
        $this->addSql("
            INSERT INTO drugs (nominative, genitive)
            SELECT 'НФГ', 'НФГ'
            WHERE NOT EXISTS (SELECT 1 FROM drugs WHERE nominative = 'НФГ')
        ");
        $this->addSql("
            INSERT INTO drugs (nominative, genitive)
            SELECT 'аспирин', 'аспирина'
            WHERE NOT EXISTS (SELECT 1 FROM drugs WHERE nominative = 'аспирин')
        ");
        $this->addSql("
            INSERT INTO drugs (nominative, genitive)
            SELECT 'клопидогрел', 'клопидогрела'
            WHERE NOT EXISTS (SELECT 1 FROM drugs WHERE nominative = 'клопидогрел')
        ");
        $this->addSql("
            INSERT INTO drugs (nominative, genitive)
            SELECT 'тикагрелор', 'тикагрелора'
            WHERE NOT EXISTS (SELECT 1 FROM drugs WHERE nominative = 'тикагрелор')
        ");

        $this->addSql("
            UPDATE drugs SET group_id = (SELECT id FROM drug_groups WHERE name = 'АВК')
            WHERE nominative IN ('варфарин', 'фенилин')
        ");
        $this->addSql("
            UPDATE drugs SET group_id = (SELECT id FROM drug_groups WHERE name = 'ПОАК')
            WHERE nominative IN ('дабигатран', 'ривароксабан', 'апиксабан', 'эдоксабан')
        ");
        $this->addSql("
            UPDATE drugs SET group_id = (SELECT id FROM drug_groups WHERE name = 'Гепарины')
            WHERE nominative IN ('НМГ', 'НФГ')
        ");
        $this->addSql("
            UPDATE drugs SET group_id = (SELECT id FROM drug_groups WHERE name = 'Антиагреганты')
            WHERE nominative IN ('аспирин', 'клопидогрел', 'тикагрелор')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE drug_group_members');
        $this->addSql('DROP TABLE drug_groups');
    }
}
