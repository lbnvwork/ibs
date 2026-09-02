<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Shared\Common;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SchemaTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testPatientAnamnesisTableExistsWithExpectedColumns(): void
    {
        $columns = $this->listTableColumns('patient_anamnesis');

        foreach ([
            'id', 'patient_id', 'mk', 'ak', 'tk', 'lk', 'diabetes_type_id',
            'stroke_hemorrhagic', 'stroke_ischemic', 'ckd_stage_id', 'acs_id',
            'cha2ds2_vasc', 'has_bled', 'created_at', 'updated_at',
        ] as $column) {
            $this->assertArrayHasKey($column, $columns, sprintf('patient_anamnesis.%s must exist', $column));
        }
    }

    public function testReferenceTablesExist(): void
    {
        $diabetesColumns = $this->listTableColumns('diabetes_types');
        foreach (['id', 'name', 'full_name'] as $column) {
            $this->assertArrayHasKey($column, $diabetesColumns, sprintf('diabetes_types.%s must exist', $column));
        }

        $ckdColumns = $this->listTableColumns('ckd_stages');
        foreach (['id', 'name', 'full_name'] as $column) {
            $this->assertArrayHasKey($column, $ckdColumns, sprintf('ckd_stages.%s must exist', $column));
        }
    }

    public function testPatientsHaveConsentAndMaxMessengerColumns(): void
    {
        $columns = $this->listTableColumns('patients');

        $this->assertArrayHasKey('consent', $columns);
        $this->assertArrayHasKey('max_messenger', $columns);
        $this->assertFalse($columns['consent']->getNotnull(), 'patients.consent must be nullable');
        $this->assertFalse($columns['max_messenger']->getNotnull(), 'patients.max_messenger must be nullable');
    }

    public function testTreatmentsHaveAntiplateletColumns(): void
    {
        $columns = $this->listTableColumns('treatments');

        $this->assertArrayHasKey('antiplatelet_drug_id', $columns);
        $this->assertArrayHasKey('antiplatelet_doze', $columns);
        $this->assertFalse($columns['antiplatelet_drug_id']->getNotnull(), 'treatments.antiplatelet_drug_id must be nullable');
        $this->assertFalse($columns['antiplatelet_doze']->getNotnull(), 'treatments.antiplatelet_doze must be nullable');
    }

    public function testVitalsHaveCreatinineColumn(): void
    {
        $vitalsColumns = $this->listTableColumns('patient_vitals');
        $latestColumns = $this->listTableColumns('patient_vitals_latest');

        $this->assertArrayHasKey('creatinine', $vitalsColumns);
        $this->assertArrayHasKey('creatinine', $latestColumns);
        $this->assertFalse($vitalsColumns['creatinine']->getNotnull(), 'patient_vitals.creatinine must be nullable');
        $this->assertFalse($latestColumns['creatinine']->getNotnull(), 'patient_vitals_latest.creatinine must be nullable');
    }

    /**
     * @return array<string, \Doctrine\DBAL\Schema\Column>
     */
    private function listTableColumns(string $table): array
    {
        $schemaManager = $this->entityManager->getConnection()->createSchemaManager();

        /** @var array<string, \Doctrine\DBAL\Schema\Column> $columns */
        $columns = $schemaManager->listTableColumns($table);

        return $columns;
    }
}
