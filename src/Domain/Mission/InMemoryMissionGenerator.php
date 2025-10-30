<?php

declare(strict_types=1);

namespace App\Domain\Mission;

use App\Domain\Mission\Enum\MissionTheme;

class InMemoryMissionGenerator implements MissionGeneratorInterface
{
    private array $missionsByTheme = [
        MissionTheme::GENERIC->value => [
            'Prendre un selfie avec ta cible en faisant tous les deux un clin d’oeil',
            'Obtenir de ta cible qu’elle échange un vêtement (au moins un accessoire : écharpe, casquette…)',
            'Que ta cible boive (ou simule boire) un verre préparé par toi',
            'Récupérer un objet personnel de ta cible (stylo, clé USB, etc.) et le rendre ensuite',
            'Obtenir un compliment public de la part de ta cible devant au moins 2 autres joueurs',
            'Faire faire à ta cible 1 squat',
            'Récupérer un autographe (ou un dessin) de ta cible sur ton téléphone',
            'Remporter un duel de bras de fer chinois contre ta cible',
            'Obtenir de ta cible qu’elle t’applaudisse spontanément',
            'Faire dire à ta cible le nom d’un fruit sans rapport avec la discussion',
            'Que ta cible change de chaise ou de place à ta demande',
            'Faire dire à ta cible "Tu as raison"',
            'Créer un high five personnalisé avec ta cible',
            'Obtenir que ta cible répète une phrase que tu viens de dire, mot pour mot',
            'Faire dire à ta cible une citation de film',
            'Que ta cible te tienne un objet pendant au moins 10 secondes',
            'Obtenir que ta cible prenne une photo de toi (pas un selfie)',
            'Faire croire à ta cible qu’il y a une araignée sur son épaule',
            'Faire dire à ta cible le nom d’un film d’horreur',
            'Faire dire à ta cible le mot “cauchemar” sans le prononcer toi-même',
            'Que ta cible te regarde droit dans les yeux pendant 10 secondes sans rire',
        ],
    ];

    public function generateMissions(int $count, ?MissionTheme $theme = null): array
    {
        $availableMissions = $theme !== null
            ? $this->missionsByTheme[$theme->value]
            : array_merge(...array_values($this->missionsByTheme));
        $generateAllMissions = $count >= \count($availableMissions);

        // Randomly select $count missions without duplicates
        $shuffled = $availableMissions;
        shuffle($shuffled);

        return \array_slice($shuffled, 0, $generateAllMissions ? null : $count);
    }
}
