<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\UploadedRecording;
use App\Entity\User;
use App\Repository\RecordingRepository;
use App\Repository\RoomsRepository;
use App\Repository\UploadedRecordingRepository;
use App\Service\MailerService;
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
        private readonly MailerService       $mailer,
        private readonly TranslatorInterface $translator,
        private readonly Environment         $environment,
        private ParameterBagInterface        $parameterBag,
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
            throw new AccessDeniedHttpException('Invalid Bearer Token');
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
        // Temporäres Verzeichnis für Chunks
        $tempDir = $this->parameterBag->get('kernel.project_dir') . "/upload_chunks/{$recordingId}/";
        $this->localFilesystem->mkdir($tempDir);

        // Speichern des aktuellen Chunks
        $chunkPath = $tempDir . "chunk_{$chunkIndex}";
        $uploadedFile->move($tempDir, "chunk_{$chunkIndex}");

        $uploadedChunks = glob($tempDir . 'chunk_*');
        if (count($uploadedChunks) == $totalChunks) {
            // Finalen Dateipfad bestimmen
            $finalPath = $tempDir . 'final.bin';
            $this->localFilesystem->remove($finalPath);

            // Datei zusammensetzen
            foreach (range(0, $totalChunks - 1) as $i) {
                $chunkContent = file_get_contents($tempDir . "chunk_{$i}");
                file_put_contents($finalPath, $chunkContent, FILE_APPEND);
            }

            // Temporäre Chunks entfernen

            $room = $this->recordingRepository->findOneBy(['uid' => $recordingId])->getRoom();
            // Datei in Gaufrette speichern
            $fileStream = fopen($finalPath, 'r');
            $fileName = md5(uniqid()) . '.mp4';
            $fileType = $uploadedFile->getClientMimeType();
            $this->filesystem->write($fileName, $fileStream);
            fclose($fileStream);
            $this->localFilesystem->remove($tempDir);
            // Datenbankeintrag erstellen

            $uploadedFileEntity = new UploadedRecording();
            $uploadedFileEntity->setFilename($fileName)
                ->setDisplayName((new \DateTime())->format('d.m.Y H:i') . '.mp4')
                ->setRoom($room)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setType('video/mp4'); // Beispieltyp

            $this->entityManager->persist($uploadedFileEntity);
            $this->entityManager->flush();
            $this->sendEmailAfterUploading($room->getModerator(), $room, $uploadedFileEntity);

            return new JsonResponse(['status' => 'File uploaded successfully']);
        }

        return new JsonResponse(['status' => 'Chunk received']);
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

    private function sendEmailAfterUploading(User $user, Rooms $room, UploadedRecording $uploadedRecording)
    {
        $this->mailer->sendEmail(
            $room->getModerator(),
            $this->translator->trans('recording.email.subject', ['{name}' => $room->getName()]),
            $this->environment->render('email/uploadRecording.html.twig', ['room' => $room, 'user' => $user]),
            $room->getServer(),
            null,
            $room
        );
    }

}
