<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\UploadedRecording;
use App\Entity\User;
use App\Message\RecordingUploadedMessage;
use App\Message\TranscriptionMessage;
use App\Repository\RecordingRepository;
use App\Service\Result\Error\RecordingFinalizeError;
use App\Service\Result\Error\RecordingUploadError;
use App\Service\Result\ServiceResult;
use Doctrine\ORM\EntityManagerInterface;
use Gaufrette\FilesystemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Twig\Environment;

class RecordingService
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $kernelProjectDir,
        private readonly Filesystem $localFilesystem,
        private readonly FilesystemInterface $recordingFilesystem,
        private readonly RecordingRepository $recordingRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerService $mailer,
        private readonly TranslatorInterface $translator,
        private readonly Environment $environment,
    )
    {
    }

    public function saveChunk(int $chunkIndex, int $totalChunks, string $recordingUid, UploadedFile $chunk): ServiceResult
    {
        // Temporäres Verzeichnis für Chunks
        $tempDir = $this->getTempPath($recordingUid);
        $this->localFilesystem->mkdir($tempDir);

        // Speichern des aktuellen Chunks
        $chunkPath = "{$tempDir}/chunk_{$chunkIndex}";
        $chunk->move($tempDir, $chunkPath);

        $uploadedChunks = glob("{$tempDir}/chunk_*");
        if (count($uploadedChunks) !== $totalChunks) {
            return ServiceResult::failure(RecordingUploadError::UPLOAD_INCOMPLETE);
        }

        // Upload fertig
        $this->messageBus->dispatch(new RecordingUploadedMessage($recordingUid));

        return ServiceResult::success();
    }

    public function finalizeUpload(string $recordingUid): ServiceResult
    {
        $room = $this->recordingRepository->findOneBy(['uid' => $recordingUid])?->getRoom();
        if ($room === null) {
            return ServiceResult::failure(RecordingFinalizeError::NO_RECORDING_FOUND);
        }

        $tempDir = $this->getTempPath($recordingUid);
        $finalPath = "{$tempDir}/final.bin";
        $this->localFilesystem->remove($finalPath);

        // Chunks suchen
        $chunks = (new Finder())
            ->files()
            ->in($tempDir)
            ->name('chunk_*')
            ->sortByName(true)
        ;
        if ($chunks->count() === 0) {
            return ServiceResult::failure(RecordingFinalizeError::NO_CHUNKS_FOUND);
        }

        // Datei zusammensetzen
        try {
            $finalFile = fopen($finalPath, 'ab');
            foreach ($chunks as $chunk) {
                $chunkFile = fopen($chunk->getPathname(), 'rb');
                stream_copy_to_stream($chunkFile, $finalFile);
                fclose($chunkFile);
            }
        } catch (Throwable) {
            return ServiceResult::failure(RecordingFinalizeError::COULD_NOT_WRITE_FINAL_FILE);
        } finally {
            if (isset($finalFile) && is_resource($finalFile)) {
                fclose($finalFile);
            }
        }

        // Datei in Gaufrette speichern
        $fileStream = fopen($finalPath, 'rb');
        $fileName = md5(uniqid()) . '.mp4';
        $this->recordingFilesystem->write($fileName, $fileStream);
        fclose($fileStream);

        // Datenbankeintrag erstellen
        $uploadedFileEntity = new UploadedRecording();
        $uploadedFileEntity->setFilename($fileName)
            ->setDisplayName((new \DateTime())->format('d.m.Y H:i') . '.mp4')
            ->setRoom($room)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setType('video/mp4')
        ;
        $this->entityManager->persist($uploadedFileEntity);
        $this->entityManager->flush();

        $this->sendEmailAfterUploading($room->getModerator(), $room);

        $this->localFilesystem->remove($tempDir);

        if ($room->getServer()?->isEnableTranscription()) {
            $this->messageBus->dispatch(
                new TranscriptionMessage($uploadedFileEntity->getId())
            );
        }

        return ServiceResult::success();
    }

    private function getTempPath(string $recordingId): string
    {
        return "{$this->kernelProjectDir}/upload_chunks/{$recordingId}";
    }


    private function sendEmailAfterUploading(User $user, Rooms $room): void
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
