<?php

declare(strict_types=1);

namespace App\Model;

readonly class CSVRowError
{
    public function __construct(
        public string $message,
        public int $line
    ) {
    }
}
