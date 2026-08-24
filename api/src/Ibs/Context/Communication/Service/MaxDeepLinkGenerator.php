<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Entity\MaxDeepLink;
use Ibs\Context\Communication\Repository\MaxDeepLinkRepository;

/**
 * Генерирует (или отдаёт существующий) защищённый диплинк MAX для пациента.
 */
final class MaxDeepLinkGenerator
{
    public function __construct(
        private readonly MaxDeepLinkRepository $deeplinks,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $botNickname,
    ) {
    }

    /**
     * Возвращает готовый диплинк `https://max.ru/<бот>?start=<token>`.
     */
    public function forPatient(int $patientId): string
    {
        $deeplink = $this->deeplinks->findByPatientId($patientId);
        if (null !== $deeplink) {
            return $this->buildUrl($deeplink->getToken());
        }

        try {
            $deeplink = new MaxDeepLink($patientId, $this->generateToken());
            $this->entityManager->persist($deeplink);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // Гонка: параллельный запрос уже создал диплинк для этого пациента
            // (unique patient_id). Возвращаем существующий вместо падения с 500.
            $this->entityManager->clear(MaxDeepLink::class);
            $deeplink = $this->deeplinks->findByPatientId($patientId);
            if (null === $deeplink) {
                throw new \RuntimeException(\sprintf('Не удалось создать диплинк для пациента %d.', $patientId));
            }
        }

        return $this->buildUrl($deeplink->getToken());
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function buildUrl(string $token): string
    {
        return 'https://max.ru/' . $this->botNickname . '?start=' . $token;
    }
}
