<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423121306AlterPaitentsDropNotNull extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop not null for second name';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patients ALTER second_name DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patients ALTER second_name SET NOT NULL');
    }
}
