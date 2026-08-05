<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Nelmio\CorsBundle\NelmioCorsBundle::class => ['all' => true],
    ApiPlatform\Symfony\Bundle\ApiPlatformBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle::class => ['all' => true],
    Ibs\Context\PatientManagement\PatientManagementBundle::class => ['all' => true],
    Ibs\Context\TreatmentTherapy\TreatmentTherapyBundle::class => ['all' => true],
    Ibs\Context\AICDSS\AICDSSBundle::class => ['all' => true],
    Ibs\Context\LabIoTGateway\LabIoTGatewayBundle::class => ['all' => true],
    Ibs\Context\AdaptivePlanning\AdaptivePlanningBundle::class => ['all' => true],
    Ibs\Context\Communication\CommunicationBundle::class => ['all' => true],
    Ibs\Context\SecurityIdentity\SecurityIdentityBundle::class => ['all' => true],
];
