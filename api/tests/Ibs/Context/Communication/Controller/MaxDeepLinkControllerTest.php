<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Ibs\Context\Communication\Controller\MaxDeepLinkController;
use Ibs\Context\Communication\Entity\MaxDeepLink;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Ibs\Context\Communication\Repository\MaxDeepLinkRepository;
use Ibs\Context\Communication\Repository\PatientChannelIdentityRepository;
use Ibs\Context\Communication\Service\MaxDeepLinkGenerator;
use Ibs\Context\Communication\Service\PatientContactResolver;
use Ibs\Context\PatientManagement\Entity\Patient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class MaxDeepLinkControllerTest extends TestCase
{
    private function controller(?Patient $patient, ?PatientChannelIdentity $identity): MaxDeepLinkController
    {
        $deeplinks = $this->createStub(MaxDeepLinkRepository::class);
        $deeplinks->method('findByPatientId')->willReturn(new MaxDeepLink(7, 'tok123'));

        $identityRepo = $this->createStub(PatientChannelIdentityRepository::class);
        $identityRepo->method('findOneByPatientAndChannel')->willReturn($identity);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn($patient);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $generator = new MaxDeepLinkGenerator($deeplinks, $em, 'botname');
        $resolver = new PatientContactResolver($identityRepo);

        return new MaxDeepLinkController($generator, $em, $resolver);
    }

    public function testReturnsUrlAndBoundTrue(): void
    {
        $identity = new PatientChannelIdentity();
        $identity->setValue('chat-123');

        $response = $this->controller($this->createStub(Patient::class), $identity)->__invoke(7);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(
            ['url' => 'https://max.ru/botname?start=tok123', 'bound' => true],
            json_decode((string) $response->getContent(), true),
        );
    }

    public function testReturnsUrlAndBoundFalse(): void
    {
        $response = $this->controller($this->createStub(Patient::class), null)->__invoke(7);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(
            ['url' => 'https://max.ru/botname?start=tok123', 'bound' => false],
            json_decode((string) $response->getContent(), true),
        );
    }

    public function testReturns404ForMissingPatient(): void
    {
        $response = $this->controller(null, null)->__invoke(7);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
