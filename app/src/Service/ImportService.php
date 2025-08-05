<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Import;
use App\Enumeration\ImportStatusEnumeration;
use App\Message\ImportMessage;
use App\Repository\ImportRepository;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class ImportService
{
    public function __construct(
        private ImportRepository $importRepository,
        private MessageBusInterface $messageBus,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function startImport(string $filename): string
    {
        $import = new Import();
        $import->setImportFile($filename);

        $this->importRepository->persist($import);
        $this->importRepository->flush();

        $id = $import->getId()->toString();

        $this->messageBus->dispatch(new ImportMessage($id));

        return $id;
    }

    public function doImport(string $importId): void
    {
        $import = $this->importRepository->find($importId);

        if ($import === null) {
            throw new \RuntimeException(sprintf("Import with id %s not found", $importId));
        }

        $import->setImportEndAt(new \DateTime());
        $import->setStatus(ImportStatusEnumeration::SUCCESS);


        $this->importRepository->persist($import);
        $this->importRepository->flush();
    }
}
