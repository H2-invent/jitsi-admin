<?php

namespace App\Tests\Addressbook;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\AdHocMeeting\AdhocMeetingWebsocketService;
use App\Service\Lobby\DirectSendService;
use App\Service\OnlineStatus\PresenceService;
use App\Service\Theme\ThemeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdhocMeetingWebsocketServiceTest extends TestCase
{
    /**
     * The extension is registered: the ring must go to the extension-only topic and no browser tab
     * may receive the dialog/push/sound messages any more.
     */
    public function testRingsOnlyTheExtensionWhenRegistered(): void
    {
        $directSendService = $this->createMock(DirectSendService::class);
        $service = $this->createService($directSendService, true);

        $directSendService->expects(self::once())
            ->method('sendCallAdhockmeeding')
            ->with(
                'addhock.notification.title',
                'extension/callee-uid',
                '<a href="/room/join/b/42">Hier beitreten</a>',
                'addhock.notification.pushMessage',
                30000,
                self::anything()
            );
        $directSendService->expects(self::never())->method('sendDialog');
        $directSendService->expects(self::never())->method('sendBrowserPush');
        $directSendService->expects(self::never())->method('sendPlaySound');

        $service->sendAddhocMeetingWebsocket($this->createCallee(), $this->createCreator(), $this->createRoom(), true);
    }

    /**
     * No extension registered: the browser tabs keep ringing exactly as before.
     */
    public function testRingsTheTabsWhenNoExtensionIsRegistered(): void
    {
        $directSendService = $this->createMock(DirectSendService::class);
        $service = $this->createService($directSendService, false);

        $directSendService->expects(self::never())->method('sendCallAdhockmeeding');
        $directSendService->expects(self::once())
            ->method('sendDialog')
            ->with(
                'personal/callee-uid',
                'addhock.notification.title',
                'addhock.notification.pushMessage',
                'question',
                self::anything(),
                ['timeout' => 30, 'timeoutUrl' => '/room/adhoc/timeout/42']
            );
        $directSendService->expects(self::once())
            ->method('sendBrowserPush')
            ->with(
                'personal/callee-uid',
                'addhock.notification.title',
                'addhock.notification.pushMessage',
                self::anything()
            );
        $directSendService->expects(self::once())
            ->method('sendPlaySound')
            ->with('personal/callee-uid', 'caller', self::anything());

        $service->sendAddhocMeetingWebsocket($this->createCallee(), $this->createCreator(), $this->createRoom(), true);
    }

    private function createService(
        DirectSendService $directSendService,
        bool $extensionRegistered
    ): AdhocMeetingWebsocketService {
        $parameterBag = $this->createStub(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed
                => $key === 'ADHOC_CALL_SIGNALING_DURATION' ? '30' : 'Name'
        );

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $urlGen = $this->createStub(UrlGeneratorInterface::class);
        $urlGen->method('generate')->willReturnCallback(
            static fn (string $name, array $parameters = []): string => match ($name) {
                'room_join'       => '/room/join/b/' . $parameters['room'],
                'add_hoc_decline' => '/room/adhoc/decline/' . $parameters['roomId'],
                'add_hoc_timeout' => '/room/adhoc/timeout/' . $parameters['roomId'],
                default           => '/' . $name,
            }
        );

        $theme = $this->createStub(ThemeService::class);
        $theme->method('getApplicationProperties')->willReturn('0');

        $presenceService = $this->createStub(PresenceService::class);
        $presenceService->method('isExtensionRegistered')->willReturn($extensionRegistered);

        return new AdhocMeetingWebsocketService(
            $parameterBag,
            $translator,
            $directSendService,
            $urlGen,
            $theme,
            $presenceService
        );
    }

    private function createCallee(): User
    {
        $callee = $this->createStub(User::class);
        $callee->method('getUid')->willReturn('callee-uid');

        return $callee;
    }

    private function createCreator(): User
    {
        $creator = $this->createStub(User::class);
        $creator->method('getFormatedName')->willReturn('Max Mustermann');

        return $creator;
    }

    private function createRoom(): Rooms
    {
        $room = $this->createStub(Rooms::class);
        $room->method('getId')->willReturn(42);
        $room->method('getName')->willReturn('Konferenz');
        $room->method('getSecondaryName')->willReturn(null);

        return $room;
    }
}
