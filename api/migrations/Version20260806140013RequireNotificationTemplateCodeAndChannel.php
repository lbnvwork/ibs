<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Makes NotificationTemplate's natural key (code, channel) required, and
 * fixes messenger_messages' datetime columns to carry Doctrine's
 * datetime_immutable type comment (matches the Doctrine transport's own
 * schema definition, picked up by the same diff run).
 */
final class Version20260806140013RequireNotificationTemplateCodeAndChannel extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'notification_templates.code/channel NOT NULL; fix messenger_messages datetime column type comments.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification_templates ALTER code SET NOT NULL');
        $this->addSql('ALTER TABLE notification_templates ALTER channel SET NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE messenger_messages ALTER available_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE messenger_messages ALTER delivered_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification_templates ALTER code DROP NOT NULL');
        $this->addSql('ALTER TABLE notification_templates ALTER channel DROP NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE messenger_messages ALTER available_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE messenger_messages ALTER delivered_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS NULL');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS NULL');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS NULL');
    }
}
