<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Command;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Model\Priority;
use Ibs\Context\Communication\Model\Recipient;
use Ibs\Context\Communication\Repository\PatientChannelIdentityRepository;
use Ibs\Context\Communication\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Отправляет тестовое MAX-уведомление через полный пайплайн NotificationService,
 * чтобы проверить реальную интеграцию с мессенджером MAX (URL, токен, чат).
 *
 * Команда использует приоритет IMMEDIATE — отправка выполняется синхронно,
 * поэтому результат (успех/ошибка) виден сразу в выводе команды.
 */
#[AsCommand(
    name: 'app:communication:test-max',
    description: 'Отправляет тестовое MAX-уведомление через полный пайплайн NotificationService (проверка реальной интеграции с MAX).',
)]
final class TestMaxNotificationCommand extends Command
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly PatientChannelIdentityRepository $identities,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('patient_id', InputArgument::REQUIRED, 'ID пациента (например 999999).')
            ->addArgument('chat_id', InputArgument::REQUIRED, 'MAX chat_id получателя.')
            ->addArgument('message', InputArgument::OPTIONAL, 'Текст сообщения.', 'Тестовое сообщение из IBS');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $patientIdValue = $input->getArgument('patient_id');
        $chatIdValue = $input->getArgument('chat_id');
        $messageValue = $input->getArgument('message');

        if (!\is_numeric($patientIdValue)) {
            $output->writeln('<error>Аргумент patient_id должен быть целым числом.</error>');

            return Command::INVALID;
        }

        $patientId = (int) $patientIdValue;
        $chatId = \is_string($chatIdValue) ? $chatIdValue : '';
        $message = \is_string($messageValue) && '' !== $messageValue ? $messageValue : 'Тестовое сообщение из IBS';

        if ($patientId < 1) {
            $output->writeln('<error>Аргумент patient_id должен быть положительным целым числом.</error>');

            return Command::INVALID;
        }

        if ('' === $chatId) {
            $output->writeln('<error>Аргумент chat_id не должен быть пустым.</error>');

            return Command::INVALID;
        }

        $this->ensureMaxContact($patientId, $chatId);

        $output->writeln('<info>Отправка тестового MAX-уведомления:</info>');
        $output->writeln(sprintf('  patient_id: %d', $patientId));
        $output->writeln(sprintf('  chat_id:    %s', $chatId));
        $output->writeln(sprintf('  priority:   %s', Priority::IMMEDIATE->value));
        $output->writeln(sprintf('  message:    %s', $message));

        try {
            $this->notificationService->send(
                new Recipient(patientId: $patientId),
                new NotificationMessage(body: $message),
                ['max'],
                Priority::IMMEDIATE,
            );
        } catch (\Throwable $exception) {
            $output->writeln(sprintf('<error>Отправка завершилась с ошибкой: %s</error>', $exception->getMessage()));
            $this->printLatestLog($output, $patientId);

            return Command::FAILURE;
        }

        $this->printLatestLog($output, $patientId);

        $logs = $this->notificationService->getHistoryForPatient($patientId);
        $log = $logs[0] ?? null;

        if (null === $log) {
            $output->writeln('<error>Запись в NotificationLog не найдена.</error>');

            return Command::FAILURE;
        }

        return \in_array($log->getStatus(), ['sent', 'delivered', 'read'], true)
            ? Command::SUCCESS
            : Command::FAILURE;
    }

    /**
     * Создаёт запись PatientChannelIdentity для канала 'max', если её ещё нет.
     */
    private function ensureMaxContact(int $patientId, string $chatId): void
    {
        $identity = $this->identities->findOneByPatientAndChannel($patientId, 'max');
        if (null !== $identity) {
            return;
        }

        $identity = (new PatientChannelIdentity())
            ->setPatientId($patientId)
            ->setChannelType('max')
            ->setValue($chatId);

        $this->entityManager->persist($identity);
        $this->entityManager->flush();
    }

    private function printLatestLog(OutputInterface $output, int $patientId): void
    {
        $logs = $this->notificationService->getHistoryForPatient($patientId);
        $log = $logs[0] ?? null;

        if (null === $log) {
            $output->writeln(sprintf('<comment>В NotificationLog нет записей для пациента %d.</comment>', $patientId));

            return;
        }

        $output->writeln('<info>Последняя запись NotificationLog:</info>');
        $output->writeln(sprintf('  status:      %s', $log->getStatus()));
        $output->writeln(sprintf('  channel:     %s', $log->getChannelType()));
        $output->writeln(sprintf('  external_id: %s', $log->getExternalId() ?? '-'));
        $output->writeln(sprintf('  address:     %s', $log->getRecipientAddress() ?? '-'));

        if (null !== $log->getErrorMessage()) {
            $output->writeln(sprintf('  error:       %s', $log->getErrorMessage()));
        }
    }
}
