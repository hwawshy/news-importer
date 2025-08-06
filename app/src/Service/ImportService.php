<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Import;
use App\Entity\News;
use App\Enumeration\ImportStatusEnumeration;
use App\Message\ImportMessage;
use App\Model\CSVRowError;
use App\Model\CSVRowResult;
use App\Repository\ImportRepository;
use App\Repository\NewsRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class ImportService
{
    public function __construct(
        private ImportRepository $importRepository,
        private NewsRepository $newsRepository,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
        private CSVReaderService $readerService,
        #[Autowire('%data_dir%')]
        private string $dataDir,
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

        $records = [];
        $errors = [];
        $count = 0;

        try {
            foreach ($this->readerService->read(Path::join($this->dataDir, $import->getImportFile())) as $row) {
                $count++;
                if ($row instanceof CSVRowResult) {
                    $records[] = $this->mapRowToEntity($row);
                } elseif ($row instanceof CSVRowError) {
                    $errors[] = $row;
                }

                if ($count === CSVReaderService::CHUNK_SIZE) {
                    $count = 0;
                    if (count($records)) {
                        $this->newsRepository->persistAll($records);
                        $records = [];
                    }

                    if (count($errors)) {

                    }
                }
            }

            if ($count > 0) {
                if (count($records)) {
                    $this->newsRepository->persistAll($records);
                }

                if (count($errors)) {

                }
            }

            $import->setImportEndAt(new \DateTime());
            $import->setStatus(ImportStatusEnumeration::SUCCESS);
        } catch (\RuntimeException $e) {
            $import->setImportEndAt(new \DateTime());
            $import->setStatus(ImportStatusEnumeration::FAILED);
            $this->logger->error(sprintf("Import with id %s failed", $import->getId()), ['exception' => $e]);
        }

        $this->importRepository->persist($import);
        $this->importRepository->flush();
    }

    private function mapRowToEntity(CSVRowResult $row): News
    {
        $news = new News();
        $news->setTitle($row->title);
        $news->setContent($row->content);
        $news->setCategories(implode("-", $row->categories));
        $news->setUrl($row->url);

        return $news;
    }
}
