<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Import;
use App\Repository\ImportRepository;

readonly class ImportService
{
    public function __construct(private ImportRepository $importRepository) {}

    public function startImport(string $filename): string
    {
        $import = new Import();
        $import->setImportFile($filename);

        $this->importRepository->persist($import);
        $this->importRepository->flush();

        return $import->getId()->toString();
    }
}
