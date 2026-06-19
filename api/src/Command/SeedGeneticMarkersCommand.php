<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Drug;
use App\Entity\GeneticMarker;
use App\Entity\GeneticMarkerValue;
use App\Entity\MarkerDrugRelation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:seed-genetic-markers')]
class SeedGeneticMarkersCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Seeding genetic markers...');

        $markersData = [
            [
                'geneSymbol' => 'CYP2C9',
                'fullName'   => 'Цитохром P450 2C9',
                'rsId'       => 'rs1799853 (CYP2C9*2), rs1057910 (CYP2C9*3)',
                'values' => [
                    ['value' => '*1/*1', 'label' => '*1/*1 (норма)', 'description' => 'Нормальная активность фермента'],
                    ['value' => '*1/*2', 'label' => '*1/*2 (гетерозигота)', 'description' => 'Снижение активности, повышенная чувствительность к варфарину, риск кровотечений'],
                    ['value' => '*1/*3', 'label' => '*1/*3 (гетерозигота)', 'description' => 'Снижение активности, повышенная чувствительность к варфарину, риск кровотечений'],
                    ['value' => '*2/*2', 'label' => '*2/*2 (мутантная гомозигота)', 'description' => 'Значительное снижение активности, высокая чувствительность, повышенный риск кровотечений'],
                    ['value' => '*3/*3', 'label' => '*3/*3 (мутантная гомозигота)', 'description' => 'Значительное снижение активности, высокая чувствительность, повышенный риск кровотечений'],
                    ['value' => '*2/*3', 'label' => '*2/*3 (компаунд-гетерозигота)', 'description' => 'Снижение активности, повышенная чувствительность, риск кровотечений'],
                ],
                'description' => 'Все варианты, кроме *1/*1, – снижение активности фермента, повышение чувствительности к варфарину, риск кровотечений',
            ],
            [
                'geneSymbol' => 'VKORC1_3673',
                'fullName'   => 'Витамин К эпоксидредуктаза, G3673A',
                'rsId'       => 'rs9923231',
                'values' => [
                    ['value' => 'GG', 'label' => 'GG (норма)', 'description' => 'Нормальная чувствительность к варфарину'],
                    ['value' => 'GA', 'label' => 'GA (гетерозигота)', 'description' => 'Повышение чувствительности к варфарину, требуется меньшая доза'],
                    ['value' => 'AA', 'label' => 'AA (мутантная гомозигота)', 'description' => 'Повышение чувствительности к варфарину, требуется меньшая доза'],
                ],
                'description' => 'Аллель A повышает чувствительность к варфарину (требуется меньшая доза)',
            ],
            [
                'geneSymbol' => 'VKORC1_3730',
                'fullName'   => 'Витамин К эпоксидредуктаза, G3730A',
                'rsId'       => 'rs7294',
                'values' => [
                    ['value' => 'GG', 'label' => 'GG (норма)', 'description' => 'Нормальная чувствительность'],
                    ['value' => 'GA', 'label' => 'GA (гетерозигота)', 'description' => 'Резистентность, требуется более высокая доза варфарина'],
                    ['value' => 'AA', 'label' => 'AA (мутантная гомозигота)', 'description' => 'Резистентность, требуется более высокая доза варфарина'],
                ],
                'description' => 'Аллель A понижает чувствительность (резистентность), требуется более высокая доза варфарина',
            ],
            [
                'geneSymbol' => 'ABCB1',
                'fullName'   => 'АВС-транспортёр 1',
                'rsId'       => null,
                'values' => [
                    ['value' => 'CC', 'label' => 'CC (норма)', 'description' => 'Нормальный транспорт ПОАК'],
                    ['value' => 'CT', 'label' => 'CT (гетерозигота)', 'description' => 'Возможно изменение транспорта ПОАК'],
                    ['value' => 'TT', 'label' => 'TT (мутантная гомозигота)', 'description' => 'Изменённый транспорт ПОАК'],
                ],
                'description' => 'Влияет на транспорт ПОАК (ривароксабан, апиксабан, дабигатран)',
            ],
            [
                'geneSymbol' => 'CYP3A5',
                'fullName'   => 'Цитохром P450 3A5',
                'rsId'       => null,
                'values' => [
                    ['value' => '*1/*1', 'label' => '*1/*1 (норма)', 'description' => 'Нормальный метаболизм'],
                    ['value' => '*1/*3', 'label' => '*1/*3 (гетерозигота)', 'description' => 'Сниженный метаболизм апиксабана и ривароксабана'],
                    ['value' => '*3/*3', 'label' => '*3/*3 (мутантная гомозигота)', 'description' => 'Значительно сниженный метаболизм апиксабана и ривароксабана'],
                ],
                'description' => 'Метаболизм апиксабана и ривароксабана',
            ],
        ];

        $markerRepo = $this->entityManager->getRepository(GeneticMarker::class);
        foreach ($markersData as $data) {
            $existing = $markerRepo->findOneBy(['geneSymbol' => $data['geneSymbol']]);
            if (!$existing) {
                $marker = new GeneticMarker();
                $marker->setGeneSymbol($data['geneSymbol']);
                $marker->setFullName($data['fullName']);
                $marker->setRsId($data['rsId']);
                $marker->setDescription($data['description']);
                $this->entityManager->persist($marker);

                foreach ($data['values'] as $val) {
                    $markerValue = new GeneticMarkerValue();
                    $markerValue->setMarker($marker);
                    $markerValue->setValue($val['value']);
                    $markerValue->setLabel($val['label']);
                    $markerValue->setDescription($val['description']);
                    $this->entityManager->persist($markerValue);
                }
                $output->writeln(sprintf('  Created marker %s with %d values', $data['geneSymbol'], count($data['values'])));
            } else {
                $output->writeln(sprintf('  Marker %s already exists', $data['geneSymbol']));
            }
        }
        $this->entityManager->flush();

        $drugRepo = $this->entityManager->getRepository(Drug::class);
        $relationRepo = $this->entityManager->getRepository(MarkerDrugRelation::class);

        $drugMappings = [
            'CYP2C9'       => ['варфарин', 'фенилин'],
            'VKORC1_3673'  => ['варфарин', 'фенилин'],
            'VKORC1_3730'  => ['варфарин', 'фенилин'],
            'ABCB1'        => ['ривароксабан', 'апиксабан', 'дабигатран'],
            'CYP3A5'       => ['апиксабан', 'ривароксабан'],
        ];

        foreach ($drugMappings as $geneSymbol => $drugNames) {
            $marker = $markerRepo->findOneBy(['geneSymbol' => $geneSymbol]);
            if (!$marker) {
                $output->writeln(sprintf('  ERROR: Marker %s not found, skipping drug relations', $geneSymbol));
                continue;
            }

            foreach ($drugNames as $drugName) {
                $drug = $drugRepo->findOneBy(['nominative' => $drugName]);
                if (!$drug) {
                    $output->writeln(sprintf('  WARNING: Drug "%s" not found, skipping relation for %s', $drugName, $geneSymbol));
                    continue;
                }

                $existingRelation = $relationRepo->findOneBy([
                    'marker' => $marker,
                    'drug'   => $drug,
                ]);

                if (!$existingRelation) {
                    $relation = new MarkerDrugRelation();
                    $relation->setMarker($marker);
                    $relation->setDrug($drug);
                    $this->entityManager->persist($relation);
                    $output->writeln(sprintf('  Created relation: %s <-> %s', $geneSymbol, $drugName));
                } else {
                    $output->writeln(sprintf('  Relation already exists: %s <-> %s', $geneSymbol, $drugName));
                }
            }
        }

        $this->entityManager->flush();

        $output->writeln('Seeding completed.');

        return Command::SUCCESS;
    }
}