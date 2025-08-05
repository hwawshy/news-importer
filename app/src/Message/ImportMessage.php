<?php

declare(strict_types=1);

namespace App\Message;

readonly class ImportMessage
{
    public function __construct(public readonly string $importId) {}
}
