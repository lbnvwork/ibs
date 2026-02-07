<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260204130857ChangePhoneNumberTypesToVarchar extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change phone number fields from integer to varchar(11) and fix text field type';
    }

    public function up(Schema $schema): void
    {
        // 1. Исправляем sms_out.sms_target (сейчас INTEGER в первой миграции, возможно уже TEXT в последней)
        $this->addSql('ALTER TABLE sms_out ALTER COLUMN sms_target TYPE VARCHAR(11)');

        // 2. Исправляем sms_out.sms_source (сейчас TEXT) на VARCHAR(11)
        $this->addSql('ALTER TABLE sms_out ALTER COLUMN sms_source TYPE VARCHAR(11)');

        // 3. Исправляем sms_in.sms_source (сейчас INTEGER) на VARCHAR(11)
        $this->addSql('ALTER TABLE sms_in ALTER COLUMN sms_source TYPE VARCHAR(11) USING sms_source::text');

        // 4. Исправляем sms_in.num (сейчас INTEGER) на VARCHAR(11)
        $this->addSql('ALTER TABLE sms_in ALTER COLUMN num TYPE VARCHAR(11) USING num::text');

        // 5. Исправляем sms_in.text (сейчас INTEGER) на TEXT для хранения текста СМС
        $this->addSql('ALTER TABLE sms_in ALTER COLUMN text TYPE TEXT USING text::text');
    }

    public function down(Schema $schema): void
    {
        // Откатываем изменения
        $this->addSql('ALTER TABLE sms_in ALTER COLUMN text TYPE INTEGER USING text::integer');
        $this->addSql('ALTER TABLE sms_in ALTER COLUMN num TYPE INTEGER USING num::integer');
        $this->addSql('ALTER TABLE sms_in ALTER COLUMN sms_source TYPE INTEGER USING sms_source::integer');
        $this->addSql('ALTER TABLE sms_out ALTER COLUMN sms_source TYPE TEXT');

        // Для sms_target - возвращаем к исходному типу (был INTEGER в первой миграции)
        $this->addSql('ALTER TABLE sms_out ALTER COLUMN sms_target TYPE INTEGER USING sms_target::integer');
    }

    public function isTransactional(): bool
    {
        return false; // Отключаем транзакцию для безопасного изменения типов
    }
}
