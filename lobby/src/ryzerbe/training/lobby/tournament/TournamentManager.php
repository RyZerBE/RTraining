<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\tournament;

use pocketmine\Player;
use ryzerbe\training\lobby\minigame\Minigame;

class TournamentManager {
    private static int $id = 0;

    /** @var Tournament[]  */
    private static array $tournaments = [];

    /**
     * @return Tournament[]
     */
    public static function getTournaments(): array{
        return self::$tournaments;
    }

    public static function createTournament(Player $host, Minigame $minigame, int $playerCount, bool $public): Tournament {
        return (self::$tournaments[++self::$id] = new Tournament($host, $minigame, $playerCount, $public, self::$id));
    }

    public static function removeTournament(Tournament $tournament): void {
        unset(self::$tournaments[$tournament->getId()]);
    }

    public static function getTournament(int $id): ?Tournament {
        return self::$tournaments[$id] ?? null;
    }

    public static function getTournamentByPlayer(Player $player): ?Tournament {
        foreach(self::getTournaments() as $tournament) {
            if($tournament->isPlayer($player)) return $tournament;
        }
        return null;
    }

    /**
     * @return Tournament[]
     */
    public static function getTournamentInvitesByPlayer(Player $player): array {
        $invites = [];
        foreach(self::getTournaments() as $tournament) {
            if(!$tournament->isInvitedPlayer($player)) continue;
            $invites[] = $tournament;
        }
        return $invites;
    }
}