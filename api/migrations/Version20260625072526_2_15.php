<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260625072526_2_15 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Задача 2.15: замена диплотипа CYP2C9 на отдельные SNP (CYP2C9*2 и CYP2C9*3), удаление полей test_date и comment, обновление VKORC1.';
    }

    public function up(Schema $schema): void
    {
        // ======= 1. Удаление полей test_date и comment (с проверкой существования) =======
        $table = $schema->getTable('patient_genetic_results');
        if ($table->hasColumn('test_date')) {
            $this->addSql('ALTER TABLE patient_genetic_results DROP COLUMN test_date');
        }
        if ($table->hasColumn('comment')) {
            $this->addSql('ALTER TABLE patient_genetic_results DROP COLUMN comment');
        }

        // ======= 2. Удаление старого маркера CYP2C9 и всех связанных данных =======
        $oldMarkerId = $this->connection->fetchOne(
            "SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9'"
        );

        if ($oldMarkerId) {
            // 2a. Удаляем patient_genetic_results, где marker_value_id принадлежит значениям удаляемого маркера
            $this->addSql(
                'DELETE FROM patient_genetic_results WHERE marker_value_id IN (
                    SELECT id FROM genetic_marker_values WHERE marker_id = :markerId
                )',
                ['markerId' => $oldMarkerId]
            );
            // 2b. Удаляем patient_genetic_results по прямому marker_id (на случай, если остались)
            $this->addSql('DELETE FROM patient_genetic_results WHERE marker_id = :markerId', [
                'markerId' => $oldMarkerId,
            ]);
            // 2c. Удаляем связи с препаратами
            $this->addSql('DELETE FROM marker_drug_relations WHERE marker_id = :markerId', [
                'markerId' => $oldMarkerId,
            ]);
            // 2d. Удаляем возможные значения маркера
            $this->addSql('DELETE FROM genetic_marker_values WHERE marker_id = :markerId', [
                'markerId' => $oldMarkerId,
            ]);
            // 2e. Удаляем сам маркер
            $this->addSql('DELETE FROM genetic_markers WHERE id = :markerId', [
                'markerId' => $oldMarkerId,
            ]);
        }

        // ======= 3. Вставка новых маркеров CYP2C9_2 и CYP2C9_3 =======
        $warfarinId = $this->connection->fetchOne(
            "SELECT id FROM drugs WHERE nominative = 'Варфарин'"
        ) ?: 1;
        $fenilinId = $this->connection->fetchOne(
            "SELECT id FROM drugs WHERE nominative = 'Фенилин'"
        ) ?: 2;

        // --- Маркер CYP2C9_2 ---
        $this->addSql("INSERT INTO genetic_markers (gene_symbol, full_name, rs_id, description) 
            VALUES ('CYP2C9_2', 'Цитохром P450 2C9, аллель *2', 'rs1799853', 'Генотип CYP2C9*2')");

        $this->addSql("INSERT INTO genetic_marker_values (marker_id, value, label, description) VALUES 
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9_2'), 'CC', 'Норма', 'Нормальная активность'),
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9_2'), 'CT', 'Гетерозигота', 'Снижение активности'),
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9_2'), 'TT', 'Мутантная гомозигота', 'Значительное снижение активности')
        ");

        $this->addSql("INSERT INTO marker_drug_relations (marker_id, drug_id) VALUES 
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9_2'), $warfarinId),
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9_2'), $fenilinId)
        ");

        // --- Маркер CYP2C9_3 ---
        $this->addSql("INSERT INTO genetic_markers (gene_symbol, full_name, rs_id, description) 
            VALUES ('CYP2C9_3', 'Цитохром P450 2C9, аллель *3', 'rs1057910', 'Генотип CYP2C9*3')");

        $this->addSql("INSERT INTO genetic_marker_values (marker_id, value, label, description) VALUES 
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9_3'), 'AA', 'Норма', 'Нормальная активность'),
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9_3'), 'AC', 'Гетерозигота', 'Снижение активности'),
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9_3'), 'CC', 'Мутантная гомозигота', 'Значительное снижение активности')
        ");

        $this->addSql("INSERT INTO marker_drug_relations (marker_id, drug_id) VALUES 
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9_3'), $warfarinId),
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9_3'), $fenilinId)
        ");

        // ======= 4. Обновление VKORC1_3673 и VKORC1_3730 =======
        $vkorc3673Id = $this->connection->fetchOne(
            "SELECT id FROM genetic_markers WHERE gene_symbol = 'VKORC1_3673'"
        );
        if ($vkorc3673Id) {
            // Перед удалением значений VKORC1_3673 также удаляем связанные результаты пациентов
            $this->addSql(
                'DELETE FROM patient_genetic_results WHERE marker_value_id IN (
                    SELECT id FROM genetic_marker_values WHERE marker_id = :id
                )',
                ['id' => $vkorc3673Id]
            );
            $this->addSql('DELETE FROM genetic_marker_values WHERE marker_id = :id', ['id' => $vkorc3673Id]);
            $this->addSql("INSERT INTO genetic_marker_values (marker_id, value, label, description) VALUES 
                ($vkorc3673Id, 'GG', 'Норма', 'Нормальная чувствительность'),
                ($vkorc3673Id, 'GA', 'Гетерозигота', 'Умеренная чувствительность'),
                ($vkorc3673Id, 'AA', 'Мутантная гомозигота', 'Высокая чувствительность')
            ");
        }

        $vkorc3730Id = $this->connection->fetchOne(
            "SELECT id FROM genetic_markers WHERE gene_symbol = 'VKORC1_3730'"
        );
        if ($vkorc3730Id) {
            $this->addSql(
                'DELETE FROM patient_genetic_results WHERE marker_value_id IN (
                    SELECT id FROM genetic_marker_values WHERE marker_id = :id
                )',
                ['id' => $vkorc3730Id]
            );
            $this->addSql('DELETE FROM genetic_marker_values WHERE marker_id = :id', ['id' => $vkorc3730Id]);
            $this->addSql("INSERT INTO genetic_marker_values (marker_id, value, label, description) VALUES 
                ($vkorc3730Id, 'GG', 'Норма', 'Нормальная чувствительность'),
                ($vkorc3730Id, 'GA', 'Гетерозигота', 'Умеренная чувствительность'),
                ($vkorc3730Id, 'AA', 'Мутантная гомозигота', 'Высокая чувствительность')
            ");
        }
    }

    public function down(Schema $schema): void
    {
        // Возвращаем поля
        $this->addSql('ALTER TABLE patient_genetic_results ADD COLUMN test_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE patient_genetic_results ADD COLUMN comment TEXT DEFAULT NULL');

        // Удаляем новые маркеры CYP2C9_2 и CYP2C9_3 вместе с их зависимостями
        foreach (['CYP2C9_2', 'CYP2C9_3'] as $symbol) {
            $markerId = $this->connection->fetchOne(
                "SELECT id FROM genetic_markers WHERE gene_symbol = '$symbol'"
            );
            if ($markerId) {
                // Удаляем результаты пациентов через marker_value_id
                $this->addSql(
                    "DELETE FROM patient_genetic_results WHERE marker_value_id IN (
                        SELECT id FROM genetic_marker_values WHERE marker_id = $markerId
                    )"
                );
                $this->addSql("DELETE FROM patient_genetic_results WHERE marker_id = $markerId");
                $this->addSql("DELETE FROM marker_drug_relations WHERE marker_id = $markerId");
                $this->addSql("DELETE FROM genetic_marker_values WHERE marker_id = $markerId");
                $this->addSql("DELETE FROM genetic_markers WHERE id = $markerId");
            }
        }

        // Восстанавливаем старый маркер CYP2C9
        $this->addSql("INSERT INTO genetic_markers (gene_symbol, full_name, rs_id, description) 
            VALUES ('CYP2C9', 'Цитохром P450 2C9 (диплотип)', NULL, 'Интегральный диплотип')");

        $this->addSql("INSERT INTO genetic_marker_values (marker_id, value, label, description) VALUES 
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9'), '*1/*1', 'Нормальный метаболизатор', 'Нормальная активность'),
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9'), '*1/*2', 'Промежуточный метаболизатор', 'Сниженная активность'),
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9'), '*1/*3', 'Промежуточный метаболизатор', 'Сниженная активность'),
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9'), '*2/*2', 'Медленный метаболизатор', 'Значительно сниженная активность'),
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9'), '*2/*3', 'Медленный метаболизатор', 'Значительно сниженная активность'),
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9'), '*3/*3', 'Медленный метаболизатор', 'Значительно сниженная активность')
        ");

        $warfarinId = $this->connection->fetchOne("SELECT id FROM drugs WHERE nominative = 'Варфарин'") ?: 1;
        $fenilinId = $this->connection->fetchOne("SELECT id FROM drugs WHERE nominative = 'Фенилин'") ?: 2;
        $this->addSql("INSERT INTO marker_drug_relations (marker_id, drug_id) VALUES 
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9'), $warfarinId),
            ((SELECT id FROM genetic_markers WHERE gene_symbol = 'CYP2C9'), $fenilinId)
        ");
    }
}