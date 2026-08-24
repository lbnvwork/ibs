<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Service\MaxDeepLinkGenerator;
use Ibs\Context\PatientManagement\Entity\Patient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Отдаёт врачу готовый диплинк MAX для пациента (для отправки пациенту).
 */
#[AsController]
final class MaxDeepLinkController
{
    public function __construct(
        private readonly MaxDeepLinkGenerator $generator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/patients/{id}/max-deeplink', name: 'max_deeplink', methods: ['GET'])]
    public function __invoke(int $id): JsonResponse
    {
        $patient = $this->entityManager->getRepository(Patient::class)->find($id);
        if (null === $patient) {
            return new JsonResponse(['error' => 'Patient not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['url' => $this->generator->forPatient($id)]);
    }
}
