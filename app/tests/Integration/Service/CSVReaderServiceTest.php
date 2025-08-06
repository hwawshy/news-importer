<?php

namespace App\Tests\Integration\Service;

use App\Service\CSVReaderService;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CSVReaderServiceTest extends KernelTestCase
{
    use MatchesSnapshots;

    private ValidatorInterface $validator;

    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var ValidatorInterface $validator */
        $validator = $container->get(ValidatorInterface::class);
        $this->validator = $validator;
    }

    #[DataProvider('invalidFileProvider')]
    public function testThrowsExceptionOnInvalidFile(string $filepath): void
    {
        $reader = new CSVReaderService($this->validator);
        $this->expectException(\RuntimeException::class);

        iterator_to_array($reader->read($filepath));
    }

    public static function invalidFileProvider(): array
    {
        return [
            'nonexistent file' => [__DIR__ . '/Resources/nonexistent_field.csv'],
            'extra field' => [__DIR__ . '/Resources/extra_field.csv'],
            'missing field' => [__DIR__ . '/Resources/missing_field.csv'],
        ];
    }

    public function testReadsFileCorrectly(): void
    {
        $filepath = __DIR__ . '/Resources/test_cases.csv';
        $reader = new CSVReaderService($this->validator);
        $this->assertMatchesJsonSnapshot(json_encode(iterator_to_array($reader->read($filepath))));
    }
}
