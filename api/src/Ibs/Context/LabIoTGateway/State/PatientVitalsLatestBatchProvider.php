<?php
declare(strict_types=1);

namespace Ibs\Context\LabIoTGateway\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Ibs\Context\LabIoTGateway\Entity\PatientVitalsLatest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/** @implements ProviderInterface<PatientVitalsLatest> */
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
                $patientIds = array_map(static fn (mixed $id): int => (int) (is_scalar($id) ? $id : 0), $patientIds);
                $qb->where($qb->expr()->in('v.patient', ':ids'))
                   ->setParameter('ids', $patientIds);
            }
        }

        /** @var list<PatientVitalsLatest> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }
}