<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Room\Entity\Room;
use App\Tests\ApiTester;

class RoomControllerCest
{
    public function _before(ApiTester $I): void
    {
        $I->sendPost('player', (string) json_encode(['name' => 'John']));
        $I->sendPost('room');
    }

    public function testCreateRoom(ApiTester $I): void
    {
        $I->seeResponseContainsJson(['roles' => ['ROLE_ADMIN']]);
    }

    public function testPatchRoom(ApiTester $I): void
    {
        /** @var int $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => 'John\'s room']);
        $I->sendPatch(sprintf('room/%d', $roomId), (string) json_encode(['name' => 'new name']));
        $I->seeInRepository(Room::class, ['name' => 'new name']);

        $I->seeResponseCodeIsSuccessful();
    }

    public function testUpdateRoomNotEnoughPlayers(ApiTester $I): void
    {
        /** @var int $roomId */
        $roomId = $I->grabFromRepository(Room::class, 'id', ['name' => 'John\'s room']);

        $I->sendPatch(sprintf('room/%d', $roomId), (string) json_encode(['status' => 'IN_GAME']));
        $I->seeResponseCodeIs(400);
    }
}
