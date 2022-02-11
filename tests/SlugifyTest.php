<?php

namespace App\Tests;

use App\UtilsHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SlugifyTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        self::assertEquals('asdf1234',UtilsHelper::slugify('asdf!"$§"$&%$1234'));
        self::assertEquals('asdf1234',UtilsHelper::slugify('!/")(§äöüasdf!"$§"$&%$1234'));
        self::assertEquals('asdf_1234',UtilsHelper::slugify('!/")(§äöüasdf !"$§"$&%$1234'));
        self::assertEquals('asdf_1234_qwert',UtilsHelper::slugify('!/")(§äöüasdf !"$§"$&%$1234 !&/"%qwert'));
    }
}
