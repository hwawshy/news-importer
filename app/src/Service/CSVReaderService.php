<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\CSVRowError;
use App\Model\CSVRowResult;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CSVReaderService
{
    public function __construct(private ValidatorInterface $validator) {}

    private const int CHUNK_SIZE = 500;

    private const array HEADER_FIELDS = ['title', 'content', 'category', 'url'];

    /**
     * @return iterable<CSVRowError|CSVRowResult>
     */
    public function read(string $filepath): iterable
    {
        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            throw new \RuntimeException(sprintf("Can't open %s", $filepath));
        }

        /** @var ?string[] $header */
        $header = null;
        /** @var (CSVRowResult|CSVRowError)[] $rows */
        $rows = [];
        $line = 0;

        try {
            while ($row = fgetcsv($handle, escape: "")) {
                $line++;
                if ($row === [null]) {
                    // blank line
                    continue;
                }

                if ($header === null) {
                    if (count($row) !== count(self::HEADER_FIELDS)) {
                        throw new \RuntimeException("Got wrong number of header fields");
                    }

                    foreach (self::HEADER_FIELDS as $field) {
                        if (!in_array($field, $row, true)) {
                            throw new \RuntimeException(sprintf("Missing required header field %s", $field));
                        }
                    }
                    $header = $row;
                    continue;
                }

                if (count(self::HEADER_FIELDS) !== count($row)) {
                    $rows[] = new CSVRowError("Mismatching number of fields", $line);
                    continue;
                }

                $rows[] = $this->rowResultOrError(array_combine($header, $row), $line);

                if (count($rows) === self::CHUNK_SIZE) {
                    yield from $rows;
                    $rows = [];
                }
            }

            if (!feof($handle)) {
                throw new \RuntimeException(sprintf("Unexpected error while reading %s", $filepath));
            }

            yield from $rows;
        } finally {
            fclose($handle);
        }
    }

    private function rowResultOrError(array $row, int $line): CSVRowResult|CSVRowError
    {
        $constraint = new Assert\Collection([
            'title' => new Assert\Required([
                new Assert\NotBlank,
                new Assert\Length(
                    max: 255,
                    maxMessage: "Field 'title' cannot be longer than {{ limit }} characters",
                )
            ]),
            'content' => new Assert\Required([
                new Assert\NotBlank(
                    message: "Field 'content' cannot be blank"
                ),
            ]),
            'category' => new Assert\Required([
                new Assert\Regex(
                    pattern: '/^[A-Za-z0-9]+(-[A-Za-z0-9]+)*$/',
                    message: "Filed 'category' must be a non-empty list of dash-separated categories"
                )
            ]),
            'url' => new Assert\Required([
                new Assert\AtLeastOneOf([
                    new Assert\Blank,
                    new Assert\Url(
                        message: 'The url {{ value }} is not a valid url'
                    )
                ])
            ])
        ]);

        $errors = $this->validator->validate($row, $constraint);

        if ($errors->count() === 0) {
            return new CSVRowResult(
                $row['title'],
                $row['content'],
                explode('-', $row['category']),
                $row['url'] === '' ? null : $row['url']
            );
        } else {
            return new CSVRowError($errors[0]->getMessage(), $line);
        }
    }
}
