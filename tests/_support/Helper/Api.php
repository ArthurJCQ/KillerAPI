<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Domain\Player\Entity\Player;
use App\Tests\ApiTester;
use Codeception\Module;
use Symfony\Bundle\FrameworkBundle\Test\TestBrowserToken;

class Api extends Module
{
    public function logInPlayer(Player $player, ApiTester $I): void
    {
//        var_dump($this->getModule('Symfony')->client->getCookieJar()->all());

        $token = new TestBrowserToken($player->getRoles(), $player, 'test');
        $I->grabService('security.untracked_token_storage')->setToken($token);

        $session = $I->grabService('session.factory')->createSession();
        $session->set('_security_test', serialize($token));
        $session->save();

        $I->setCookie($session->getName(), $session->getId());
    }
}
