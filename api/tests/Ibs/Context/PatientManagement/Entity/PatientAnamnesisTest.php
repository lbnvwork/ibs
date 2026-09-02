<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\PatientManagement\Entity;

use Ibs\Context\PatientManagement\Entity\CkdStage;
use Ibs\Context\PatientManagement\Entity\DiabetesType;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\PatientManagement\Entity\PatientAnamnesis;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PatientAnamnesisTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    /**
     * СЦ-3.31.1 (позитив): сахарный диабет и стадия ХБП — справочники (ManyToOne),
     * а не перечисления. «Нет диабета/ХБП» = null (нет ссылки).
     */
    public function testDiabetesAndCkdStageAreReferenceAssociations(): void
    {
        $diabetes = new DiabetesType();
        $diabetes->setName('СД2');
        $diabetes->setFullName('Сахарный диабет 2 типа');

        $ckd = new CkdStage();
        $ckd->setName('Стадия 2');
        $ckd->setFullName('Хроническая болезнь почек, стадия 2');

        $anamnesis = new PatientAnamnesis();
        $anamnesis->setDiabetes($diabetes);
        $anamnesis->setCkdStage($ckd);

        $this->assertSame($diabetes, $anamnesis->getDiabetes());
        $this->assertSame($ckd, $anamnesis->getCkdStage());

        $empty = new PatientAnamnesis();
        $this->assertNull($empty->getDiabetes());
        $this->assertNull($empty->getCkdStage());
    }

    /**
     * СЦ-3.31.13 / СЦ-3.31.5 (негатив): шкалы CHA₂DS₂-VASc и HAS-BLED должны быть ≥ 0
     * (отрицательный балл отклоняется).
     */
    public function testScalesMustBeNonNegative(): void
    {
        $valid = new PatientAnamnesis();
        $valid->setPatient(new Patient());
        $valid->setCha2ds2Vasc(0);
        $valid->setHasBled(0);
        $this->assertCount(0, $this->validator->validate($valid));

        $invalid = new PatientAnamnesis();
        $invalid->setPatient(new Patient());
        $invalid->setCha2ds2Vasc(-1);
        $invalid->setHasBled(-1);
        $violations = $this->validator->validate($invalid);

        $paths = array_map(
            static fn ($v) => $v->getPropertyPath(),
            iterator_to_array($violations)
        );
        $this->assertContains('cha2ds2Vasc', $paths);
        $this->assertContains('hasBled', $paths);
    }

    /**
     * СЦ-3.31.13 / СЦ-3.31.5 (негатив): шкалы CHA₂DS₂-VASc и HAS-BLED ограничены 0..9
     * (балл выше 9 отклоняется).
     */
    public function testScalesMustNotExceedNine(): void
    {
        $invalid = new PatientAnamnesis();
        $invalid->setPatient(new Patient());
        $invalid->setCha2ds2Vasc(10);
        $invalid->setHasBled(10);
        $violations = $this->validator->validate($invalid);

        $paths = array_map(
            static fn ($v) => $v->getPropertyPath(),
            iterator_to_array($violations)
        );
        $this->assertContains('cha2ds2Vasc', $paths);
        $this->assertContains('hasBled', $paths);
    }

    /**
     * СЦ-3.31.1 (негатив): анамнез без пациента отклоняется (NotNull).
     */
    public function testPatientIsRequired(): void
    {
        $anamnesis = new PatientAnamnesis();
        $violations = $this->validator->validate($anamnesis);

        $paths = array_map(
            static fn ($v) => $v->getPropertyPath(),
            iterator_to_array($violations)
        );
        $this->assertContains('patient', $paths);
    }

    /**
     * СЦ-3.31.1 (позитив): полный анамнез (клапаны + шкалы) валиден.
     */
    public function testAnamnesisWithAllFlagsIsValid(): void
    {
        $anamnesis = new PatientAnamnesis();
        $anamnesis->setPatient(new Patient());
        $anamnesis->setMk(true);
        $anamnesis->setAk(false);
        $anamnesis->setTk(null);
        $anamnesis->setLk(true);
        $anamnesis->setStrokeHemorrhagic(false);
        $anamnesis->setStrokeIschemic(true);
        $anamnesis->setCha2ds2Vasc(4);
        $anamnesis->setHasBled(1);

        $this->assertCount(0, $this->validator->validate($anamnesis));
    }
}
