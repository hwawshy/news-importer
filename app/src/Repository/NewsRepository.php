<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\News;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<News>
 */
class NewsRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    /** @param News[] $records */
    public function persistAll(array $records): void
    {
        foreach ($records as $record) {
            $this->persist($record);
        }

        $this->flush();
    }
}
