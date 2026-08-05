<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\AICDSS\Command;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\AICDSS\Entity\GeneticMarker;
use Ibs\Context\AICDSS\Entity\MarkerDrugRelation;
use Ibs\Context\TreatmentTherapy\Entity\Drug;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SeedGeneticMarkersCommandTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();

        $application = new Application($client->getKernel());
        $command = $application->find('app:seed-genetic-markers');
        $this->commandTester = new CommandTester($command);
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

    public function testSeedsMarkersAndLinksThemToMatchingDrugs(): void
    {
        $warfarin = new Drug();
        $warfarin->setNominative('варфарин');
        $this->entityManager->persist($warfarin);
        $this->entityManager->flush();

        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(0, $exitCode);

        // The command persists via the same EntityManager instance, whose identity
        // map would otherwise return the in-memory (not DB-reloaded) collection.
        $this->entityManager->clear();

        $markerRepo = $this->entityManager->getRepository(GeneticMarker::class);
        $marker = $markerRepo->findOneBy(['geneSymbol' => 'CYP2C9_2']);
        $this->assertNotNull($marker);
        $this->assertCount(3, $marker->getPossibleValues());

        $relationRepo = $this->entityManager->getRepository(MarkerDrugRelation::class);
        $relation = $relationRepo->findOneBy(['marker' => $marker, 'drug' => $warfarin]);
        $this->assertNotNull($relation, 'CYP2C9_2 must be linked to варфарин.');
    }

    public function testRunningTwiceDoesNotCreateDuplicateMarkers(): void
    {
        $this->commandTester->execute([]);
        $this->commandTester->execute([]);

        $markerRepo = $this->entityManager->getRepository(GeneticMarker::class);
        $count = $markerRepo->count(['geneSymbol' => 'CYP2C9_2']);

        $this->assertSame(1, $count);
        $this->assertStringContainsString('already exists', $this->commandTester->getDisplay());
    }
}
