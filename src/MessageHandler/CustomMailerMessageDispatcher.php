<?php

namespace App\MessageHandler;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Message\CustomMailerMessage;
use App\Message\LobbyLeaverMessage;
use App\Service\Lobby\ToModeratorWebsocketService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\RouterInterface;

class CustomMailerMessageDispatcher implements MessageHandlerInterface
{
    public function __construct(
        private MailerInterface        $mailer,
        private ParameterBagInterface  $parameterBag,
        private EntityManagerInterface $entityManager)
    {

    }

    public function __invoke(CustomMailerMessage $customMailerMessage)
    {
        $transport = $this->transport = Transport::fromDsn($customMailerMessage->getDsn());
        try {
            $transport->send($customMailerMessage->getEmail());
        } catch (\Exception $exception) {
            $room = $this->entityManager->getRepository(Rooms::class)->find($customMailerMessage->getRoomId());

            $sender = $this->parameterBag->get('registerEmailAdress');
            $senderName = $this->parameterBag->get('registerEmailName');
            $message = (new Email())
                ->subject('Wrong email adress ')
                ->from(new Address($sender, $senderName))
                ->to($customMailerMessage->getAbsender())
                ->html('<h2>You tried to invite a participant with a wrong email adress.:' . $customMailerMessage->getTo() . '</h2>'
                    . '<p>Please doublecheck the email adress.</p>'
                    . ($room ? sprintf('<br><p>%s: %s</p>', 'Room name', $room->getName()):'')
                );
            $this->mailer->send($message);
        }

    }
}