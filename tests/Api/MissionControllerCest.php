<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Tests\ApiTester;

class MissionControllerCest
{
    public function _before(ApiTester $I): void
    {
        $I->sendPost('player', json_encode(['name' => 'John']));
    }

    public function testCreateMissionFailWithoutRoom(ApiTester $I): void
    {
        $I->sendPost('mission', json_encode(['content' => 'mission']));
        $I->seeResponseCodeIs(400);
    }

    public function testCreateMission(ApiTester $I): void
    {
        $I->sendPost('room');
        $I->sendPost('mission', json_encode(['content' => 'mission']));
        $I->seeResponseCodeIsSuccessful();
    }
}
