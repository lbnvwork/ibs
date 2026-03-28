<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328104255AlterMkb10 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alter mkb10 table: set id as NOT NULL, change mkb_name to TEXT, and add primary key on id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mkb10 ALTER id SET NOT NULL');
        $this->addSql('ALTER TABLE mkb10 ALTER mkb_name TYPE TEXT');
        $this->addSql('ALTER TABLE mkb10 ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mkb10 DROP CONSTRAINT mkb10_pkey');
        $this->addSql('ALTER TABLE mkb10 ALTER id DROP NOT NULL');
        $this->addSql('ALTER TABLE mkb10 ALTER mkb_name TYPE VARCHAR(255)');
    }
}
