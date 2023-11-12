<?php

namespace App\Tests\Utils;

use App\Entity\Deputy;
use App\Entity\Rooms;
use App\Entity\User;
use App\UtilsHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UtilsHelperTest extends KernelTestCase
{
    public function testIsAllowedToOrganiser(): void
    {
        $deputy = new User();
        $deputy->setFirstName('debuty1');
        $deputy2 = new User();
        $deputy2->setFirstName('deputy2');
        $manager = new User();
        $manager->setFirstName('manager');
        $depElement1 = new Deputy();
        $depElement1->setManager($manager)
            ->setDeputy($deputy)
            ->setCreatedAt(new \DateTime())
            ->setIsFromLdap(false);
        $manager->addManagerElement($depElement1);
        $deputy->addDeputiesElement($depElement1);

        $room = new Rooms();

        $room->setModerator($deputy)   //creator is organiser
        ->setCreator($deputy);   //creator is organiser
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($deputy, $room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom($manager, $room));

        $room->setModerator($manager);  //manager is organiser

        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($deputy, $room));
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($manager, $room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom($deputy2, $room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom(null, $room));

        $room->setCreator($manager);    //manager is creator too --> private room

        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom($deputy, $room));
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($manager, $room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom($deputy2, $room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom(null, $room));

        $room->setCreator($deputy);//creator is deputy

        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($deputy, $room));
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($manager, $room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom($deputy2, $room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom(null, $room));

        $depElement2 = new Deputy();
        $depElement2->setManager($manager)
            ->setDeputy($deputy2)
            ->setCreatedAt(new \DateTime())
            ->setIsFromLdap(false);
        $manager->addManagerElement($depElement2);
        $deputy2->addDeputiesElement($depElement2);

        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($deputy, $room));
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($manager, $room));
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($deputy2, $room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom(null, $room));


        $manager->removeManagerElement($depElement1);
        $deputy->removeDeputiesElement($depElement1);
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom($deputy, $room));
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($manager, $room));
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($deputy2, $room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom(null, $room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom($deputy, null));
    }

    public function testRoomIsReadonly(): void
    {
        $deputy = new User();
        $deputy->setFirstName('debuty1');
        $deputy2 = new User();
        $deputy2->setFirstName('deputy2');
        $manager = new User();
        $manager->setFirstName('manager');
        $room = new Rooms();
        $room->setModerator($deputy)
            ->setCreator($deputy);

        $depElement1 = new Deputy();
        $depElement1->setManager($manager)
            ->setDeputy($deputy)
            ->setCreatedAt(new \DateTime())
            ->setIsFromLdap(false);
        $manager->addManagerElement($depElement1);
        $deputy->addDeputiesElement($depElement1);

        self::assertFalse(UtilsHelper::isRoomReadOnly($room, $deputy));
        self::assertTrue(UtilsHelper::isRoomReadOnly($room, $manager));
        $room->setModerator($manager);
        self::assertFalse(UtilsHelper::isRoomReadOnly($room, $deputy));
        self::assertFalse(UtilsHelper::isRoomReadOnly($room, $manager));
        self::assertTrue(UtilsHelper::isRoomReadOnly($room, $deputy2));

        $depElement2 = new Deputy();
        $depElement2->setManager($manager)
            ->setDeputy($deputy2)
            ->setCreatedAt(new \DateTime())
            ->setIsFromLdap(false);
        $manager->addManagerElement($depElement2);
        $deputy2->addDeputiesElement($depElement2);

        self::assertFalse(UtilsHelper::isRoomReadOnly($room, $deputy2));
        $manager->removeManagerElement($depElement1);
        $deputy->removeDeputiesElement($depElement1);
        self::assertFalse(UtilsHelper::isRoomReadOnly($room, $deputy2));

        $manager->removeManagerElement($depElement2);
        $deputy2->removeDeputiesElement($depElement2);
        self::assertTrue(UtilsHelper::isRoomReadOnly($room, $deputy2));
    }
}
