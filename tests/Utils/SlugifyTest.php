<?php

namespace App\Tests\Utils;

use App\UtilsHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SlugifyTest extends KernelTestCase
{
    public function testnoDot(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        self::assertEquals('asdf1234', UtilsHelper::slugify('asdf!"$§"$&%$1234'));
        self::assertEquals('asdf1234', UtilsHelper::slugify('!/")(§äöüasdf!"$§"$&%$1234'));
        self::assertEquals('asdf_1234', UtilsHelper::slugify('!/")(§äöüasdf !"$§"$&%$1234'));
        self::assertEquals('asdf_1234_qwert', UtilsHelper::slugify('!/")(§äöüasdf !"$§"$&%$1234 !&/"%qwert'));
        self::assertEquals('asdf_1234_qwert', UtilsHelper::slugify('!/")(§äöüAsdf !"$§"$&%$1234 !&/"%QweRt'));
    }
    public function testwithDot(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        self::assertEquals('asdf123.4', UtilsHelper::slugifywithDot('asdf!"$§"$&%$123.4'));
        self::assertEquals('asdf1234', UtilsHelper::slugifywithDot('!/")(§äöüasdf!"$§"$&%$1234'));
        self::assertEquals('.asdf_1234', UtilsHelper::slugifywithDot('!/")(.§äöüasdf !"$§"$&%$1234'));
        self::assertEquals('a.sdf_1234_qwert', UtilsHelper::slugifywithDot('!/")(§äöüa.sdf !"$§"$&%$1234 !&/"%qwert'));
        self::assertEquals('a.sdf_1234_qwert', UtilsHelper::slugifywithDot('!/")(§äöüA.sdF !"$§"$&%$1234 !&/"%qWert'));
    }
}
