<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ibs\Context\Communication\Entity\NotificationTemplate;

/**
 * @extends ServiceEntityRepository<NotificationTemplate>
 */
class NotificationTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationTemplate::class);
    }

    public function findOneByCodeAndChannel(string $code, string $channel): ?NotificationTemplate
    {
        return $this->findOneBy(['code' => $code, 'channel' => $channel]);
    }
}
