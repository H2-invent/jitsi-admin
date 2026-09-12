<?php

namespace App\Tests\Helper;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Helper\UidHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UidHelperTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UidHelper $helper;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->helper = new UidHelper($this->entityManager);
    }

    public function testGetUidReturnsUidRealWithoutRepeater(): void
    {
        $room = new Rooms();
        $room->setUidReal('real-uid');

        $this->assertSame('real-uid', $this->helper->getUid($room));
    }

    public function testGetUidReturnsRepeaterUidWhenAlreadySet(): void
    {
        $repeat = (new Repeat())->setUid('repeat-uid');
        $room = (new Rooms())->setUidReal('real-uid')->setRepeater($repeat);

        $this->assertSame('repeat-uid', $this->helper->getUid($room));
    }

    public function testGetUidGeneratesAndPersistsRepeaterUid(): void
    {
        $repeat = (new Repeat())
            ->setRepeatType(1)
            ->setStartDate(new \DateTime());
        $room = (new Rooms())->setUidReal('real-uid')->setRepeater($repeat);

        $uid = $this->helper->getUid($room);

        $this->assertSame(32, strlen($uid));
        $this->assertSame($uid, $repeat->getUid());
        $this->assertNotNull($repeat->getId());

        $this->entityManager->clear();
        $persisted = $this->entityManager->getRepository(Repeat::class)->findOneBy(['uid' => $uid]);
        $this->assertNotNull($persisted);
    }
}
