<?php

namespace App\MessageHandler;

use App\Entity\Rooms;
use App\Message\CustomMailerMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class CustomMailerMessageDispatcher
{
    public function __construct(
        private MailerInterface        $mailer,
        private ParameterBagInterface  $parameterBag,
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger
    )
    {
    }

    public function __invoke(CustomMailerMessage $customMailerMessage)
    {
        $this->logger->debug($customMailerMessage->getDsn());
        $transport = $this->transport = Transport::fromDsn($customMailerMessage->getDsn());
        $this->logger->debug('We build the new Mailer from the dsn', ['dsn' => $customMailerMessage->getDsn()]);
        try {
            $transport->send($customMailerMessage->getEmail());
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->logger->debug('there was an exeption during sending', ['error' => $exception->getMessage()]);
            $room = $this->entityManager->getRepository(Rooms::class)->find($customMailerMessage->getRoomId());
            $this->logger->debug('We looking for a room with the id', ['id' => $customMailerMessage->getRoomId()]);
            $this->sendNotdelivery($room, $customMailerMessage->getAbsender(), $customMailerMessage->getTo());
        }
    }

    private function sendNotdelivery(Rooms $room, $to, $wrongEmail)
    {
        $sender = $this->parameterBag->get('registerEmailAdress');
        $senderName = $this->parameterBag->get('registerEmailName');
        $message = (new Email())
            ->subject('Invalid email address ')
            ->from(new Address($sender, $senderName))
            ->to($to)
            ->html(
                '<h2>You tried to send an email with an invalid email address.:' . $wrongEmail . '</h2>'
                . '<p>Please doublecheck the email address and try to resend the message again.</p>'
                . ($room ? sprintf('<br><p>%s: %s</p>', 'Room name', $room->getName()) : '')
            );
        $this->logger->info('we send an email to', ['to' => $to]);
        $this->mailer->send($message);
    }
}
