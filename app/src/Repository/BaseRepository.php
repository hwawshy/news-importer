<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;

/**
 * @template T of object
 * @template-extends EntityRepository<T>
 */
class BaseRepository extends EntityRepository implements ServiceEntityRepositoryInterface
{
    private EntityManagerInterface $em;

    /**
     * @var EntityRepository<T>
     */
    private EntityRepository $decorated;

    /**
     * @param class-string<T> $entityClass
     * @throws LogicException
     */
    public function __construct(private readonly ManagerRegistry $registry, private readonly string $entityClass)
    {
        /**
         * @var EntityManagerInterface|null $manager
         */
        $manager = $this->registry->getManagerForClass($this->entityClass);

        if ($manager === null) {
            throw new LogicException('Could not get entity manager for ' . $this->entityClass);
        }

        $this->em        = $manager;
        $this->decorated = new EntityRepository($this->em, $this->em->getClassMetadata($this->entityClass));
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?object
    {
        return $this->decorated->find($id, $lockMode, $lockVersion);
    }

    public function findAll(): array
    {
        return $this->decorated->findAll();
    }

    /**
     * @param T $entity
     * @return void
     */
    public function persist(object $entity): void
    {
        $this->em->persist($entity);
    }

    public function flush(): void
    {
        $this->em->flush();
    }

    public function beginTransaction(): void
    {
        $this->em->beginTransaction();
    }

    public function rollback(): void
    {
        $this->em->rollback();
    }

    public function commit(): void
    {
        $this->em->commit();
    }

    public function close(): void
    {
        $this->em->close();
    }

    public function resetManager(): void
    {
        /** @var EntityManagerInterface $manager */
        $manager = $this->registry->resetManager();

        $this->em        = $manager;
        $this->decorated = new EntityRepository($this->em, $this->em->getClassMetadata($this->entityClass));
    }
}
