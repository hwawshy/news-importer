<?php

declare(strict_types=1);

namespace App\Model;

readonly class CSVRowResult
{
    public function __construct(
        public string $title,
        public string $content,
        /** @var string[] $categories */
        public array $categories,
        public ?string $url = null
    ) {
    }
}
