<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Tests\ApiTester;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertGreaterThan;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertNotEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

class SecondaryMissionsAndSwitchingCest
{
    public const string PLAYER_1 = 'Player1';
    public const string PLAYER_2 = 'Player2';
    public const string PLAYER_3 = 'Player3';

    public function testSecondaryMissionsAreCreatedWhenGameStarts(ApiTester $I): void
    {
        // Setup: Create a room with 3 players
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'Admin mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        // Player 1 joins
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_1);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player1 mission']);

        // Player 2 joins
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_2);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player2 mission']);

        // Player 3 joins
        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_3);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player3 mission']);

        // Start the game (4 players total including admin, should generate 8 secondary missions)
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);
        $I->seeResponseCodeIs(200);

        // Verify secondary missions were created in database
        $room = $I->grabEntityFromRepository(Room::class, ['id' => $room->getId()]);
        $secondaryMissions = $room->getSecondaryMissions();

        assertCount(8, $secondaryMissions, 'Expected 8 secondary missions (4 players * 2)');

        // Verify all secondary missions have content and no author
        foreach ($secondaryMissions as $mission) {
            assertNotEmpty($mission->getContent());
            assertNull($mission->getAuthor(), 'Secondary missions should not have an author');
        }
    }

    public function testPlayerCanSwitchMissionUsingSecondaryPool(ApiTester $I): void
    {
        // Setup: Create a game with 3 players
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'Admin mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_1);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player1 mission']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_2);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player2 mission']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_3);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player3 mission']);

        // Start the game
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        // Count secondary missions before switch
        $room = $I->grabEntityFromRepository(Room::class, ['id' => $room->getId()]);
        $countBefore = $room->getSecondaryMissions()->count();

        // Get player's current mission
        $I->setJwtHeader($I, self::PLAYER_1);
        $I->sendGetAsJson('/user/me');
        /** @var array $response */
        $response = json_decode($I->grabResponse(), true);
        $originalMissionId = $response['currentPlayer']['assignedMission']['id'];

        // Switch mission
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_1]);
        $I->sendPatchAsJson(sprintf('/player/%s/switch-mission', $player1Id));
        $I->seeResponseCodeIs(200);

        // Verify new mission is assigned
        $I->sendGetAsJson('/user/me');
        /** @var array $responseAfter */
        $responseAfter = json_decode($I->grabResponse(), true);
        $newMissionId = $responseAfter['currentPlayer']['assignedMission']['id'];

        assertNotEquals($originalMissionId, $newMissionId, 'Mission should have changed');

        // Verify a secondary mission was consumed from the pool
        $room = $I->grabEntityFromRepository(Room::class, ['id' => $room->getId()]);
        $countAfter = $room->getSecondaryMissions()->count();

        assertEquals($countBefore - 1, $countAfter, 'One secondary mission should have been removed from pool');

        // Verify mission switch was used (points deducted)
        assertEquals($responseAfter['currentPlayer']['points'], -5, 'Player should have -5 points after switching');
        assertTrue($responseAfter['currentPlayer']['missionSwitchUsed'], 'Mission switch should be marked as used');
    }

    public function testPlayerCannotSwitchMissionTwice(ApiTester $I): void
    {
        // Setup game with players
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'Admin mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_1);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player1 mission']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_2);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player2 mission']);

        // Start game
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        // First switch - should work
        $I->setJwtHeader($I, self::PLAYER_1);
        /** @var int $player1Id */
        $player1Id = $I->grabFromRepository(Player::class, 'id', ['name' => self::PLAYER_1, 'room' => $room->getId()]);
        $I->sendPatchAsJson(sprintf('/player/%s/switch-mission', $player1Id));
        $I->seeResponseCodeIs(200);

        // Second switch - should fail
        $I->sendPatchAsJson(sprintf('/player/%s/switch-mission', $player1Id));
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['status' => 400, 'detail' => 'MISSION_SWITCH_ALREADY_USED']);
    }

    public function testSecondaryPoolDepletionFallbackToGeneration(ApiTester $I): void
    {
        // Setup: Create a room with 3 players (will generate 6 secondary missions)
        $I->createAdminAndUpdateHeaders($I);
        $I->sendPostAsJson('room');
        $I->sendPostAsJson('/mission', ['content' => 'Admin mission']);

        $room = $I->grabEntityFromRepository(Room::class, ['name' => 'Admin\'s room']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_1);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player1 mission']);

        $I->createPlayerAndUpdateHeaders($I, self::PLAYER_2);
        $I->sendPatchAsJson('/user', ['room' => $room->getId()]);
        $I->sendPostAsJson('/mission', ['content' => 'Player2 mission']);

        // Start game (3 players = 6 secondary missions)
        $I->setAdminJwtHeader($I);
        $I->sendPatchAsJson(sprintf('/room/%s', $room->getId()), ['status' => 'IN_GAME']);

        // Verify 4 secondary missions exist
        $room = $I->grabEntityFromRepository(Room::class, ['id' => $room->getId()]);
        $secondaryMissions = $room->getSecondaryMissions();
        assertCount(6, $secondaryMissions);

        // Manually deplete the pool by removing all secondary missions
        foreach ($secondaryMissions->toArray() as $mission) {
            $room->removeSecondaryMission($mission);
        }

        $I->flushToDatabase();

        // Now try to switch - should fall back to generation
        $I->setAdminJwtHeader($I);
        $I->sendGetAsJson('/user/me');
        /** @var array $adminResponse */
        $adminResponse = json_decode($I->grabResponse(), true);
        /** @var int $adminId */
        $adminId = $adminResponse['currentPlayer']['id'];

        $I->sendPatchAsJson(sprintf('/player/%s/switch-mission', $adminId));
        $I->seeResponseCodeIs(200);

        // Verify new mission was created (not from secondary pool)
        $I->sendGetAsJson('/user/me');
        /** @var array $responseAfter */
        $responseAfter = json_decode($I->grabResponse(), true);
        assertNotNull($responseAfter['currentPlayer']['assignedMission']);

        // The new mission should be assigned to the room but not as a secondary mission
        $room = $I->grabEntityFromRepository(Room::class, ['id' => $room->getId()]);
        $allMissions = $I->grabEntitiesFromRepository(Mission::class, ['room' => $room]);
        $generatedMissionCount = 0;

        foreach ($allMissions as $mission) {
            if ($mission->getAuthor() !== null || $room->getSecondaryMissions()->contains($mission)) {
                continue;
            }

            $generatedMissionCount++;
        }

        assertGreaterThan(0, $generatedMissionCount, 'At least one generated mission should exist');
    }
}
