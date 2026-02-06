<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206132529FixTestHistoryTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix Test History Table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE test_history ADD doze DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE test_history ADD doze2 INT DEFAULT -1 NOT NULL');
        $this->addSql('ALTER TABLE test_history ADD comment TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE test_history ALTER mno SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE test_history DROP doze');
        $this->addSql('ALTER TABLE test_history DROP doze2');
        $this->addSql('ALTER TABLE test_history DROP comment');
        $this->addSql('ALTER TABLE test_history ALTER mno DROP NOT NULL');
    }
}
