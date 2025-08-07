<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enumeration\ImportStatusEnumeration;
use App\Repository\ImportRepository;
use App\Service\ImportService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ImportController extends AbstractController
{
    private const int MAX_FILENAME_LENGTH = 50;

    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly LoggerInterface $logger,
        private readonly ImportService $importService,
        private readonly ImportRepository $importRepository,
        #[Autowire('%data_dir%')]
        private readonly string $dataDir,
    ) {
    }

    #[Route('/upload', name: 'file_upload', methods: ['POST'])]
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

    #[Route('/status/{id}', requirements: ['id' => Requirement::UUID_V7], methods: ['GET'])]
    public function status(string $id): JsonResponse
    {
        $import = $this->importRepository->find($id);
        if ($import === null) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['status' => $import->getStatus()->value]);
    }

    #[Route('/list/{status}', name: 'list_by_status', methods: ['GET'])]
    public function list(ImportStatusEnumeration $status): JsonResponse
    {
        return new JsonResponse($this->importRepository->findBy(['status' => $status]));
    }

    #[Route('/errors/{id}', requirements: ['id' => Requirement::UUID_V7], methods: ['GET'])]
    public function streamErrorFile(string $id): Response
    {
        $import = $this->importRepository->find($id);
        if ($import?->getErrorFile() === null) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        $stream = new Stream(Path::join($this->dataDir, $import->getErrorFile()));
        $response = new BinaryFileResponse($stream);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $import->getErrorFile()
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        return $response;
    }
}
