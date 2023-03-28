<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Tests\ApiTester;

class MissionControllerCest
{
    public function _before(ApiTester $I): void
    {
        $I->createAdminAndUpdateHeaders($I);
    }

    public function testCreateMissionFailWithoutRoom(ApiTester $I): void
    {
        $I->sendPost('mission', (string) json_encode(['content' => 'mission']));
        $I->seeResponseCodeIs(400);
    }

    public function testCreateMission(ApiTester $I): void
    {
        $I->sendPost('room');
        $I->sendPost('mission', (string) json_encode(['content' => 'mission']));
        $I->seeResponseCodeIsSuccessful();
    }

    public function testCreateMissionNotEnoughCharacters(ApiTester $I): void
    {
        $I->sendPost('room');
        $I->sendPost('mission', (string) json_encode(['content' => 'mi']));
        $I->seeResponseCodeIs(400);
    }
}
