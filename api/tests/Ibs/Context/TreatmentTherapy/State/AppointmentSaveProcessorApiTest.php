<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\State;

use App\Tests\Support\AuthenticatesUsers;
use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\PatientManagement\Entity\Patient;
use Ibs\Context\TreatmentTherapy\Entity\Appointment;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Ibs\Context\TreatmentTherapy\Entity\Treatment;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AppointmentSaveProcessorApiTest extends WebTestCase
{
    use AuthenticatesUsers;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();
        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        $this->entityManager->close();

        parent::tearDown();
    }

    public function testAppointmentOnActiveTreatmentIsPersistedWithAutoFilledFields(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager, login: 'doctor.ivanov');

        $treatment = $this->createTreatment(realEndDt: null);
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/appointments',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'treatment' => '/api/treatments/'.$treatment->getId(),
                'appointmentDt' => '2026-08-01T10:00:00+00:00',
                'doze' => 1.5,
            ])
        );

        $response = $this->client->getResponse();
        $this->assertSame(201, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);

        $this->entityManager->clear();
        $appointment = $this->entityManager->find(Appointment::class, $data['id']);

        $this->assertNotNull($appointment);
        $this->assertNotNull($appointment->getCreationDt(), 'creationDt must be auto-filled when omitted.');
        $this->assertNotNull($appointment->getModDt(), 'modDt must be auto-filled on save.');
        $this->assertSame('doctor.ivanov', $appointment->getDoctorName(), 'doctorName must be auto-filled from the authenticated user.');
    }

    public function testAppointmentOnInactiveTreatmentIsRejected(): void
    {
        $token = $this->createAuthenticatedClient($this->client, $this->entityManager);

        $treatment = $this->createTreatment(realEndDt: new \DateTime('-1 day'));
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/appointments',
            server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']),
            content: json_encode([
                'treatment' => '/api/treatments/'.$treatment->getId(),
                'appointmentDt' => '2026-08-01T10:00:00+00:00',
                'doze' => 1.5,
            ])
        );

        $this->assertSame(422, $this->client->getResponse()->getStatusCode());
    }

    private function createTreatment(?\DateTimeInterface $realEndDt): Treatment
    {
        $patient = new Patient();
        $patient->setFirstname('Тест');
        $patient->setLastname('Тестов');
        $patient->setBirthday(new \DateTime('1985-01-01'));
        $patient->setSmsPhone('8(900)000-00-00');
        $this->entityManager->persist($patient);

        $drug = new Drug();
        $drug->setNominative('Варфарин');
        $this->entityManager->persist($drug);

        $treatment = new Treatment();
        $treatment->setPatient($patient);
        $treatment->setDrug($drug);
        $treatment->setDiagnosis('Диагноз');
        $treatment->setDiagnosisCode('I80');
        $treatment->setMnoFrom(2.0);
        $treatment->setMnoTo(3.0);
        $treatment->setBegDt(new \DateTime('-10 days'));
        $treatment->setRealEndDt($realEndDt);
        $this->entityManager->persist($treatment);

        return $treatment;
    }
}
