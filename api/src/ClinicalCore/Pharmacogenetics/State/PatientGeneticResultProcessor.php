<?php

declare(strict_types=1);

namespace App\ClinicalCore\Pharmacogenetics\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\GeneticMarker;
use App\Entity\GeneticMarkerValue;
use App\Entity\PatientGeneticResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class PatientGeneticResultProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire('@api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PatientGeneticResult
    {
        /** @var PatientGeneticResult $data */

        // ---------- 1. Marker ----------
        $markerInput = $data->getMarker();
        if ($markerInput === null) {
            throw new NotFoundHttpException(
                $this->translator->trans('genetic_result.marker_required', [], 'validators')
            );
        }
        $markerId = $markerInput->getId();
        if ($markerId === null) {
            throw new NotFoundHttpException(
                $this->translator->trans('genetic_result.marker_id_required', [], 'validators')
            );
        }
        $marker = $this->entityManager->find(GeneticMarker::class, $markerId);
        if ($marker === null) {
            throw new NotFoundHttpException(
                $this->translator->trans('genetic_result.marker_not_found', ['%markerId%' => $markerId], 'validators')
            );
        }
        $data->setMarker($marker);

        // ---------- 2. MarkerValue ----------
        $valueInput = $data->getMarkerValue();
        if ($valueInput === null) {
            throw new NotFoundHttpException(
                $this->translator->trans('genetic_result.marker_value_required', [], 'validators')
            );
        }
        $valueId = $valueInput->getId();
        if ($valueId === null) {
            throw new NotFoundHttpException(
                $this->translator->trans('genetic_result.marker_value_id_required', [], 'validators')
            );
        }
        $value = $this->entityManager->find(GeneticMarkerValue::class, $valueId);
        if ($value === null) {
            throw new NotFoundHttpException(
                $this->translator->trans('genetic_result.marker_value_not_found', ['%valueId%' => $valueId], 'validators')
            );
        }

        // Принадлежность значения маркеру
        if ($value->getMarker() === null || $value->getMarker()->getId() !== $marker->getId()) {
            throw new BadRequestHttpException(
                $this->translator->trans(
                    'genetic_result.marker_value_wrong_marker',
                    ['%valueId%' => $valueId, '%markerId%' => $marker->getId()],
                    'validators'
                )
            );
        }
        $data->setMarkerValue($value);

        // ---------- 3. Проверка дубликата (только для POST) ----------
        if ($operation instanceof Post) {
            $patient = $data->getPatient();
            if ($patient === null) {
                throw new BadRequestHttpException(
                    $this->translator->trans('genetic_result.patient_required', [], 'validators')
                );
            }

            $existing = $this->entityManager->getRepository(PatientGeneticResult::class)->findOneBy([
                'patient' => $patient,
                'marker'  => $marker,
            ]);

            if ($existing !== null) {
                throw new ConflictHttpException(
                    $this->translator->trans('genetic_result.duplicate_result', [], 'validators')
                );
            }
        }

        // ---------- 4. Передача стандартному процессору ----------
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}