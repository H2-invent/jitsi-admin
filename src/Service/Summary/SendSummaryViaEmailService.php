<?php

namespace App\Service\Summary;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\MailerService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class SendSummaryViaEmailService
{
    private Rooms $rooms;

    public function __construct(
        private MailerService        $mailerService,
        private CreateSummaryService $createSummaryService,
        private TranslatorInterface  $translator,
        private Environment          $environment
    )
    {
    }

    public function sendSummaryForRoom(Rooms $rooms)
    {
        $this->rooms = $rooms;
        foreach ($this->rooms->getUser() as $data) {
            $this->sendSumaryToParticipant($data);
        }
    }

    public function sendSumaryToParticipant(User $user)
    {
        $dompdf = $this->createSummaryService->createSummaryPdf($this->rooms);
        $pdf = $dompdf->output();

        $attachment = [['type' => 'application/pdf', 'filename' => $this->rooms->getName() . '.pdf', 'body' => $pdf]];

        $this->mailerService->sendEmail(
            $user,
            $this->translator->trans('Meeting fertig'),
            $this->environment->render('email/finishMeeting.html.twig', ['room' => $this->rooms]),
            $this->rooms->getServer(),
            $this->rooms->getModerator()->getEmail(),
            $this->rooms,
            $attachment
        );
    }
}
