<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Ibs\Context\Communication\Repository\MaxDeepLinkRepository;
use Ibs\Context\Communication\Repository\PatientChannelIdentityRepository;

/**
 * Обрабатывает входящее событие/обновление MAX и сохраняет контакт пациента.
 *
 * Используется и командой Long Polling (app:communication:collect-max-contacts),
 * и Webhook-контроллером.
 */
final class MaxUpdateProcessor
{
    public function __construct(
        private readonly PatientChannelIdentityRepository $identities,
        private readonly MaxDeepLinkRepository $deeplinks,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Обрабатывает объект Update из MAX.
     *
     * Сохраняет/обновляет PatientChannelIdentity (channelType = 'max',
     * value = chat_id) для пациента из payload — токена диплинка
     * `?start=<token>`, который резолвится в patientId через
     * MaxDeepLinkRepository::findByToken().
     *
     * @param array<mixed, mixed> $update
     *
     * @return array{patientId: int, chatId: string}|null данные сохранённого
     *                                                   контакта, либо null,
     *                                                   если update пропущен
     */
    public function process(array $update): ?array
    {
        $chatId = $update['chat_id'] ?? null;
        $payload = $update['payload'] ?? null;

        if (!\is_string($chatId) && !\is_int($chatId)) {
            return null;
        }
        $chatId = (string) $chatId;

        // Не сохраняем пустой chat_id — иначе в patient_channel_identities попадёт пустой контакт.
        if ('' === trim($chatId)) {
            return null;
        }

        if (!\is_string($payload) || '' === $payload) {
            return null;
        }

        $deeplink = $this->deeplinks->findByToken($payload);
        if (null === $deeplink) {
            return null;
        }

        $patientId = $deeplink->getPatientId();
        $this->upsertContact($patientId, $chatId);

        return ['patientId' => $patientId, 'chatId' => $chatId];
    }

    public function flush(): void
    {
        $this->entityManager->flush();
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
}
