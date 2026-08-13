<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Storage table for the Doctrine Messenger transport (async + failed queues,
 * distinguished by queue_name) used to actually queue ROUTINE notifications
 * instead of handling them inline. Schema matches Symfony's own
 * messenger:setup-transports output for a doctrine:// DSN.
 */
final class Version20260806133732CreateMessengerMessagesTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create messenger_messages table for the Doctrine Messenger async/failed transports.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_75ea56e0fb7336f0e3bd61ce16ba31dbbf396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE messenger_messages');
    }
}
