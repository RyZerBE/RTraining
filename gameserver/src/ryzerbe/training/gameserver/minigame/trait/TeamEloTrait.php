<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\trait;

use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\statssystem\provider\StatsProvider;
use ryzerbe\statssystem\StatsSystem;
use ryzerbe\training\gameserver\game\team\Team;
use function array_map;
use function count;

trait TeamEloTrait {
    public function loadTeamElo(Team $team, string $minigame): void {
        $players = array_map(function(Player $player): string {
            return $player->getName();
        }, $team->getPlayers());
        AsyncExecutor::submitMySQLAsyncTask(StatsSystem::DATABASE, function(mysqli $mysqli) use ($players, $minigame): int {
            $elo = 0;
            foreach($players as $player) {
                $statistics = StatsProvider::getStatistics($mysqli, $player, $minigame);
                if($statistics === null){
                    $elo += 1000;
                    continue;
                }
                $elo += StatsProvider::getStatistics($mysqli, $player, $minigame)["elo"] ?? 1000;
            }
            return ($elo / count($players));
        }, function(Server $server, int $elo) use ($team): void {
            $team->setElo($elo);
        });
    }
}