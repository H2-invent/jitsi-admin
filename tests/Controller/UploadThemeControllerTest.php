<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

class UploadThemeControllerTest extends WebTestCase
{
    public function testIndexRendersUploadForm(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/upload/theme/form');

        $this->assertResponseIsSuccessful();
    }

    public function testSaveWithoutFileRedirectsToForm(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/upload/theme/form');
        $form = $crawler->filter('form')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/room/upload/theme/form');
    }

    public function testSaveWithInvalidThemeRedirectsToForm(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $cacheDir = self::getContainer()->getParameter('app.theme.cache_dir');
        $before = is_dir($cacheDir) ? scandir($cacheDir) : [];

        $zipPath = tempnam(sys_get_temp_dir(), 'theme') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('readme.txt', 'not a theme');
        $zip->close();

        try {
            $crawler = $client->request('GET', '/room/upload/theme/form');
            $form = $crawler->filter('form')->form();
            $values = $form->getPhpValues();
            $name = array_key_first($values);
            $form[$name . '[theme]']->upload($zipPath);
            $client->submit($form);

            $this->assertResponseRedirects('/room/upload/theme/form');
        } finally {
            unlink($zipPath);
            if (is_dir($cacheDir)) {
                $filesystem = new Filesystem();
                foreach (array_diff(scandir($cacheDir), $before, ['.', '..']) as $entry) {
                    $filesystem->remove($cacheDir . DIRECTORY_SEPARATOR . $entry);
                }
            }
        }
    }
}
