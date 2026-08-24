<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Command;

use Ibs\Context\Communication\Service\MaxWebhookClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Регистрирует Webhook-подписку MAX (POST /subscriptions).
 *
 * Запускается на деплое, когда сервис доступен по публичному HTTPS-URL.
 * До этого Webhook не активируется.
 */
#[AsCommand(
    name: 'app:communication:max-webhook-subscribe',
    description: 'Регистрирует Webhook-подписку MAX (запуск на деплое).',
)]
final class MaxWebhookSubscribeCommand extends Command
{
    public function __construct(
        private readonly MaxWebhookClient $client,
        private readonly string $webhookSecret,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('url', InputArgument::REQUIRED, 'Публичный HTTPS-URL вебхука (например https://example.com/api/max/webhook).')
            ->addOption('update-types', null, InputOption::VALUE_REQUIRED, 'Типы событий через запятую.', 'bot_started,message_created');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $urlValue = $input->getArgument('url');
        $url = \is_string($urlValue) ? trim($urlValue) : '';

        if ('' === $url) {
            $output->writeln('<error>Аргумент url не должен быть пустым.</error>');

            return Command::INVALID;
        }

        $typesValue = $input->getOption('update-types');
        $updateTypes = [];
        if (\is_string($typesValue)) {
            foreach (explode(',', $typesValue) as $type) {
                $type = trim($type);
                if ('' !== $type) {
                    $updateTypes[] = $type;
                }
            }
        }

        $result = $this->client->subscribe($url, $updateTypes, $this->webhookSecret);

        $output->writeln((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $success = ($result['success'] ?? false) === true;

        return $success ? Command::SUCCESS : Command::FAILURE;
    }
}
