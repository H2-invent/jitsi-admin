<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\Rooms;
use App\Entity\User;
use App\Form\Type\SchedulerType;
use App\Repository\TagRepository;
use App\Service\ThemeService;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class SchedulerTypeTest extends KernelTestCase
{
    private MockObject&TagRepository $tagRepository;

    private MockObject&LoggerInterface $logger;

    private MockObject&ThemeService $themeService;

    private MockObject&TranslatorInterface $translator;

    private SchedulerType $subject;

    public function setUp(): void
    {
        $this->tagRepository = $this->createMock(TagRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->themeService = $this->createMock(ThemeService::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->subject = new SchedulerType(
            $this->tagRepository,
            $this->logger,
            $this->themeService,
            $this->translator,
        );

        parent::setUp();
    }

    public function testBuildForm(): void
    {
        $room = $this->getRoomMock();
        $formBuilder = $this->getFormBuilder();
        $user = $this->getUserMock();


        $this->themeService
            ->expects(self::exactly(10))
            ->method('getApplicationProperties')
            ->willReturn(1);

        $options = [
            'data' => $room,
            'server' => [],
            'user' => $user,
            'showTag' => true,
        ];

        $formBuilder
            ->expects(self::exactly(16))
            ->method('add')
            ->willReturn($formBuilder);

        $collection = $this->createMock(Collection::class);

        $user
            ->expects(self::once())
            ->method('getManagers')
            ->willReturn($collection);

        $collection->expects(self::once())->method('toArray')->willReturn([1]);

        $this->subject->buildForm($formBuilder, $options);
    }

    /**
     * @dataProvider provideForConfigureOptions
     */
    public function testConfigureOptions(
        int   $allowMaybeOptionDefault,
        bool  $isEdit,
        bool  $showTag,
        array $attr,
        array $themeServiceReturns,
    ): void
    {
        $this->translator
            ->method('trans')
            ->with('new.room.blockSave.text')
            ->willReturn('test');

        $optionsResolver = $this->getOptionsResolver();

        $this->themeService
            ->expects(self::exactly(count($themeServiceReturns)))
            ->method('getApplicationProperties')
            ->willReturnOnConsecutiveCalls(...$themeServiceReturns);

        $this->subject->configureOptions($optionsResolver);

        $expected = [
            'server' => [],
            'data_class' => Rooms::class,
            'minDate' => 'today',
            'user' => User::class,
            'allowMaybeOption' => (bool)$allowMaybeOptionDefault,
            'isEdit' => $isEdit,
            'attr' => $attr,
            'showTag' => $showTag,
        ];

        $this->assertSame($expected, $optionsResolver->resolve(['isEdit' => $isEdit]));
    }

    public static function provideForConfigureOptions(): array
    {
        $attr = [
            'id' => 'newRoom_form',
        ];

        return [
            "All properties true" => [
                "allowMaybeOptionDefault" => 1,
                "isEdit" => true,
                "showTag" => true,
                "attr" => $attr,
                "themeServiceReturns" => [1, 1, 1],
            ],
            "All properties true except allowMaybeOptionDefault" => [
                "allowMaybeOptionDefault" => 0,
                "isEdit" => true,
                "showTag" => true,
                "attr" => $attr,
                "themeServiceReturns" => [0, 1, 1],
            ],
            "All properties true except allowEditTag" => [
                "allowMaybeOptionDefault" => 1,
                "isEdit" => true,
                "showTag" => false,
                "attr" => $attr,
                "themeServiceReturns" => [1, 1, 0],
            ],
            "All properties true except isEdit" => [
                "allowMaybeOptionDefault" => 1,
                "isEdit" => false,
                "showTag" => true,
                "attr" => $attr,
                "themeServiceReturns" => [1, 1, 1, 1],
            ],
            "All properties true except isEdit and allowEditTag" => [
                "allowMaybeOptionDefault" => 1,
                "isEdit" => false,
                "showTag" => true,
                "attr" => array_merge($attr, ['data-blocktext' => 'test']),
                "themeServiceReturns" => [1, 0, 1, 1, 0],
            ],
        ];
    }

    private function getRoomMock(): MockObject&Rooms
    {
        return $this->createMock(Rooms::class);
    }

    private function getFormBuilder(): MockObject&FormBuilderInterface
    {
        return $this->createMock(FormBuilderInterface::class);
    }

    private function getOptionsResolver(): OptionsResolver
    {
        return new OptionsResolver();
    }

    private function getUserMock(): MockObject&User
    {
        return $this->createMock(User::class);
    }
}