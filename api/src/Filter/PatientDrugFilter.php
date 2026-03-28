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

final class PatientDrugFilter extends AbstractFilter
{
    public const DRUG_FILTER_NAME = 'drug';

    public function __construct(
        ManagerRegistry         $managerRegistry,
        ?LoggerInterface        $logger = null,
        ?array                  $properties = null,
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
        if ($property !== self::DRUG_FILTER_NAME) {
            return;
        }

        $parameterName = $queryNameGenerator->generateParameterName(self::DRUG_FILTER_NAME);

        $entityManager = $this->managerRegistry->getManager();

        $subQuery = $entityManager->createQueryBuilder()
            ->select('1')
            ->from(Treatment::class, 't')
            ->where('t.patient = o.id')
            ->andWhere('t.begDt = (
                SELECT MAX(t2.begDt)
                FROM App\Entity\Treatment t2
                WHERE t2.patient = o.id
            )')
            ->andWhere('t.drug = :' . $parameterName)
            ->andWhere('t.realEndDt IS NULL');

        $queryBuilder->andWhere($queryBuilder->expr()->exists($subQuery->getDQL()))
            ->setParameter($parameterName, $value);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            self::DRUG_FILTER_NAME => [
                'property' => self::DRUG_FILTER_NAME,
                'type' => 'int',
                'required' => false,
                'description' => 'Фильтр пациентов по конкретному препарату (текущее лечение)',
            ],
        ];
    }
}