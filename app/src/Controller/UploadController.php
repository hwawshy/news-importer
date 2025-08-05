<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ImportService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UploadController extends AbstractController
{
    private const int MAX_FILENAME_LENGTH = 50;

    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly LoggerInterface $logger,
        private readonly ImportService $importService,
        #[Autowire('%kernel.project_dir%/data')]
        private readonly string $dataDir,
    ) {
    }

    #[Route('/upload', methods: ['POST'])]
    public function upload(
        #[MapUploadedFile([new Assert\File(mimeTypes: ['text/csv', 'text/plain'])])] UploadedFile $file
    ): JsonResponse {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = sprintf("%s-%s.csv", substr((string) $safeFilename, 0, self::MAX_FILENAME_LENGTH) , uniqid());

        try {
            $file->move($this->dataDir, $newFilename);
        } catch (FileException $e) {
            $this->logger->error('Error while moving uploaded file', ['exception' => $e]);
            return new JsonResponse(['error' => 'Could not process uploaded file'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $id = $this->importService->startImport($newFilename);

        return new JsonResponse(['importId' => $id]);
    }
}
