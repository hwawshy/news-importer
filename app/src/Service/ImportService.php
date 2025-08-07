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
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class ImportService
{
    private Writer $writer;

    public function __construct(
        private ImportRepository $importRepository,
        private NewsRepository $newsRepository,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
        private CSVReaderService $readerService,
        #[Autowire('%data_dir%')]
        private string $dataDir,
    ) {
        $this->writer = new Writer();
    }

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
        $chunkReads = 0;
        $validRowCount = 0;
        $openedErrorFile = false;

        try {
            foreach ($this->readerService->read(Path::join($this->dataDir, $import->getImportFile())) as $row) {
                $chunkReads++;
                if ($row instanceof CSVRowResult) {
                    $records[] = $this->mapRowToEntity($row);
                    $validRowCount++;
                } elseif ($row instanceof CSVRowError) {
                    if (!$openedErrorFile) {
                        $errorFile = $this->createErrorFilename($importId);
                        $import->setErrorFile($errorFile);
                        $this->writer->openToFile(Path::join($this->dataDir, $errorFile));
                        $openedErrorFile = true;
                    }
                    $errors[] = $row;
                }

                if ($chunkReads === CSVReaderService::CHUNK_SIZE) {
                    $chunkReads = 0;
                    if (count($records)) {
                        $this->newsRepository->persistAll($records);
                        $records = [];
                    }

                    if (count($errors)) {
                        foreach ($errors as $error) {
                            $this->writer->addRow(Row::fromValues([$error->line, $error->message]));
                        }
                    }
                }
            }

            if ($chunkReads > 0) {
                if (count($records)) {
                    $this->newsRepository->persistAll($records);
                }

                if (count($errors)) {
                    foreach ($errors as $error) {
                        $this->writer->addRow(Row::fromValues([$error->line, $error->message]));
                    }
                }
            }

            $import->setImportEndAt(new \DateTime());
            $import->setStatus(ImportStatusEnumeration::SUCCESS);
            $import->setRowCount($validRowCount);
        } catch (\Exception $e) {
            $import->setImportEndAt(new \DateTime());
            $import->setStatus(ImportStatusEnumeration::FAILED);
            $this->logger->error(sprintf("Import with id %s failed", $import->getId()), ['exception' => $e]);
        } finally {
            $this->writer->close();
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

    private function createErrorFilename(string $importId): string
    {
        return sprintf("errors-%s.xlsx", $importId);
    }
}
