<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Command;

use Ibs\Context\Communication\Service\MaxUpdatePoller;
use Ibs\Context\Communication\Service\MaxUpdateProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Опрашивает MAX GET /updates и автоматически сохраняет контакты пациентов.
 *
 * Сценарий: пациенту выдаётся диплинк `https://max.ru/<bot>?start=<patientId>`.
 * Когда пациент открывает бота, MAX присылает событие `bot_started` с
 * `payload = <patientId>` и `chat_id` диалога. Команда сохраняет
 * PatientChannelIdentity (channelType = 'max', value = chat_id).
 */
#[AsCommand(
    name: 'app:communication:collect-max-contacts',
    description: 'Опрашивает MAX GET /updates и сохраняет chat_id контактов пациентов (диплинк payload = patientId).',
)]
final class CollectMaxContactsCommand extends Command
{
    private const MARKER_FILE = 'var/max_updates_marker';

    public function __construct(
        private readonly MaxUpdatePoller $poller,
        private readonly MaxUpdateProcessor $processor,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Таймаут Long Polling (секунд).', 1)
            ->addOption('loop', null, InputOption::VALUE_NONE, 'Работать непрерывно (бесконечный цикл опроса).')
            ->addOption('max-iterations', null, InputOption::VALUE_REQUIRED, 'Максимум итераций в режиме --loop (0 = бесконечно).', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->poller->isAvailable()) {
            $output->writeln('<error>MAX не настроен: задайте MAX_API_URL и MAX_BOT_TOKEN.</error>');

            return Command::FAILURE;
        }

        $timeout = $this->resolveNonNegativeInt($input, $output, 'timeout');
        if (null === $timeout) {
            return Command::INVALID;
        }

        $maxIterations = $this->resolveNonNegativeInt($input, $output, 'max-iterations');
        if (null === $maxIterations) {
            return Command::INVALID;
        }

        $loop = (bool) $input->getOption('loop');

        $iterations = 0;
        $status = Command::SUCCESS;

        while (true) {
            $status = $this->pollOnce($output, $timeout);
            $iterations++;

            if (!$loop) {
                break;
            }

            if ($maxIterations > 0 && $iterations >= $maxIterations) {
                break;
            }

            // Пауза между опросами; сам GET /updates блокируется до timeout.
            usleep(1_000_000);
        }

        return $status;
    }

    private function pollOnce(OutputInterface $output, int $timeout): int
    {
        $marker = $this->readMarker();
        $result = $this->poller->fetch($marker, types: ['bot_started'], limit: 100, timeout: $timeout);

        $saved = 0;
        foreach ($result['updates'] as $update) {
            if (!\is_array($update)) {
                continue;
            }

            $contact = $this->processor->process($update);
            if (null !== $contact) {
                $saved++;
                $output->writeln(sprintf(
                    'Сохранён контакт: patient_id=%d, chat_id=%s',
                    $contact['patientId'],
                    $contact['chatId'],
                ));
            }
        }

        if ($saved > 0) {
            $this->processor->flush();
        }

        if (null !== $result['marker'] && '' !== $result['marker']) {
            $this->writeMarker($result['marker']);
        }

        $output->writeln(sprintf(
            '<info>Обработано обновлений: %d, сохранено контактов: %d.</info>',
            count($result['updates']),
            $saved,
        ));

        return Command::SUCCESS;
    }

    private function resolveNonNegativeInt(InputInterface $input, OutputInterface $output, string $name): ?int
    {
        $value = $input->getOption($name);

        if (!\is_numeric($value)) {
            $output->writeln(sprintf('<error>Опция --%s должна быть целым числом.</error>', $name));

            return null;
        }

        $number = (int) $value;
        if ($number < 0) {
            $output->writeln(sprintf('<error>Опция --%s должна быть неотрицательной.</error>', $name));

            return null;
        }

        return $number;
    }

    private function markerFile(): string
    {
        return rtrim($this->projectDir, '/') . '/' . self::MARKER_FILE;
    }

    private function readMarker(): ?string
    {
        $file = $this->markerFile();

        if (!is_file($file)) {
            return null;
        }

        $content = file_get_contents($file);
        $value = \is_string($content) ? trim($content) : '';

        return '' === $value ? null : $value;
    }

    private function writeMarker(string $marker): void
    {
        $dir = \dirname($this->markerFile());
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($this->markerFile(), $marker);
    }
}
