<?php

declare(strict_types=1);

namespace App\Controller;

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
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%/data')]
        private readonly string $dataDir,
    ) {
    }

    #[Route('/upload', methods: ['POST'])]
    public function index(
        #[MapUploadedFile([new Assert\File(mimeTypes: ['text/csv', 'text/plain'])])] UploadedFile $file
    ): JsonResponse {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = sprintf("%s-%s.csv", $safeFilename, uniqid(more_entropy: true));

        try {
            $file->move($this->dataDir, $newFilename);
        } catch (FileException $e) {
            $this->logger->error('Error while moving uploaded file', ['exception' => $e]);
            return new JsonResponse(['error' => 'Could not process uploaded file'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(['importId' => 'abc123']);
    }
}
