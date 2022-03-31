<?php

namespace App\Tests\Livecycle;

use App\Entity\User;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class LifeCycleTest extends PantherTestCase
{
//    protected function runTest(): void
//    {
//        $this->markTestSkipped('Skip Lifecycle');
//    }

//    public function testSomething(): void
//    {
//        $client = static::createPantherClient();
//        $userRepo = self::getContainer()->get(UserRepository::class);
//        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
//
//        $crawler = $client->request('GET', '/room/dashboard');
//        $client->takeScreenshot('screenshot/homepage.png');
//        $this->assertSelectorTextContains('h1', 'Online Jitsi Verwaltung');
//    }

    protected function loginPantherClient(Client $client, User $user)
    {
        $client->request('GET', '/');
        $session = $this->getContainer()->get('session');
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $session->set('_security_main', serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());

        $client->getCookieJar()->set($cookie);
        return $client;
    }
}
