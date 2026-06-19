<?php

declare(strict_types=1);

namespace App\ClinicalCore\Pharmacogenetics\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\GeneticMarker;
use App\Entity\GeneticMarkerValue;
use App\Entity\PatientGeneticResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PatientGeneticResultProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire('@api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PatientGeneticResult
    {
        /** @var PatientGeneticResult $data */

        // Resolve marker
        if ($data->getMarker() !== null) {
            $markerId = $data->getMarker()->getId();
            if ($markerId === null) {
                throw new NotFoundHttpException('GeneticMarker ID must be provided.');
            }
            $marker = $this->entityManager->find(GeneticMarker::class, $markerId);
            if ($marker === null) {
                throw new NotFoundHttpException(sprintf('GeneticMarker with ID %d not found.', $markerId));
            }
            $data->setMarker($marker);
        }

        // Resolve markerValue
        if ($data->getMarkerValue() !== null) {
            $valueId = $data->getMarkerValue()->getId();
            if ($valueId === null) {
                throw new NotFoundHttpException('GeneticMarkerValue ID must be provided.');
            }
            $value = $this->entityManager->find(GeneticMarkerValue::class, $valueId);
            if ($value === null) {
                throw new NotFoundHttpException(sprintf('GeneticMarkerValue with ID %d not found.', $valueId));
            }
            $data->setMarkerValue($value);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}