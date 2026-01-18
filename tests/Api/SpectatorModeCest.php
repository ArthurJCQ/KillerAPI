<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\Entity\Room;
use App\Tests\ApiTester;
use PHPUnit\Framework\Assert;

class SpectatorModeCest
{
    public function testAdminCanEnableSpectatorMode(ApiTester $I): void
    {
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Enable spectators
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['allowSpectators' => true]);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson(['allowSpectators' => true]);

        // Verify it persisted
        $I->sendGetAsJson(sprintf('/room/%s', $room->getId()));
        $I->seeResponseContainsJson(['allowSpectators' => true]);
    }

    public function testCanJoinRoomAsSpectator(ApiTester $I): void
    {
        // Create admin and room with spectators allowed
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Enable spectators
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['allowSpectators' => true]);
        $I->seeResponseCodeIsSuccessful();

        // Create a new user and join as spectator
        $I->createPlayerAndUpdateHeaders($I, 'Spectator');
        $I->sendPatchAsJson('/user', ['room' => $room->getId(), 'spectate' => true]);
        $I->seeResponseCodeIsSuccessful();

        // Verify player has SPECTATING status
        $I->seeInRepository(Player::class, [
            'name' => 'Spectator',
            'room' => $room->getId(),
            'status' => PlayerStatus::SPECTATING,
        ]);
    }

    public function testCannotJoinAsSpectatorWhenDisabled(ApiTester $I): void
    {
        // Create admin and room (spectators disabled by default)
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Try to join as spectator
        $I->createPlayerAndUpdateHeaders($I, 'Spectator');
        $I->sendPatchAsJson('/user', ['room' => $room->getId(), 'spectate' => true]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['message' => 'ROOM_SPECTATORS_NOT_ALLOWED']);
    }

    public function testSpectatorSeesLimitedRoomData(ApiTester $I): void
    {
        // Create admin and room with spectators allowed
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'Secret mission 1']);
        $I->sendPostAsJson('/mission', ['content' => 'Secret mission 2']);
        $I->sendPostAsJson('/mission', ['content' => 'Secret mission 3']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Enable spectators
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['allowSpectators' => true]);

        // Add regular players
        $I->createPlayerAndUpdateHeaders($I, 'John');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);

        $I->createPlayerAndUpdateHeaders($I, 'Jane');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);

        // Start the game
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);
        $I->seeResponseCodeIsSuccessful();

        // Join as spectator
        $I->createPlayerAndUpdateHeaders($I, 'Spectator');
        $I->sendPatchAsJson('/user', ['room' => $room->getId(), 'spectate' => true]);
        $I->seeResponseCodeIsSuccessful();

        // Get room data as spectator
        $I->sendGetAsJson(sprintf('/room/%s', $room->getId()));
        $I->seeResponseCodeIsSuccessful();

        // Spectator should see basic player info
        $I->seeResponseContainsJson([
            'id' => $room->getId(),
            'name' => 'Admin\'s room',
            'status' => Room::IN_GAME,
            'allowSpectators' => true,
        ]);

        // Spectator should see players with limited info (name, status, points)
        /** @var array<string, mixed> $response */
        $response = json_decode($I->grabResponse(), true);
        Assert::assertArrayHasKey('players', $response);

        /** @var array<int, array<string, mixed>> $players */
        $players = $response['players'];

        foreach ($players as $player) {
            // Should have these fields
            Assert::assertArrayHasKey('id', $player);
            Assert::assertArrayHasKey('name', $player);
            Assert::assertArrayHasKey('status', $player);
            Assert::assertArrayHasKey('points', $player);
            Assert::assertArrayHasKey('avatar', $player);

            // Should NOT have sensitive fields (target, assignedMission)
            Assert::assertArrayNotHasKey('target', $player);
            Assert::assertArrayNotHasKey('assignedMission', $player);
        }

        // Should NOT see missions
        Assert::assertArrayNotHasKey('missions', $response);
        Assert::assertArrayNotHasKey('secondaryMissions', $response);
    }

    public function testRegularPlayerSeesFullRoomData(ApiTester $I): void
    {
        // Create admin and room
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'mission 1']);
        $I->sendPostAsJson('/mission', ['content' => 'mission 2']);
        $I->sendPostAsJson('/mission', ['content' => 'mission 3']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Add regular players
        $I->createPlayerAndUpdateHeaders($I, 'John');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);

        $I->createPlayerAndUpdateHeaders($I, 'Jane');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);

        // Start the game
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);
        $I->seeResponseCodeIsSuccessful();

        // Get room data as regular player (Admin)
        $I->sendGetAsJson(sprintf('/room/%s', $room->getId()));
        $I->seeResponseCodeIsSuccessful();

        // Regular player should see missions
        /** @var array<string, mixed> $response */
        $response = json_decode($I->grabResponse(), true);
        Assert::assertArrayHasKey('missions', $response);
    }

    public function testGameMasterSeesEverythingDespiteSpectatingStatus(ApiTester $I): void
    {
        // Create admin with game master mode
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room', ['isGameMastered' => true]);
        $I->sendPostAsJson('/mission', ['content' => 'mission 1']);
        $I->sendPostAsJson('/mission', ['content' => 'mission 2']);
        $I->sendPostAsJson('/mission', ['content' => 'mission 3']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Add players
        $I->createPlayerAndUpdateHeaders($I, 'John');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);

        $I->createPlayerAndUpdateHeaders($I, 'Jane');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);

        $I->createPlayerAndUpdateHeaders($I, 'Doe');
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);

        // Start the game as game master
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);
        $I->seeResponseCodeIsSuccessful();

        // Verify admin has SPECTATING status but is a master
        $I->sendGetAsJson('/user/me');
        $I->seeResponseContainsJson([
            'currentPlayer' => [
                'status' => PlayerStatus::SPECTATING->value,
                'isMaster' => true,
            ],
        ]);

        // Get room data as game master - should see full data including missions
        $I->sendGetAsJson(sprintf('/room/%s', $room->getId()));
        $I->seeResponseCodeIsSuccessful();

        /** @var array<string, mixed> $response */
        $response = json_decode($I->grabResponse(), true);
        Assert::assertArrayHasKey('missions', $response);
    }

    public function testSpectatorCannotJoinRegularWhenAlreadySpectating(ApiTester $I): void
    {
        // Create admin and room with spectators allowed
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Enable spectators
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['allowSpectators' => true]);

        // Join as spectator
        $I->createPlayerAndUpdateHeaders($I, 'Spectator');
        $I->sendPatchAsJson('/user', ['room' => $room->getId(), 'spectate' => true]);
        $I->seeResponseCodeIsSuccessful();

        // Verify player status is SPECTATING
        $I->seeInRepository(Player::class, [
            'name' => 'Spectator',
            'status' => PlayerStatus::SPECTATING,
        ]);
    }
}
