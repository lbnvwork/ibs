<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206105022FixSmsOutPacketsTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix sms_out_packets table';
    }

    public function up(Schema $schema): void
    {
        // 1. Добавляем столбец server_code (TEXT, nullable)
        $this->addSql('ALTER TABLE sms_out_packets ADD COLUMN IF NOT EXISTS server_code TEXT DEFAULT NULL');

        // 2. Устанавливаем NOT NULL для server_packet_id
        // Сначала заполняем NULL значения (если есть)
        $this->addSql('UPDATE sms_out_packets SET server_packet_id = 0 WHERE server_packet_id IS NULL');

        // Затем устанавливаем NOT NULL
        $this->addSql('ALTER TABLE sms_out_packets ALTER COLUMN server_packet_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Убираем NOT NULL
        $this->addSql('ALTER TABLE sms_out_packets ALTER COLUMN server_packet_id DROP NOT NULL');

        // Удаляем столбец server_code
        $this->addSql('ALTER TABLE sms_out_packets DROP COLUMN IF EXISTS server_code');
    }
}
