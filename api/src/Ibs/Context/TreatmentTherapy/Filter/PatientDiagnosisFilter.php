<?php

declare(strict_types=1);

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Treatment;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class PatientDiagnosisFilter extends AbstractFilter
{
    public const DIAGNOSIS_FILTER_NAME = 'diagnosisCode';

    public function __construct(
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if ($property !== self::DIAGNOSIS_FILTER_NAME) {
            return;
        }

        $codes = is_array($value) ? $value : [$value];
        if (empty($codes)) {
            return;
        }

        $parameterName = $queryNameGenerator->generateParameterName('diagnosisCodes');
        $entityManager = $this->managerRegistry->getManager();

        $subQuery = $entityManager->createQueryBuilder()
            ->select('1')
            ->from(Treatment::class, 't_diag')
            ->innerJoin('t_diag.mkb10', 'm')
            ->where('t_diag.patient = o.id')
            ->andWhere('t_diag.begDt = (
                SELECT MAX(t2_diag.begDt)
                FROM App\Entity\Treatment t2_diag
                WHERE t2_diag.patient = o.id
            )')
            ->andWhere('t_diag.realEndDt IS NULL')
            ->andWhere('m.mkbCode IN (:' . $parameterName . ')');

        $queryBuilder->andWhere($queryBuilder->expr()->exists($subQuery->getDQL()))
            ->setParameter($parameterName, $codes);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            self::DIAGNOSIS_FILTER_NAME => [
                'property' => self::DIAGNOSIS_FILTER_NAME,
                'type' => 'array',
                'required' => false,
                'description' => 'Фильтр пациентов по коду диагноза (активное лечение)',
            ],
        ];
    }
}