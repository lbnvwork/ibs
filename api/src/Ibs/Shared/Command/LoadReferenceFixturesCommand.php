<?php

declare(strict_types=1);

namespace Ibs\Shared\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Загружает SQL-артефакты справочников (api/fixtures/reference/*.sql) в БД.
 *
 * Артефакты готовит задача 3.33 (pg_dump --data-only --column-inserts из копии
 * боевой PostgreSQL). Порядок загрузки важен: родительские таблицы (drug_groups,
 * genetic_markers) должны быть загружены раньше дочерних (drugs, genetic_marker_values),
 * иначе PostgreSQL отклонит INSERT по внешнему ключу.
 */
#[AsCommand(
    name: 'app:fixtures:load-reference',
    description: 'Загружает SQL-артефакты справочников из api/fixtures/reference/*.sql в БД.',
)]
final class LoadReferenceFixturesCommand extends Command
{
    private const DEFAULT_DIR = 'fixtures/reference';

    /**
     * Порядок загрузки: родительские таблицы раньше дочерних (внешние ключи).
     * Файлы, которых нет в этом списке (если появятся), догружаются в конце
     * в алфавитном порядке.
     */
    private const LOAD_ORDER = [
        'drug_groups',
        'drugs',
        'genetic_markers',
        'genetic_marker_values',
        'mkb10',
        'sms_templates',
        'phone_types',
    ];

    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Каталог с SQL-артефактами (относительно корня проекта).',
            self::DEFAULT_DIR,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = rtrim($this->projectDir, '/') . '/' . trim((string) $input->getOption('dir'), '/');

        if (!is_dir($dir)) {
            $output->writeln(sprintf('<error>Каталог фикстур не найден: %s</error>', $dir));

            return Command::FAILURE;
        }

        $files = $this->resolveFiles($dir);
        if ([] === $files) {
            $output->writeln('<error>В каталоге нет SQL-файлов.</error>');

            return Command::FAILURE;
        }

        foreach ($files as $file) {
            $sql = file_get_contents($file);
            if (false === $sql) {
                $output->writeln(sprintf('<error>Не удалось прочитать: %s</error>', $file));

                return Command::FAILURE;
            }

            $this->connection->beginTransaction();
            try {
                $this->connection->executeStatement($sql);
                $this->connection->commit();
            } catch (\Throwable $e) {
                $this->connection->rollBack();
                $output->writeln(sprintf('<error>Ошибка загрузки %s: %s</error>', basename($file), $e->getMessage()));

                return Command::FAILURE;
            }

            $output->writeln(sprintf('  <info>%s</info>', basename($file)));
        }

        $output->writeln(sprintf('<info>Справочники загружены: %d файл(ов).</info>', count($files)));

        return Command::SUCCESS;
    }

    /**
     * @return list<string> абсолютные пути к SQL-файлам в порядке загрузки
     */
    private function resolveFiles(string $dir): array
    {
        $ordered = [];
        foreach (self::LOAD_ORDER as $table) {
            $file = $dir . '/' . $table . '.sql';
            if (is_file($file)) {
                $ordered[] = $file;
            }
        }

        $known = array_map(static fn (string $file): string => basename($file), $ordered);
        $rest = glob($dir . '/*.sql') ?: [];
        sort($rest);
        foreach ($rest as $file) {
            if (!\in_array(basename($file), $known, true)) {
                $ordered[] = $file;
            }
        }

        return $ordered;
    }
}
