<?php
declare(strict_types=1);

namespace Ibs\Context\LabIoTGateway\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Ibs\Context\LabIoTGateway\Entity\PatientVitalsLatest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class PatientVitalsLatestBatchProvider implements ProviderInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $request = $context['request'] ?? null;
        $qb = $this->em->getRepository(PatientVitalsLatest::class)->createQueryBuilder('v');

        if ($request instanceof Request) {
            $patientIds = $request->query->all('patient_id');
            if (!empty($patientIds)) {
                // Приводим значения к целым числам
                $patientIds = array_map('intval', $patientIds);
                $qb->where($qb->expr()->in('v.patient', ':ids'))
                   ->setParameter('ids', $patientIds);
            }
        }

        return $qb->getQuery()->getResult();
    }
}