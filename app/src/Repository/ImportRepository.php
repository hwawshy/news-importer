<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Import;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Import>
 */
class ImportRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Import::class);
    }
}
