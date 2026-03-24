<?php

declare(strict_types=1);

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class ActiveTreatmentFilter extends AbstractFilter
{
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
        if ($property !== 'active') {
            return;
        }

        // Приводим значение к boolean (можно передать true, 1, 'true', '1')
        $active = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        if ($active) {
            $alias = $queryBuilder->getRootAliases()[0];
            $queryBuilder->andWhere("$alias.realEndDt IS NULL");
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'active' => [
                'property' => 'active',
                'type' => 'bool',
                'required' => false,
                'description' => 'Фильтр активных лечений (realEndDt IS NULL)',
            ],
        ];
    }
}