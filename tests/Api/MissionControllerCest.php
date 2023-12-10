<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Mission\Entity\Mission;
use App\Tests\ApiTester;

class MissionControllerCest
{
    public function _before(ApiTester $I): void
    {
        $I->createAdminAndUpdateHeaders($I);
    }

    public function testCreateMissionFailWithoutRoom(ApiTester $I): void
    {
        $I->sendPostAsJson('mission', ['content' => 'mission']);
        $I->seeResponseCodeIs(400);
    }

    public function testCreateMission(ApiTester $I): void
    {
        $I->sendPostAsJson('room');
        $I->sendPostAsJson('mission', ['content' => 'mission']);
        $I->seeResponseCodeIsSuccessful();
    }

    public function testCreateMissionNotEnoughCharacters(ApiTester $I): void
    {
        $I->sendPostAsJson('room');
        $I->sendPostAsJson('mission', ['content' => 'mi']);
        $I->seeResponseCodeIs(422);
    }

    public function testPatchMissionContent(ApiTester $I): void
    {
        $I->sendPostAsJson('room');
        $I->sendPostAsJson('mission', ['content' => 'mission outdated']);

        /** @var string $missionId */
        $missionId = $I->grabFromRepository(Mission::class, 'id', ['content' => 'mission outdated']);

        $I->sendPatch(sprintf('mission/%s', $missionId), ['content' => 'updated content']);
        $I->seeResponseCodeIsSuccessful();
    }
}
