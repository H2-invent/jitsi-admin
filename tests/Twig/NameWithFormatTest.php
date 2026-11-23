<?php

namespace App\Tests\Twig;

use App\Entity\User;
use App\Service\FormatName;
use App\Twig\NameWithFormat;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class NameWithFormatTest extends TestCase
{

    private function functionNames(object $extension): array
    {
        $names = [];
        foreach ((new \ReflectionClass($extension))->getMethods() as $method) {
            foreach ($method->getAttributes(\Twig\Attribute\AsTwigFunction::class) as $attribute) {
                $names[] = $attribute->newInstance()->name;
            }
        }
        return $names;
    }

    private function filterNames(object $extension): array
    {
        $names = [];
        foreach ((new \ReflectionClass($extension))->getMethods() as $method) {
            foreach ($method->getAttributes(\Twig\Attribute\AsTwigFilter::class) as $attribute) {
                $names[] = $attribute->newInstance()->name;
            }
        }
        return $names;
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john@example.com')
            ->setUsername('jdoe')
            ->setSpezialProperties(['ou' => 'IT', 'departmentNumber' => '42']);

        return $user;
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = new NameWithFormat(new FormatName());
        $this->assertSame(["nameOfUserwithFormat"], $this->functionNames($extension));
    }

    public function testNameOfUserwithFormatReplacesStandardFields(): void
    {
        $extension = new NameWithFormat(new FormatName());
        $user = $this->createUser();

        $this->assertSame('John', $extension->nameOfUserwithFormat($user, '$user.firstName$'));
        $this->assertSame('JohnDoe', $extension->nameOfUserwithFormat($user, '$user.firstName$ $user.lastName$'));
        $this->assertSame('john@example.com', $extension->nameOfUserwithFormat($user, '$user.email$'));
        $this->assertSame('jdoe', $extension->nameOfUserwithFormat($user, '$user.username$'));
    }

    public function testNameOfUserwithFormatReplacesSpecialFields(): void
    {
        $extension = new NameWithFormat(new FormatName());
        $user = $this->createUser();

        $this->assertSame('IT', $extension->nameOfUserwithFormat($user, '$user.specialField.ou$'));
        $this->assertSame('42', $extension->nameOfUserwithFormat($user, '$user.specialField.departmentNumber$'));
    }

    public function testNameOfUserwithFormatFallsBackToUsernameWhenNothingIsResolved(): void
    {
        $extension = new NameWithFormat(new FormatName());
        $user = $this->createUser();

        $this->assertSame('jdoe', $extension->nameOfUserwithFormat($user, 'No placeholder here'));
    }

    public function testNameOfUserwithFormatFallsBackToUsernameForEmptyField(): void
    {
        $extension = new NameWithFormat(new FormatName());
        $user = (new User())->setUsername('fallback')->setFirstName('');

        $this->assertSame('fallback', $extension->nameOfUserwithFormat($user, '$user.firstName$'));
    }

    public function testNameOfUserwithFormatViaRegisteredCallable(): void
    {
        $extension = new NameWithFormat(new FormatName());
        $user = $this->createUser();

        $this->assertSame('JohnDoe', call_user_func([$extension, 'nameOfUserwithFormat'], $user, '$user.firstName$ $user.lastName$'));
    }
}
