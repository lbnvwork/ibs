<?php

declare(strict_types=1);

namespace Ibs\Shared\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\SecurityIdentity\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Загружает демо-данные (задача 3.77): справочники (fixtures/reference) +
 * демо-сущности (fixtures/demo) + хэширует пароли пользователей.
 *
 * Идемпотентна: demo/*.sql используют INSERT … ON CONFLICT DO NOTHING.
 */
#[AsCommand(
    name: 'app:fixtures:load-demo',
    description: 'Загружает справочники + демо-данные (3.77) и хэширует пароли.',
)]
final class LoadDemoFixturesCommand extends Command
{
    private const REFERENCE_DIR = 'fixtures/reference';
    private const DEMO_DIR = 'fixtures/demo';

    /**
     * Порядок справочников: родительские таблицы раньше дочерних (внешние ключи).
     */
    private const REFERENCE_ORDER = [
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
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Справочники:</info>');
        $this->loadDir($output, self::REFERENCE_DIR, self::REFERENCE_ORDER, true);

        // reference/*.sql (pg_dump) выставляют search_path=''; возвращаем public,
        // чтобы demo/*.sql (без явной схемы) грузились в нужную схему.
        $this->connection->executeStatement("SELECT pg_catalog.set_config('search_path', 'public', false)");

        // Связи маркер↔препарат (marker_drug_relations) — команда 3.47, идемпотентна.
        $seed = $this->getApplication()?->find('app:seed-genetic-markers');
        if (null !== $seed) {
            $output->writeln('<info>Маркеры/связи (seed-genetic-markers):</info>');
            $seed->run(new ArrayInput([]), $output);
        }

        $output->writeln('<info>Демо-данные:</info>');
        $this->loadDir($output, self::DEMO_DIR, null, false);

        $this->hashPasswords($output);
        $output->writeln('<info>Демо-данные загружены.</info>');

        return Command::SUCCESS;
    }

    /** @param list<string>|null $order */
    private function loadDir(OutputInterface $output, string $dir, ?array $order, bool $upsert): void
    {
        $abs = rtrim($this->projectDir, '/') . '/' . $dir;
        if (!is_dir($abs)) {
            $output->writeln(sprintf('<error>Каталог не найден: %s</error>', $abs));

            return;
        }

        $files = null === $order ? $this->sorted($abs) : $this->ordered($abs, $order);
        foreach ($files as $file) {
            $sql = file_get_contents($file);
            if (false === $sql) {
                $output->writeln(sprintf('<error>Не удалось прочитать %s</error>', basename($file)));
                continue;
            }

            if ($upsert) {
                $sql = $this->toUpsert($sql);
            }

            $this->connection->executeStatement($sql);
            $output->writeln(sprintf('  <info>%s</info>', basename($file)));
        }
    }

    /**
     * Преобразует INSERT-строки (pg_dump) в идемпотентные
     * INSERT … ON CONFLICT (id) DO NOTHING — для повторной загрузки справочников.
     */
    private function toUpsert(string $sql): string
    {
        $result = preg_replace_callback(
            '/^INSERT INTO public\.(\w+) \(([^)]*)\) VALUES (.*);$/m',
            static function (array $matches): string {
                return sprintf(
                    'INSERT INTO public.%s (%s) VALUES %s ON CONFLICT (id) DO NOTHING;',
                    $matches[1],
                    $matches[2],
                    $matches[3],
                );
            },
            $sql,
        );

        return null === $result ? $sql : $result;
    }

    /**
     * @param list<string> $order
     * @return list<string>
     */
    private function ordered(string $dir, array $order): array
    {
        $files = [];
        foreach ($order as $table) {
            $file = $dir . '/' . $table . '.sql';
            if (is_file($file)) {
                $files[] = $file;
            }
        }

        $known = array_map(static fn (string $f): string => basename($f), $files);
        foreach ($this->sorted($dir) as $file) {
            if (!in_array(basename($file), $known, true)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /** @return list<string> */
    private function sorted(string $dir): array
    {
        $files = glob($dir . '/*.sql') ?: [];
        sort($files);

        return $files;
    }

    private function hashPasswords(OutputInterface $output): void
    {
        $users = $this->em->getRepository(User::class)->findAll();
        $count = 0;
        foreach ($users as $user) {
            $plain = $user->getPassword();
            if ($plain && !$this->isAlreadyHashed($plain)) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $plain));
                ++$count;
            }
        }

        if ($count > 0) {
            $this->em->flush();
            $output->writeln(sprintf('<info>Хэшировано паролей: %d</info>', $count));
        }
    }

    private function isAlreadyHashed(string $password): bool
    {
        return str_starts_with($password, '$2y$')
            || str_starts_with($password, '$2a$')
            || str_starts_with($password, '$argon2');
    }
}
