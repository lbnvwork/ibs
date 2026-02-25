<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223131639UpdateUserRolesToJson extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert roles field to JSON and prepare for role-based auth';
    }

    public function up(Schema $schema): void
    {
        // 1. Добавляем поле medical_personnel_id
        $this->addSql('ALTER TABLE users ADD COLUMN medical_personnel_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_USERS_MEDPERS FOREIGN KEY (medical_personnel_id) REFERENCES medical_personnel (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_users_medpers ON users (medical_personnel_id)');

        // 2. Создаём временную колонку для JSON-ролей
        $this->addSql('ALTER TABLE users ADD COLUMN roles_json JSONB DEFAULT \'[]\'');

        // 3. Преобразуем роли у существующих пользователей (не из med_personnel_users)
        $this->addSql('
            UPDATE users SET roles_json = 
                CASE 
                    WHEN roles = 2 THEN \'["ROLE_ADMIN"]\'::jsonb
                    ELSE \'["ROLE_DOCTOR"]\'::jsonb
                END
            WHERE id NOT IN (SELECT id FROM med_personnel_users)
        ');

        // 4. Вставляем данные из med_personnel_users в users (без mod_dt)
        $this->addSql('
            INSERT INTO users (id, login, password, user_name, roles_json, medical_personnel_id)
            SELECT 
                mp.id,
                mp.login,
                mp.password,
                (SELECT name FROM medical_personnel WHERE id = mp.medical_personnel_id),
                \'["ROLE_DOCTOR_ASSISTANT"]\'::jsonb,
                mp.medical_personnel_id
            FROM med_personnel_users mp
            ON CONFLICT (id) DO UPDATE SET
                login = EXCLUDED.login,
                password = EXCLUDED.password,
                user_name = EXCLUDED.user_name,
                roles_json = EXCLUDED.roles_json,
                medical_personnel_id = EXCLUDED.medical_personnel_id
        ');

        // 5. Уточняем роли на основе должности (post) из medical_personnel
        // Для заведующих лабораториями (оставляем ROLE_DOCTOR_ASSISTANT, но можно повысить)
        $this->addSql('
            UPDATE users u
            SET roles_json = \'["ROLE_DOCTOR_ASSISTANT"]\'::jsonb
            FROM medical_personnel mp
            WHERE u.medical_personnel_id = mp.id
              AND mp.post ILIKE \'%зав%лаборатори%\' 
        ');

        // Для главных врачей и начмедов – ROLE_DOCTOR_ADMIN
        $this->addSql('
            UPDATE users u
            SET roles_json = \'["ROLE_DOCTOR_ADMIN"]\'::jsonb
            FROM medical_personnel mp
            WHERE u.medical_personnel_id = mp.id
              AND (mp.post ILIKE \'%начмед%\' 
                   OR mp.post ILIKE \'%главный врач%\'
                   OR mp.post ILIKE \'%зав. поликлиникой%\')
        ');

        // 6. Удаляем старое поле roles и переименовываем roles_json в roles
        $this->addSql('ALTER TABLE users DROP COLUMN roles');
        $this->addSql('ALTER TABLE users RENAME COLUMN roles_json TO roles');

        // 7. Обновляем последовательность id
        $this->addSql('SELECT setval(\'users_id_seq\', (SELECT MAX(id) FROM users))');

        // 8. Удаляем таблицу med_personnel_users
        $this->addSql('DROP TABLE med_personnel_users CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Восстановление (упрощённо, для разработки)
        $this->addSql('CREATE TABLE med_personnel_users (
            id INT PRIMARY KEY,
            mod_dt TIMESTAMP DEFAULT NULL,
            login TEXT,
            password TEXT,
            roles INT,
            medical_personnel_id INT,
            CONSTRAINT fk_mpu_medical FOREIGN KEY (medical_personnel_id) REFERENCES medical_personnel (id)
        )');
        // Переносим обратно тех, у кого есть medical_personnel_id
        $this->addSql('
            INSERT INTO med_personnel_users (id, mod_dt, login, password, roles, medical_personnel_id)
            SELECT id, NULL, login, password, 1, medical_personnel_id
            FROM users WHERE medical_personnel_id IS NOT NULL
        ');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_USERS_MEDPERS');
        $this->addSql('DROP INDEX idx_users_medpers');
        $this->addSql('ALTER TABLE users DROP COLUMN medical_personnel_id');
        // Возвращаем roles в int
        $this->addSql('ALTER TABLE users ADD COLUMN roles_int INT DEFAULT 0');
        $this->addSql('UPDATE users SET roles_int = 
            CASE 
                WHEN roles ? \'ROLE_ADMIN\' THEN 2
                ELSE 1
            END');
        $this->addSql('ALTER TABLE users DROP COLUMN roles');
        $this->addSql('ALTER TABLE users RENAME COLUMN roles_int TO roles');
    }
}