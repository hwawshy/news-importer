<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Import;
use App\Entity\News;
use App\Enumeration\ImportStatusEnumeration;
use App\Repository\ImportRepository;
use App\Repository\NewsRepository;
use App\Service\CSVReaderService;
use App\Service\ImportService;
use OpenSpout\Reader\XLSX\Reader;
use Psr\Log\NullLogger;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Messenger\MessageBusInterface;

class ImportServiceTest extends KernelTestCase
{
    use MatchesSnapshots;

    private const string TEST_FILE_NAME = "tmp_integration_test.csv";

    private ImportRepository $importRepository;
    private NewsRepository $newsRepository;
    private MessageBusInterface $messageBus;
    private CSVReaderService $readerService;

    private ?string $errorFileName = null;

    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var ImportRepository $importService */
        $importService = $container->get(ImportRepository::class);
        $this->importRepository = $importService;

        /** @var NewsRepository $newsRepository */
        $newsRepository = $container->get(NewsRepository::class);
        $this->newsRepository = $newsRepository;

        /** @var MessageBusInterface $messageBus */
        $messageBus = $container->get(MessageBusInterface::class);
        $this->messageBus = $messageBus;

        /** @var CSVReaderService $readerService */
        $readerService = $container->get(CSVReaderService::class);
        $this->readerService = $readerService;

        $this->importRepository->deleteAll();
        $this->newsRepository->deleteAll();
    }

    public function testImportsFeedCorrectly(): void
    {
        $importService = new ImportService(
            $this->importRepository,
            $this->newsRepository,
            $this->messageBus,
            new NullLogger(),
            $this->readerService,
            sys_get_temp_dir()
        );

        // necessary because the sync transport fetches the handler from the container, overwriting the data dir
        $container = static::getContainer();
        $container->set(ImportService::class, $importService);

        // simulate upload
        $dest = Path::join(sys_get_temp_dir(), self::TEST_FILE_NAME);
        $filesystem = new Filesystem();
        $filesystem->copy(
            Path::join(__DIR__, 'Resources/test_cases.csv'),
            $dest,
        );

        $id = $importService->startImport(self::TEST_FILE_NAME);

        /** @var ?Import $import */
        $import = $this->importRepository->find($id);
        $this->assertNotNull($import);
        $this->assertSame(self::TEST_FILE_NAME, $import->getImportFile());
        $this->assertSame(ImportStatusEnumeration::SUCCESS, $import->getStatus());
        $this->assertSame(3, $import->getRowCount());
        $this->assertNotNull($import->getErrorFile());

        $this->errorFileName = $import->getErrorFile(); // for cleanup

        /** @var News[] $news */
        $news = $this->newsRepository->findAll();
        $this->assertSame($import->getRowCount(), count($news));
        assert(count($news) > 0);

        $news = $news[0];
        $this->assertSame('one category', $news->getTitle());
        $this->assertSame('some content', $news->getContent());
        $this->assertSame('one category', $news->getCategories());
        $this->assertSame('https://www.google.com', $news->getUrl());

        $this->assertMatchesJsonSnapshot($this->getErrorFileContentAsJson(Path::join(sys_get_temp_dir(), $this->errorFileName)));
    }

    private function getErrorFileContentAsJson($filepath): string
    {
        $reader = new Reader();
        $reader->open($filepath);

        $result = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $result[] = $row->toArray();
            }
        }

        $reader->close();

        return json_encode($result, JSON_THROW_ON_ERROR);
    }

    protected function tearDown(): void
    {
        unlink(Path::join(sys_get_temp_dir(), self::TEST_FILE_NAME));
        if ($this->errorFileName !== null) {
            unlink(Path::join(sys_get_temp_dir(), $this->errorFileName));
        }
    }
}
