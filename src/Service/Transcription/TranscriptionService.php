<?php
declare(strict_types=1);

namespace App\Service\Transcription;

use App\Entity\Rooms;
use App\Entity\Transcription;
use App\Entity\UploadedRecording;
use App\Repository\RoomStatusParticipantRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class TranscriptionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerService $mailerService,
        private readonly Environment $twig,
        private readonly TranslatorInterface $translator,
        private readonly TranscriptionProviderResolver $transcriptionProviderResolver,
        private readonly RoomStatusParticipantRepository $participantRepository,
    )
    {
    }

    public function transcribe(UploadedRecording $recording): void
    {
        $server = $recording->getRoom()->getServer();
        $transcriptionProvider = $this->transcriptionProviderResolver->resolve($server);

        $audioChunksGenerator = $transcriptionProvider->yieldAudioChunks($recording->getFilename());
        [$text, $audioChunks] = $transcriptionProvider->transcribeChunks($audioChunksGenerator, $server);
        $this->addNewTranscription($recording->getRoom(), $text);
        $transcriptionProvider->deleteChunks($audioChunks);
    }

    public function addNewTranscription(Rooms $room, string $text): Transcription
    {
        $header = $this->getHeader($room);
        $transcription = (new Transcription())
            ->setRoom($room)
            ->setText($header . $text)
        ;
        $this->entityManager->persist($transcription);
        $this->entityManager->flush();

        $this->sendTranscriptionReadyNotification($room, $transcription);

        return $transcription;
    }

    public function removeTranscription(Transcription $transcription): void
    {
        $this->entityManager->remove($transcription);
        $this->entityManager->flush();
    }

    public function toggleTranscriptionForRoom(Rooms $room, bool $enabled): void
    {
        $room->setEnableTranscription($enabled);

        $this->entityManager->persist($room);
        $this->entityManager->flush();
    }

    private function sendTranscriptionReadyNotification(Rooms $room, Transcription $transcription): void
    {
        $moderator = $room->getModerator();
        if ($moderator === null) {
            return;
        }

        $subject = $this->translator->trans('transcription.ready.subject', ['{roomName}' => $room->getName()]);
        $content = $this->twig->render('email/newTranscriptionReady.twig', ['room' => $room, 'transcription' => $transcription]);

        $this->mailerService->sendEmail(
            user: $moderator,
            betreff: $subject,
            content: $content,
            server: $room->getServer(),
            rooms: $room
        );
    }

    private function getHeader(Rooms $room): string
    {
        $name = $room->getName();

        $date = $room->getStart()?->format('Y-m-d') ?? '';
        if ($date !== '') {
            $date = "**{$date}**\n\n";
        }

        $agenda = $room->getAgenda();
        if (!empty($agenda)) {
            $agenda .= "\n";
        }

        $participantEntities = $this->participantRepository->findParticipantsByRoom($room);
        $participants = '';
        if (count($participantEntities) > 0) {
            $participantNames = array_map(static fn($participant) => '-' . $participant->getParticipantName(), $participantEntities);
            $participantNamesFormatted = implode("\n", $participantNames);

            $participants = "Participants:\n\n {$participantNamesFormatted}";
        }

        return <<<HEADER
        # {$name}
        
        {$date}{$agenda}
        
        {$participants}
        ---


        HEADER;
    }
}
