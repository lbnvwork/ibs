<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Command;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Ibs\Context\Communication\Repository\PatientChannelIdentityRepository;
use Ibs\Context\Communication\Service\MaxUpdatePoller;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
        private readonly PatientChannelIdentityRepository $identities,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->poller->isAvailable()) {
            $output->writeln('<error>MAX не настроен: задайте MAX_API_URL и MAX_BOT_TOKEN.</error>');

            return Command::FAILURE;
        }

        $marker = $this->readMarker();
        $result = $this->poller->fetch($marker, types: ['bot_started'], limit: 100, timeout: 1);

        $saved = 0;
        foreach ($result['updates'] as $update) {
            if (!\is_array($update)) {
                continue;
            }

            $chatId = $update['chat_id'] ?? null;
            $payload = $update['payload'] ?? null;

            if (!\is_string($chatId) && !\is_int($chatId)) {
                continue;
            }
            $chatId = (string) $chatId;

            if (!\is_string($payload) || !\is_numeric($payload)) {
                continue;
            }

            $this->upsertContact((int) $payload, $chatId);
            $saved++;
            $output->writeln(sprintf('Сохранён контакт: patient_id=%s, chat_id=%s', $payload, $chatId));
        }

        if ($saved > 0) {
            $this->entityManager->flush();
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

    private function upsertContact(int $patientId, string $chatId): void
    {
        $identity = $this->identities->findOneByPatientAndChannel($patientId, 'max');

        if (null === $identity) {
            $identity = (new PatientChannelIdentity())
                ->setPatientId($patientId)
                ->setChannelType('max')
                ->setValue($chatId);
            $this->entityManager->persist($identity);

            return;
        }

        $identity->setValue($chatId);
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
