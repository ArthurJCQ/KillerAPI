<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\Entity\Room;
use App\Tests\ApiTester;

class PlayerControllerCest
{
    public function _before(ApiTester $I): void
    {
        $I->sendPost('player', json_encode(['name' => 'John']));
        $I->seeAuthentication();
    }

    public function testCreatePlayer(ApiTester $I): void
    {
        $I->seeInRepository(Player::class, ['name' => 'John']);

        $I->canSeeResponseContainsJson(
            [
                'name' => 'John',
                'roles' => ['ROLE_PLAYER'],
                'room' => null,
                'status' => PlayerStatus::ALIVE->value
            ],
        );
    }

    public function testPatchPlayer(ApiTester $I): void
    {
        $I->sendPatch('player', json_encode(['name' => 'Hey']));
        $I->seeInRepository(Player::class, ['name' => 'Hey']);
        $I->dontSeeInRepository(Player::class, ['name' => 'John']);

        $I->canSeeResponseContainsJson(
            [
                'name' => 'Hey',
                'roles' => ['ROLE_PLAYER'],
                'status' => PlayerStatus::ALIVE->value
            ],
        );
    }

    public function testPlayerJoinRoom(ApiTester $I): void
    {
        $I->haveInRepository(Room::class, ['code' => 'XXXXX', 'name' => 'John\'s room']);
        $I->sendPatch('player', json_encode(['room' => 'XXXXX']));

        $I->seeInRepository(Player::class, ['name' => 'John', 'room' => ['code' => 'XXXXX']]);

        $I->seeResponseCodeIsSuccessful();
    }

    public function testPlayerLeaveRoom(ApiTester $I): void
    {
        $I->haveInRepository(Room::class, ['code' => 'XXXXX', 'name' => 'John\'s room']);
        $player = $I->grabEntityFromRepository(Player::class, ['name' => 'John']);
        $player->setStatus(PlayerStatus::KILLED);
        $I->flushToDatabase();

        $I->sendPatch('player', json_encode(['room' => 'XXXXX']));
        $I->seeInRepository(
            Player::class,
            ['name' => 'John', 'status' => PlayerStatus::KILLED->value, 'room' => ['code' => 'XXXXX']],
        );

        $I->sendPatch('player', json_encode(['room' => null]));
        $I->seeInRepository(Player::class, ['name' => 'John', 'status' => PlayerStatus::ALIVE->value, 'room' => null]);

        $I->seeResponseCodeIsSuccessful();
    }
}
