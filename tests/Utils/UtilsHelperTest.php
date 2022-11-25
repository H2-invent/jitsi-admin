<?php

namespace App\Tests\Utils;

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
        $manager->addDeputy($deputy)->setFirstName('manager');
        $room = new Rooms();

        $room->setModerator($deputy)   //creator is organiser
            ->setCreator($deputy);   //creator is organiser
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($deputy,$room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom($manager,$room));

        $room->setModerator($manager);  //manager is organiser

        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($deputy,$room));
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($manager,$room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom($deputy2,$room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom(null,$room));

        $room->setCreator($manager);    //manager is creator too --> private room

        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom($deputy,$room));
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($manager,$room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom($deputy2,$room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom(null,$room));

        $room->setCreator($deputy);//creator is deputy

        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($deputy,$room));
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($manager,$room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom($deputy2,$room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom(null,$room));

        $manager->addDeputy($deputy2);//add deputy2 as second deputy
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($deputy,$room));
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($manager,$room));
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($deputy2,$room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom(null,$room));

        $manager->removeDeputy($deputy);//creator deputy is no longer deputy
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom($deputy,$room));
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($manager,$room));
        self::assertTrue(UtilsHelper::isAllowedToOrganizeRoom($deputy2,$room));
        self::assertFalse(UtilsHelper::isAllowedToOrganizeRoom(null,$room));
    }

    public function testRoomIsReadonly(): void
    {
        $deputy = new User();
        $deputy->setFirstName('debuty1');
        $deputy2 = new User();
        $deputy2->setFirstName('deputy2');
        $manager = new User();
        $manager->addDeputy($deputy)->setFirstName('manager');
        $room = new Rooms();
        $room->setModerator($deputy)
            ->setCreator($deputy);
        self::assertFalse(UtilsHelper::isRoomReadOnly($room, $deputy));
        self::assertTrue(UtilsHelper::isRoomReadOnly($room, $manager));
       $room->setModerator($manager);
        self::assertFalse(UtilsHelper::isRoomReadOnly($room, $deputy));
        self::assertFalse(UtilsHelper::isRoomReadOnly($room, $manager));
        self::assertTrue(UtilsHelper::isRoomReadOnly($room, $deputy2));

        $manager->addDeputy($deputy2);//deputy 2 is added, so he can see the room not readOnly
        self::assertFalse(UtilsHelper::isRoomReadOnly($room, $deputy2));
        $manager->removeDeputy($deputy);//remove the first deputy.
        self::assertFalse(UtilsHelper::isRoomReadOnly($room, $deputy2));
        $manager->removeDeputy($deputy2);//remove the first deputy.
        self::assertTrue(UtilsHelper::isRoomReadOnly($room, $deputy2));
    }
}
