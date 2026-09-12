<?php

namespace App\Tests\Twig;

use App\Entity\Deputy;
use App\Entity\Rooms;
use App\Entity\User;
use App\Twig\CheckIfUserIsAllowdToOrganize;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class CheckIfUserIsAllowdToOrganizeTest extends TestCase
{
    private function createUser(string $firstName): User
    {
        $user = new User();
        $user->setFirstName($firstName);

        return $user;
    }

    private function makeDeputy(User $manager, User $deputy): void
    {
        $deputyElement = new Deputy();
        $deputyElement->setManager($manager)
            ->setDeputy($deputy)
            ->setCreatedAt(new \DateTime())
            ->setIsFromLdap(false);
        $manager->addManagerElement($deputyElement);
        $deputy->addDeputiesElement($deputyElement);
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = new CheckIfUserIsAllowdToOrganize();

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('isAllowedToOrganize', $functions[0]->getName());
        $this->assertSame([$extension, 'isAllowedToOrganize'], $functions[0]->getCallable());
    }

    public function testModeratorIsAllowedToOrganize(): void
    {
        $extension = new CheckIfUserIsAllowdToOrganize();
        $deputy = $this->createUser('deputy');
        $room = (new Rooms())->setModerator($deputy)->setCreator($deputy);

        $this->assertTrue($extension->isAllowedToOrganize($room, $deputy));
        $this->assertTrue(call_user_func($extension->getFunctions()[0]->getCallable(), $room, $deputy));
    }

    public function testModeratorAndDeputyAreAllowedToOrganize(): void
    {
        $extension = new CheckIfUserIsAllowdToOrganize();
        $manager = $this->createUser('manager');
        $deputy = $this->createUser('deputy');
        $this->makeDeputy($manager, $deputy);
        $room = (new Rooms())->setModerator($manager)->setCreator($deputy);

        $this->assertTrue($extension->isAllowedToOrganize($room, $manager));
        $this->assertTrue($extension->isAllowedToOrganize($room, $deputy));
        $this->assertFalse($extension->isAllowedToOrganize($room, $this->createUser('other')));
        $this->assertFalse($extension->isAllowedToOrganize($room, null));

        $room->setCreator($manager);
        $this->assertTrue($extension->isAllowedToOrganize($room, $manager));
        $this->assertFalse($extension->isAllowedToOrganize($room, $deputy));
    }
}
