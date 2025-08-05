<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ImportMessage;
use App\Service\ImportService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ImportMessageHandler
{
    public function __construct(private ImportService $importService) {}

    public function __invoke(ImportMessage $message): void
    {
        $this->importService->doImport($message->importId);
    }
}
