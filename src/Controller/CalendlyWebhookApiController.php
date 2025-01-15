<?php

namespace App\Controller;

use App\Form\CalendlyTokenType;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\api\RoomService;
use App\Service\calendly\CallendlyConnect;

use App\Service\JoinUrlGeneratorService;
use App\Service\RemoveRoomService;
use App\Service\RoomAddService;
use App\Service\RoomGeneratorService;
use App\Service\ServerUserManagment;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\User;

class CalendlyWebhookApiController extends AbstractController
{

    public function __construct(
        private CallendlyConnect        $callendlyConnect,
        private TranslatorInterface     $translator,
        private EntityManagerInterface  $entityManager,
        private UserRepository          $userRepository,
        private RoomsRepository         $roomsRepository,
        private ServerUserManagment     $serverUserManagment,
        private RoomService             $roomService,
        private RoomAddService          $roomAddService,
        private JoinUrlGeneratorService $joinUrlGeneratorService,
        private LoggerInterface         $logger,
        private RemoveRoomService       $removeRoomService,
    )
    {
    }

    #[
        Route('/room/calendly/connect', name: 'app_calendly_webhook_connect', methods: ['GET', 'POST'])]
    public function connect(Request $request): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(CalendlyTokenType::class,
            $user,
            ['action' => $this->generateUrl('app_calendly_webhook_connect')]
        );
        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $user = $form->getData();
                /**
                 * @var User $user
                 */
                $calendlyToken = $user->getCalendlyToken();
                try {
                    if ($calendlyToken) {
                        $res = $this->callendlyConnect->getUserInfo($calendlyToken)['resource'];
                        $exitingUser = $this->userRepository->findOneBy(['calendly_user_uri' => $res['uri']]);
                        if ($exitingUser) {
                            $this->addFlash('success', $this->translator->trans('calendly.connect.alreadyConnected'));
                            return $this->redirectToRoute('dashboard');
                        }
                        $user->setCalendlyToken($calendlyToken);
                        $user->setCalendlyOrgUri($res['current_organization']);
                        $user->setCalendlyUserUri($res['uri']);
                        $user->setCalendlySucessfullyAdded(false);
                        $user->setCalendlySecret(md5(uniqid()));
                        $cleanRes = $this->callendlyConnect->getWebhooks($user);
                        foreach ($cleanRes['collection'] as $data) {
                            $this->callendlyConnect->cleanWebhooks($user, $data['uri']);
                        }
                        $con_res = $this->callendlyConnect->registerWebhook($user);
                        $user->setCalendlySucessfullyAdded(true);
                        $user->setCalendlyWebhookId($con_res['resource']['uri']);
                        $this->entityManager->persist($user);
                        $this->entityManager->flush();
                        $this->addFlash('success', $this->translator->trans('calendly.connect.success'));
                        return $this->redirectToRoute('dashboard');
                    }
                } catch (\Exception $exception) {
                    $this->addFlash('danger', $exception->getMessage());
                    return $this->redirectToRoute('dashboard');
                }

            }
        } catch (\Exception $e) {


        }
        return $this->render('calendly_webhook_api/form.html.twig', ['form' => $form->createView(), 'title' => $this->translator->trans('calendly.header')]);
    }

    #[Route('/room/calendly/remove', name: 'app_calendly_webhook_remove', methods: ['GET'])]
    public function remove(Request $request): Response
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        try {

            try {

                $this->callendlyConnect->cleanWebhooks($user, $user->getCalendlyWebhookId());
                $user->setCalendlyToken(null);
                $user->setCalendlyOrgUri(null);
                $user->setCalendlyUserUri(null);
                $user->setCalendlySecret(null);
                $user->setCalendlyWebhookId(null);
                $user->setCalendlySucessfullyAdded(false);
                $this->entityManager->persist($user);
                $this->entityManager->flush();

            } catch (\Exception $exception) {
                $user->setCalendlyToken(null);
                $user->setCalendlySucessfullyAdded(false);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                $this->addFlash('danger', $exception->getMessage());
                return $this->redirectToRoute('dashboard');
            }

        } catch
        (\Exception $e) {
            $this->addFlash('danger', $exception->getMessage());
            return $this->redirectToRoute('dashboard');

        }
        $this->addFlash('success', $this->translator->trans('calendly.remove.success'));
        return $this->redirectToRoute('dashboard');
    }

    #[Route('/calendly/webhook/api', name: 'app_calendly_webhook_api')]
    public function index(Request $request): Response
    {
        try {
            $body = json_decode($request->getContent(), true);

            $userCalendly = $body['created_by'];
            $this->logger->debug('searchgin for calendly User',['calendly_user'=>$userCalendly]);
            $user = $this->userRepository->findOneBy(array('calendly_user_uri' => $userCalendly));
            $this->logger->debug('calendly user found',['user'=>$user->getId()]);
            if ($user) {
                $event = $body['event'];
                $this->logger->debug('event found',['event'=>$event]);
                switch ($event) {
                    case 'invitee.created':
                        $this->logger->debug('calendly creating found');
                        $server = $this->serverUserManagment->getServersFromUser($user);
                        $existingEvent = $this->roomsRepository->findOneBy(['calendly_uri'=>$body['payload']['event']]);
                        if ($existingEvent){
                               return new JsonResponse(['result' => 'error', 'error' => 1,'message'=>'event already exit']);
                        }
                        if ($server) {
                            $server = $server[0];
                            $startTime = new \DateTime($body['payload']['scheduled_event']['start_time'],new \DateTimeZone('UTC'));
                            $startTime->setTimezone(new \DateTimeZone($body['payload']['timezone']));
                            $endTime = new \DateTime($body['payload']['scheduled_event']['end_time'],new \DateTimeZone('UTC'));
                            $endTime->setTimezone(new \DateTimeZone($body['payload']['timezone']));
                            $duration = $startTime->diff($endTime);
                            $eventNAme = $body['payload']['scheduled_event']['name'] . ' | ' . $body['payload']['name'] .' from calendly';
                            $newRoom = $this->roomService->createRoom($user, $server, $startTime, $duration->i, $eventNAme);
                            $newRoom->setTimeZone($body['payload']['timezone']);
                            $newRoom->setCalendlyUri($body['payload']['event']);
                            $agenda = '';
                            foreach ($body['payload']['questions_and_answers'] as $qa) {
                                $agenda .= '**' . $qa['question'] . '**: ' . $qa['answer'] . "\n\r";
                            }
                            $newRoom->setAgenda($agenda);
                            $this->entityManager->persist($newRoom);
                            $this->entityManager->flush();
                            $participant = $this->roomAddService->createSingleParticipantAndAddtoRoom($body['payload']['email'], $user, $newRoom);
                            foreach ($body['payload']['scheduled_event']['event_guests'] as $guest) {
                                $participant = $this->roomAddService->createSingleParticipantAndAddtoRoom($guest['email'], $user, $newRoom);
                            }
                            return new JsonResponse(['result' => 'success', 'error' => 0, 'url' => $this->joinUrlGeneratorService->generateUrl($newRoom, $user)]);

                        }

                        break;
                    case 'invitee.canceled':
                        $this->logger->debug('got calendly cancellation');
                        $room = $this->roomsRepository->findOneBy(['calendly_uri' => $body['payload']['event']]);
                        if ($room) {
                            $this->logger->debug('room found',['room'=>$room->getId()]);
                            $this->logger->debug('found calendly room',['room'=>$room->getId()]);
                            $this->removeRoomService->deleteRoom($room);
                            $this->logger->debug('room removed');
                            return new JsonResponse(['result' => 'success', 'error' => 0]);
                        }
                        break;
                    default:
                        break;
                }

            } else {
                $this->logger->error('NO user with this user uri found');
                throw new NotFoundHttpException('not found');
            }
            return new JsonResponse(['result' => 'okay', 'error' => 0]);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new NotFoundHttpException('not found');
        }


    }
}
