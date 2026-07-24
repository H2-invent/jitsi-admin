<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\UploadedRecording;
use App\Entity\User;
use App\Message\TranscriptionMessage;
use App\Repository\RecordingRepository;
use App\Repository\RoomsRepository;
use App\Repository\UploadedRecordingRepository;
use App\Service\MailerService;
use App\Service\RecordingService;
use App\Service\Result\Error\RecordingUploadError;
use Doctrine\ORM\EntityManagerInterface;
use Gaufrette\FilesystemInterface;
use Gaufrette\Stream\InMemoryBuffer;
use Gaufrette\StreamMode;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RecordingController extends AbstractController
{
    private FilesystemInterface $filesystem;
    private EntityManagerInterface $entityManager;
    private string $expectedBearerToken;
    private Filesystem $localFilesystem;

    public function __construct(
        FilesystemInterface                  $recordingFilesystem,
        EntityManagerInterface               $entityManager,
        private RecordingRepository          $recordingRepository,
        private LoggerInterface              $logger,
        private UploadedRecordingRepository  $uploadedRecordingRepository,
        private ParameterBagInterface        $parameterBag,
        private readonly MessageBusInterface $messageBus,
        private readonly RecordingService    $recordingService,
    )
    {
        $this->filesystem = $recordingFilesystem; // Filesystem für die Aufnahmen
        $this->entityManager = $entityManager;
        $this->expectedBearerToken = $_ENV['RECORDING_UPLOAD_TOKEN']; // Token aus Umgebungsvariablen
        $this->localFilesystem = new Filesystem();
    }

    #[Route('/recording/upload', name: 'recording_file_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        set_time_limit(600);
        // Überprüfe Bearer-Token
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !$this->isValidBearerToken($authHeader)) {
            throw $this->createAccessDeniedException('Invalid Bearer Token');
        }

        // Hole die Konferenz-ID und die Datei
        $chunkIndex = $request->request->get('chunk_index');
        $totalChunks = $request->request->get('total_chunks');
        $recordingId = $request->request->get('recording_id');
        $uploadedFile = $request->files->get('file');

        if ($chunkIndex === null) {
            $this->logger->debug('Chunk index is null');
            return new JsonResponse(['error' => 'Chunk index is missing'], Response::HTTP_BAD_REQUEST);
        }

        if ($totalChunks === null) {
            $this->logger->debug('Total chunks is null');
            return new JsonResponse(['error' => 'Total chunks are missing'], Response::HTTP_BAD_REQUEST);
        }

        if (!$uploadedFile) {
            $this->logger->debug('Uploaded file is missing');
            return new JsonResponse(['error' => 'No uploaded file provided'], Response::HTTP_BAD_REQUEST);
        }

        if (!$recordingId) {
            $this->logger->debug('Recording ID is missing');
            return new JsonResponse(['error' => 'Recording ID is missing'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->recordingService->saveChunk(
            (int)$chunkIndex,
            (int)$totalChunks,
            $recordingId,
            $uploadedFile,
        );

        if ($result->isFailure()) {
            if ($result->getErrorType() === RecordingUploadError::UPLOAD_INCOMPLETE) {
                return new JsonResponse(['status' => 'Chunk received']);
            }

            return new JsonResponse(['error' => $result->getErrorType()->value]);
        }

        return new JsonResponse(['status' => 'File uploaded successfully']);
    }

    #[Route('/room/recordings/modal/{room}', name: 'recording_modal', methods: ['GET'])]
    public function modal(Rooms $room): Response
    {
        if (!$room->getModerator() == $this->getUser()) {
            throw new NotFoundHttpException('Room not found');
        }

        return $this->render('recording/modal.html.twig', ['room' => $room]);
    }

    #[Route('/room/recordings/download/{filename}', name: 'recording_download', methods: ['GET'])]
    public function download(string $filename): Response
    {
        try {
            // Finde die Datei in der Datenbank
            $uploadedFile = $this->uploadedRecordingRepository->findOneBy(['filename' => $filename]);

            if (!$uploadedFile) {
                return new JsonResponse(['error' => 'File not found in database'], Response::HTTP_NOT_FOUND);
            }

            // Überprüfe, ob der Benutzer berechtigt ist, die Datei herunterzuladen
            if ($uploadedFile->getRoom()->getModerator() !== $this->getUser()) {
                throw new AccessDeniedHttpException('Access denied');
            }

            // Überprüfe, ob die Datei im Dateisystem existiert
            if (!$this->filesystem->has($uploadedFile->getFilename())) {
                return new JsonResponse(['error' => 'File not found in path'], Response::HTTP_NOT_FOUND);
            }

            // Hole die Dateierweiterung basierend auf dem MIME-Typ
            $extension = $this->getFileExtensionFromMimeType($uploadedFile->getType());
            // Adapter abrufen (LocalAdapter)
            // Den Adapter holen
            $file = $this->filesystem->get($filename);
            $response = new StreamedResponse(function () use ($filename) {
                $stream = $this->filesystem->createStream($filename);
                $stream->open(new StreamMode('rb'));
                while (!$stream->eof()){
                    $chunk = $stream->read(8 * 1024);
                    echo $chunk;
                   flush();
                }
                $stream->close();
            });


            $response->headers->set('Content-Type', $uploadedFile->getType());
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $uploadedFile->getRoom()->getName() . '.' . $extension . '"');
            $response->headers->set('Content-Length', $this->filesystem->size($uploadedFile->getFilename()));

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Error downloading file', ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Error downloading file: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Hacky Route um die Recordings einer Sofortkonferenz runterzuladen
     * Spätestens wenn die Recordings auch über das Dashboard heruntergeladen werden sollen, sollten wir das auch in den Service auslagern
     * FIXME
     */
    #[Route('/room/recordings/download-fastconference/{id}', name: 'recording_download_fastconference', methods: ['GET'])]
    public function downloadForFastConference(Rooms $room): Response
    {
        // Bei Sofortkonferenzen gehen wir davon aus dass nur ein einzelnes Recording existiert
        $uploadedRecording = $this->uploadedRecordingRepository->findOneBy(['room' => $room]);
        if ($uploadedRecording === null) {
            throw $this->createNotFoundException('Could not find recording');
        }

        return $this->download($uploadedRecording->getFilename());
    }


    #[Route('/room/recordings/remove/{filename}', name: 'recording_remove', methods: ['GET'])]
    public function remove(string $filename): Response
    {
        try {
            $uploadedFile = $this->uploadedRecordingRepository->findOneBy(['filename' => $filename]);

            if (!$uploadedFile) {
                return new JsonResponse(['error' => 'File not found in database'], Response::HTTP_NOT_FOUND);
            }
            if ($uploadedFile->getRoom()->getModerator() !== $this->getUser()) {
                throw new AccessDeniedHttpException('Access denied');
            }
            $this->entityManager->remove($uploadedFile);
            $this->entityManager->flush();
            // Überprüfen, ob die Datei existiert
            if ($this->filesystem->has($uploadedFile->getFilename())) {
                $this->filesystem->delete($uploadedFile->getFilename());
            }

            return new JsonResponse(['error' => false]);
        } catch (\Exception $e) {
            $this->logger->error('Error downloading file', ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Error downloading file: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    private function isValidBearerToken(string $authHeader): bool
    {
        // Bearer-Token extrahieren
        $token = str_replace('Bearer ', '', $authHeader);
        return $token === $this->expectedBearerToken;
    }

    private function generateUniqueFileName(string $originalName): string
    {
        return md5(uniqid()) . '_' . $originalName;
    }

    private function getFileExtensionFromMimeType(string $mimeType): string
    {
        $mimeTypeMap = [
            'image/jpg' => 'jpg',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            'audio/mp3' => 'mp3',
            'video/mp4' => 'mp4',
            'text/plain' => 'txt',
        ];

        return $mimeTypeMap[$mimeType] ?? 'bin';  // Standard auf 'bin', falls der MIME-Typ nicht gefunden wird
    }
}
